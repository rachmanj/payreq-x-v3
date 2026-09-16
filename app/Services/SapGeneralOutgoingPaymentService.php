<?php

namespace App\Services;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Cashier\TransaksiController;
use App\Models\Account;
use App\Models\Bilyet;
use App\Models\GeneralOutgoingPayment;
use App\Models\GeneralOutgoingPaymentAccount;
use App\Models\Giro;
use App\Models\SapSubmissionLog;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SapGeneralOutgoingPaymentService
{
    public function __construct(protected SapService $sapService) {}

    /**
     * @param  list<array{account_id: int, amount: float|int, description?: string|null, profit_center?: string|null}>  $destinationLines
     * @return array{success: bool, message?: string, preview?: array<string, mixed>, sap_payload?: array<string, mixed>, local_impact?: array<string, mixed>}
     */
    public function preview(
        int $giroId,
        int $bilyetId,
        float $amount,
        string $docDate,
        string $project,
        ?string $remarks,
        array $destinationLines,
        User $user,
        ?string $preparedBy = null,
        ?string $approvedBy = null,
    ): array {
        $context = $this->resolveSubmissionContext(
            $giroId,
            $bilyetId,
            $amount,
            $docDate,
            $project,
            $remarks,
            $destinationLines,
            $user,
            $preparedBy,
            $approvedBy,
        );

        if (isset($context['error'])) {
            return ['success' => false, 'message' => $context['error']];
        }

        $builder = $context['builder'];
        $errors = $builder->validate();
        if ($errors !== []) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $destinationAccounts = $context['destination_accounts'];

        return [
            'success' => true,
            'preview' => $builder->getPreviewData(),
            'sap_payload' => $builder->build(),
            'local_impact' => [
                'destination_accounts' => array_map(static fn (array $line) => [
                    'account_id' => $line['account']->id,
                    'account_name' => $line['account']->account_name,
                    'sap_account' => $line['account']->sap_account,
                    'amount' => (float) $line['amount'],
                ], $destinationAccounts['lines']),
                'total_amount' => $amount,
                'note' => 'Saldo kas akan bertambah, akun advance berkurang sesuai nominal tiap akun tujuan.',
            ],
        ];
    }

    /**
     * @param  list<array{account_id: int, amount: float|int, description?: string|null, profit_center?: string|null}>  $destinationLines
     * @return array{success: bool, message: string, doc_num?: string, general_outgoing_payment_id?: int}
     */
    public function submit(
        int $giroId,
        int $bilyetId,
        float $amount,
        string $docDate,
        string $postingDate,
        string $project,
        ?string $remarks,
        array $destinationLines,
        User $user,
        ?string $preparedBy = null,
        ?string $approvedBy = null,
    ): array {
        $existingPayment = GeneralOutgoingPayment::query()
            ->where('bilyet_id', $bilyetId)
            ->where('status', GeneralOutgoingPayment::STATUS_SUCCESS)
            ->first();

        if ($existingPayment) {
            return [
                'success' => true,
                'message' => 'Outgoing Payment sudah tercatat (DocNum: '.$existingPayment->sap_doc_num.').',
                'doc_num' => (string) $existingPayment->sap_doc_num,
                'general_outgoing_payment_id' => $existingPayment->id,
            ];
        }

        $context = $this->resolveSubmissionContext(
            $giroId,
            $bilyetId,
            $amount,
            $docDate,
            $project,
            $remarks,
            $destinationLines,
            $user,
            $preparedBy,
            $approvedBy,
        );

        if (isset($context['error'])) {
            return ['success' => false, 'message' => $context['error']];
        }

        $giro = $context['giro'];
        $bilyet = $context['bilyet'];
        $destinationAccounts = $context['destination_accounts'];
        $builder = $context['builder'];

        $errors = $builder->validate();
        if ($errors !== []) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $payload = $builder->build();

        try {
            $sapResult = $this->sapService->createGeneralOutgoingPayment($payload);

            if (! ($sapResult['success'] ?? false)) {
                $message = $sapResult['message'] ?? 'Gagal membuat Outgoing Payment di SAP B1.';
                $this->logSubmission($user, 'failed', $message, null, (int) round($amount));

                return ['success' => false, 'message' => $message];
            }

            $docNum = (string) ($sapResult['doc_num'] ?? '');
            $docEntry = isset($sapResult['doc_entry']) ? (int) $sapResult['doc_entry'] : null;
            $paymentDate = Carbon::parse($docDate);

            $payment = DB::transaction(function () use (
                $giro,
                $bilyet,
                $destinationAccounts,
                $amount,
                $docDate,
                $postingDate,
                $project,
                $remarks,
                $user,
                $docNum,
                $docEntry,
                $paymentDate,
            ) {
                $firstSapAccount = (string) $destinationAccounts['lines'][0]['account']->sap_account;

                $payment = GeneralOutgoingPayment::query()->create([
                    'giro_id' => $giro->id,
                    'bilyet_id' => $bilyet->id,
                    'posting_date' => $postingDate,
                    'doc_date' => $docDate,
                    'amount' => (int) round($amount),
                    'remarks' => $remarks,
                    'project' => $project,
                    'akun_tujuan_utama' => $firstSapAccount,
                    'sap_doc_num' => $docNum !== '' ? $docNum : null,
                    'sap_doc_entry' => $docEntry,
                    'status' => GeneralOutgoingPayment::STATUS_SUCCESS,
                    'created_by' => $user->id,
                    'submitted_at' => now(),
                    'sap_error_message' => null,
                ]);

                foreach ($destinationAccounts['lines'] as $line) {
                    $account = $line['account'];

                    GeneralOutgoingPaymentAccount::query()->create([
                        'general_outgoing_payment_id' => $payment->id,
                        'account_id' => $account->id,
                        'sap_account' => (string) $account->sap_account,
                        'account_name' => $account->account_name,
                        'amount' => (int) round((float) $line['amount']),
                        'description' => $line['description'] ?? null,
                        'profit_center' => $line['profit_center'] ?? null,
                    ]);
                }

                $bilyet->update([
                    'status' => 'cair',
                    'bilyet_date' => $paymentDate->format('Y-m-d'),
                    'cair_date' => $paymentDate->format('Y-m-d'),
                    'amount' => $amount,
                    'remarks' => $remarks,
                    'sap_doc_num' => $docNum !== '' ? $docNum : null,
                ]);

                $this->applyLocalBalanceEffects($payment, $giro, $bilyet, $destinationAccounts['lines'], $docNum, $paymentDate);

                return $payment;
            });

            $this->logSubmission(
                $user,
                'success',
                null,
                $sapResult['data'] ?? $sapResult,
                (int) round($amount),
                $docNum,
                $docEntry,
            );

            return [
                'success' => true,
                'message' => 'Outgoing Payment berhasil dibuat di SAP B1.',
                'doc_num' => $docNum,
                'general_outgoing_payment_id' => $payment->id,
            ];
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
            $this->logSubmission($user, 'failed', $message, null, (int) round($amount));

            Log::error('General Outgoing Payment submission failed', [
                'giro_id' => $giroId,
                'bilyet_id' => $bilyetId,
                'error' => $message,
            ]);

            return [
                'success' => false,
                'message' => 'Gagal membuat Outgoing Payment: '.$message,
            ];
        }
    }

    /**
     * @param  list<array{account: Account, amount: float|int, description?: string|null, profit_center?: string|null}>  $destinationLines
     */
    protected function applyLocalBalanceEffects(
        GeneralOutgoingPayment $payment,
        Giro $giro,
        Bilyet $bilyet,
        array $destinationLines,
        string $docNum,
        Carbon $paymentDate,
    ): void {
        $bankLabel = trim((string) ($giro->acc_name ?: $giro->acc_no));
        $bilyetLabel = trim($bilyet->prefix.' '.$bilyet->nomor);
        $opLabel = $docNum !== '' ? " (OP {$docNum})" : '';

        foreach ($destinationLines as $line) {
            $account = $line['account'];
            $lineAmount = (float) $line['amount'];

            $alreadyRecorded = Transaksi::query()
                ->where('document_type', 'incoming')
                ->where('document_id', $payment->id)
                ->where('account_id', $account->id)
                ->exists();

            if ($alreadyRecorded) {
                continue;
            }

            app(AccountController::class)->incomingTo($account, $lineAmount);

            $description = 'Pinbuk dari '.$bankLabel.' - Bilyet '.$bilyetLabel.$opLabel;

            app(TransaksiController::class)->storeIncomingForAccount(
                $account->id,
                $payment->id,
                'incoming',
                $paymentDate->format('Y-m-d'),
                $description,
                $lineAmount,
            );
        }
    }

    /**
     * @param  list<array{account_id: int, amount: float|int, description?: string|null, profit_center?: string|null}>  $destinationLines
     * @return array{
     *     giro: Giro,
     *     bilyet: Bilyet,
     *     destination_accounts: array{lines: list<array{account: Account, amount: float|int, description?: string|null, profit_center?: string|null}>},
     *     builder: SapGeneralOutgoingPaymentBuilder
     * }|array{error: string}
     */
    protected function resolveSubmissionContext(
        int $giroId,
        int $bilyetId,
        float $amount,
        string $docDate,
        string $project,
        ?string $remarks,
        array $destinationLines,
        User $user,
        ?string $preparedBy = null,
        ?string $approvedBy = null,
    ): array {
        $giro = Giro::query()->find($giroId);
        if (! $giro) {
            return ['error' => 'Giro tidak ditemukan.'];
        }

        $bilyet = Bilyet::query()->find($bilyetId);
        if (! $bilyet) {
            return ['error' => 'Bilyet tidak ditemukan.'];
        }

        $destinationAccounts = $this->resolveDestinationAccounts($destinationLines);
        if (isset($destinationAccounts['error'])) {
            return ['error' => $destinationAccounts['error']];
        }

        $builder = new SapGeneralOutgoingPaymentBuilder(
            $giro,
            $bilyet,
            $amount,
            $docDate,
            $project,
            $remarks,
            $destinationAccounts['lines'],
            $preparedBy ?? $user->name,
            $approvedBy ?? $user->name,
            $this->resolveDefaultProfitCenter($user),
        );

        return [
            'giro' => $giro,
            'bilyet' => $bilyet,
            'destination_accounts' => $destinationAccounts,
            'builder' => $builder,
        ];
    }

    protected function resolveDefaultProfitCenter(User $user): ?string
    {
        $user->loadMissing('department');

        $sapCode = trim((string) ($user->department?->sap_code ?? ''));

        return $sapCode !== '' ? $sapCode : null;
    }

    /**
     * @param  list<array{account_id: int, amount: float|int, description?: string|null, profit_center?: string|null}>  $destinationLines
     * @return array{lines: list<array{account: Account, amount: float|int, description?: string|null, profit_center?: string|null}>}|array{error: string}
     */
    protected function resolveDestinationAccounts(array $destinationLines): array
    {
        if ($destinationLines === []) {
            return ['error' => 'Daftar akun tujuan tidak boleh kosong.'];
        }

        $lines = [];

        foreach ($destinationLines as $line) {
            $account = Account::query()->find($line['account_id'] ?? 0);
            if (! $account) {
                return ['error' => 'Akun tujuan tidak ditemukan (ID: '.($line['account_id'] ?? 'null').').'];
            }

            $lines[] = [
                'account' => $account,
                'amount' => $line['amount'],
                'description' => $line['description'] ?? null,
                'profit_center' => $line['profit_center'] ?? null,
            ];
        }

        return ['lines' => $lines];
    }

    /**
     * @param  array<string, mixed>|null  $sapResponse
     */
    protected function logSubmission(
        User $user,
        string $status,
        ?string $errorMessage = null,
        ?array $sapResponse = null,
        ?int $amount = null,
        ?string $docNum = null,
        ?int $docEntry = null,
    ): void {
        SapSubmissionLog::create([
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_GENERAL_OUTGOING_PAYMENT,
            'status' => $status,
            'action' => 'submission',
            'error_message' => $errorMessage,
            'sap_error' => $errorMessage,
            'sap_response' => $sapResponse,
            'sap_doc_num' => $docNum,
            'sap_doc_entry' => $docEntry,
            'amount' => $amount,
            'attempt_number' => 1,
            'submitted_by' => $user->id,
            'user_id' => $user->id,
        ]);
    }
}
