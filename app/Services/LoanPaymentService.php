<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bilyet;
use App\Models\Installment;
use App\Models\Loan;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoanPaymentService
{
    public function validateBilyetForInstallment(Bilyet $bilyet, Installment $installment): array
    {
        $errors = [];

        if ($bilyet->status === 'void') {
            $errors[] = 'Cannot link voided bilyet to installment';
        }

        if ($bilyet->purpose !== 'loan_payment') {
            $errors[] = 'Bilyet purpose must be set to "loan_payment"';
        }

        if ($bilyet->loan_id && $bilyet->loan_id !== $installment->loan_id) {
            $errors[] = 'Bilyet is linked to a different loan';
        }

        $tolerance = 0.01;
        if ($bilyet->amount < ($installment->bilyet_amount * (1 - $tolerance))) {
            $errors[] = 'Bilyet amount is less than installment amount';
        }

        // Find Account that matches Giro's acc_no
        $giroAccount = Account::where('account_number', $bilyet->giro->acc_no)
            ->where('type', 'bank')
            ->first();

        if ($giroAccount && $installment->account_id && $giroAccount->id !== $installment->account_id) {
            $errors[] = 'Bilyet bank account does not match installment account';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public function linkBilyetToInstallment(int $bilyetId, int $installmentId): bool
    {
        try {
            DB::beginTransaction();

            $bilyet = Bilyet::findOrFail($bilyetId);
            $installment = Installment::findOrFail($installmentId);

            $validation = $this->validateBilyetForInstallment($bilyet, $installment);

            if (!$validation['valid']) {
                throw new Exception(implode(', ', $validation['errors']));
            }

            $installment->bilyet_id = $bilyetId;
            $installment->payment_method = 'bilyet';

            // Automatically set account_id from giro's acc_no
            $giro = $bilyet->giro;
            $account = Account::where('account_number', $giro->acc_no)
                ->where('type', 'bank')
                ->first();

            if ($account) {
                $installment->account_id = $account->id;
            } else {
                // Log warning but don't fail - account might be created later
                Log::warning('Account not found for giro acc_no when linking bilyet', [
                    'giro_id' => $giro->id,
                    'acc_no' => $giro->acc_no,
                    'installment_id' => $installmentId,
                    'bilyet_id' => $bilyetId
                ]);
            }

            if ($bilyet->status === 'cair' && !$installment->paid_date) {
                $installment->paid_date = $bilyet->cair_date ?? now();
                $installment->status = 'paid';
            }

            $installment->save();

            if (!$bilyet->loan_id) {
                $bilyet->loan_id = $installment->loan_id;
                $bilyet->save();
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function linkExistingBilyetToInstallment(int $bilyetId, int $installmentId): bool
    {
        try {
            DB::beginTransaction();

            $bilyet = Bilyet::findOrFail($bilyetId);
            $installment = Installment::findOrFail($installmentId);

            // Validate bilyet is onhand
            if ($bilyet->status !== 'onhand') {
                throw new Exception('Bilyet must have status "onhand" to be linked');
            }

            // Validate bilyet purpose
            if ($bilyet->purpose !== 'loan_payment') {
                throw new Exception('Bilyet purpose must be "loan_payment"');
            }

            // Check if bilyet is already linked to another installment
            $existingInstallment = Installment::where('bilyet_id', $bilyetId)
                ->where('id', '!=', $installmentId)
                ->first();
            if ($existingInstallment) {
                throw new Exception('Bilyet is already linked to another installment');
            }

            // Check if installment already has a bilyet linked
            if ($installment->bilyet_id && $installment->bilyet_id != $bilyetId) {
                throw new Exception('Installment already has a bilyet linked. Please unlink it first.');
            }

            // Link bilyet to installment
            $installment->bilyet_id = $bilyetId;
            $installment->payment_method = 'bilyet';

            // Automatically set account_id from giro's acc_no
            $giro = $bilyet->giro;
            if (!$giro) {
                throw new Exception('Bilyet must have a giro account');
            }

            $account = Account::where('account_number', $giro->acc_no)
                ->where('type', 'bank')
                ->first();

            if ($account) {
                $installment->account_id = $account->id;
            } else {
                Log::warning('Account not found for giro acc_no when linking existing bilyet', [
                    'giro_id' => $giro->id,
                    'acc_no' => $giro->acc_no,
                    'installment_id' => $installmentId,
                    'bilyet_id' => $bilyetId
                ]);
            }

            $installment->save();

            // Update bilyet: set status to 'release' and amount to installment amount
            $bilyet->status = 'release';
            $bilyet->amount = $installment->bilyet_amount;

            // Set loan_id if not already set
            if (!$bilyet->loan_id && $installment->loan_id) {
                $bilyet->loan_id = $installment->loan_id;
            }

            $bilyet->save();

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function unlinkBilyet(int $installmentId): bool
    {
        try {
            DB::beginTransaction();

            $installment = Installment::findOrFail($installmentId);

            $installment->bilyet_id = null;
            $installment->payment_method = null;

            if ($installment->status === 'paid' && $installment->paid_date) {
                $installment->paid_date = null;
                $installment->status = null;
            }

            $installment->save();

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function markInstallmentAsPaid(Installment $installment, string $paymentMethod, $paidDate = null)
    {
        if (!in_array($paymentMethod, array_keys(Installment::PAYMENT_METHODS))) {
            throw new Exception('Invalid payment method');
        }

        if ($paymentMethod === 'bilyet' && !$installment->bilyet_id) {
            throw new Exception('Bilyet payment method requires a linked bilyet');
        }

        $installment->payment_method = $paymentMethod;
        $installment->paid_date = $paidDate ?? now();
        $installment->status = 'paid';
        $installment->save();

        return $installment;
    }
}
