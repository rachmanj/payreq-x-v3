<?php

namespace App\Services;

use App\Models\Bilyet;
use App\Models\Installment;
use App\Events\BilyetCreated;
use Exception;
use Illuminate\Support\Facades\DB;

class InstallmentPaymentService
{
    protected $loanPaymentService;

    public function __construct(LoanPaymentService $loanPaymentService)
    {
        $this->loanPaymentService = $loanPaymentService;
    }

    public function createBilyetAndPay(int $installmentId, array $bilyetData): Bilyet
    {
        try {
            DB::beginTransaction();

            $installment = Installment::findOrFail($installmentId);

            $bilyetData['loan_id'] = $installment->loan_id;
            $bilyetData['purpose'] = 'loan_payment';
            $bilyetData['created_by'] = auth()->id();

            if (!isset($bilyetData['amount']) || !$bilyetData['amount']) {
                $bilyetData['amount'] = $installment->bilyet_amount;
            }

            if (!isset($bilyetData['status'])) {
                $bilyetData['status'] = 'onhand';
            }

            $bilyet = Bilyet::create($bilyetData);

            event(new BilyetCreated($bilyet, auth()->user()));

            $this->loanPaymentService->linkBilyetToInstallment($bilyet->id, $installmentId);

            DB::commit();
            return $bilyet;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function markAsPaid(int $installmentId, int $bilyetId = null): Installment
    {
        try {
            DB::beginTransaction();

            $installment = Installment::findOrFail($installmentId);

            if ($bilyetId) {
                $this->loanPaymentService->linkBilyetToInstallment($bilyetId, $installmentId);
            } else {
                $installment->paid_date = now();
                $installment->status = 'paid';
                $installment->save();
            }

            DB::commit();
            return $installment->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function markAsAutoDebitPaid(int $installmentId, $paidDate = null, $accountId = null): Installment
    {
        try {
            DB::beginTransaction();

            $installment = Installment::findOrFail($installmentId);

            $installment->payment_method = 'auto_debit';
            $installment->paid_date = $paidDate; // Allow null - will be set later when payment is realized
            // Only mark as paid if paid_date is provided
            $installment->status = $paidDate ? 'paid' : null;
            $installment->bilyet_id = null;
            
            if ($accountId) {
                $installment->account_id = $accountId;
            }

            $installment->save();

            DB::commit();

            return $installment->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
