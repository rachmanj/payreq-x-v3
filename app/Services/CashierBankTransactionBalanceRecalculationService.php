<?php

namespace App\Services;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Cashier\TransaksiController;
use App\Models\Account;
use App\Models\Incoming;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\VerificationJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashierBankTransactionBalanceRecalculationService
{
    public function findIncomingByJournalNumber(VerificationJournal $journal): ?Incoming
    {
        if ($journal->nomor === null || $journal->nomor === '') {
            return null;
        }

        return Incoming::query()
            ->where('nomor', $journal->nomor)
            ->first();
    }

    public function incomingHasBeenBooked(Incoming $incoming): bool
    {
        return Transaksi::query()
            ->where('document_type', 'incoming')
            ->where('document_id', $incoming->id)
            ->exists();
    }

    public function needsRecalculateBalance(VerificationJournal $journal): bool
    {
        if ($journal->type !== 'bank') {
            return false;
        }

        if (empty($journal->sap_journal_no)) {
            return false;
        }

        $incoming = $this->findIncomingByJournalNumber($journal);
        if ($incoming === null) {
            return false;
        }

        return ! $this->incomingHasBeenBooked($incoming);
    }

    /**
     * @return array{status: string, message?: string, amount?: int, balance_after?: int}
     */
    public function recalculate(VerificationJournal $journal, User $user): array
    {
        if ($journal->type !== 'bank' || empty($journal->sap_journal_no)) {
            return [
                'status' => 'ineligible',
                'message' => 'This bank transaction is not eligible for balance recalculation.',
            ];
        }

        return DB::transaction(function () use ($journal, $user) {
            $incoming = Incoming::query()
                ->where('nomor', $journal->nomor)
                ->lockForUpdate()
                ->first();

            if ($incoming === null) {
                return [
                    'status' => 'ineligible',
                    'message' => 'This bank transaction is not eligible for balance recalculation.',
                ];
            }

            if ($this->incomingHasBeenBooked($incoming)) {
                return [
                    'status' => 'already_booked',
                    'message' => 'Saldo transaksi ini sudah dibukukan, tidak dihitung ulang.',
                ];
            }

            $bookingProject = ($incoming->project !== null && $incoming->project !== '')
                ? $incoming->project
                : $user->project;

            $cashAccount = Account::query()
                ->where('type', 'cash')
                ->where('project', $bookingProject)
                ->orderBy('id')
                ->first();

            $balanceBefore = $cashAccount ? (int) $cashAccount->app_balance : 0;

            $booked = ($incoming->project !== null && $incoming->project !== '')
                ? app(AccountController::class)->incomingForProject($incoming->project, (float) $incoming->amount)
                : app(AccountController::class)->incoming((float) $incoming->amount);

            if (! $booked) {
                throw new \RuntimeException(
                    'Cash or advance account not found for project '.$bookingProject.'.'
                );
            }

            app(TransaksiController::class)->store('incoming', $incoming);

            $cashAccount?->refresh();
            $balanceAfter = $cashAccount ? (int) $cashAccount->app_balance : $balanceBefore;

            Log::info('CASHIER BALANCE RECALCULATED', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'vj_nomor' => $journal->nomor,
                'incoming_id' => $incoming->id,
                'amount' => (int) $incoming->amount,
                'cash_balance_before' => $balanceBefore,
                'cash_balance_after' => $balanceAfter,
                'recalculated_at' => now()->toDateTimeString(),
            ]);

            return [
                'status' => 'success',
                'amount' => (int) $incoming->amount,
                'balance_after' => $balanceAfter,
            ];
        });
    }
}
