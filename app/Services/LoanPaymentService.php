<?php

namespace App\Services;

use App\Models\Bilyet;
use App\Models\Installment;
use App\Models\Loan;
use Exception;
use Illuminate\Support\Facades\DB;

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

        if ($bilyet->giro->id !== $installment->account_id && $installment->account_id) {
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
