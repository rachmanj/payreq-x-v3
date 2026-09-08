<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Installment;
use App\Models\SapBusinessPartner;
use App\Models\SapSubmissionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstallmentOpService
{
    public function __construct(protected SapService $sapService) {}

    /**
     * @param  array{payment_date: string, prepared_by?: string|null, approved_by?: string|null, payment_means?: string|null, confirm_bilyet_cair?: bool, confirm_auto_debit?: bool}  $opData
     * @return array{success: bool, message: string, doc_num?: string}
     */
    public function createOp(int $installmentId, array $opData, User $user): array
    {
        $installment = Installment::with(['loan.creditor.sapBusinessPartner', 'bilyet', 'account', 'loan.account'])
            ->find($installmentId);

        if (! $installment) {
            return [
                'success' => false,
                'message' => 'Angsuran tidak ditemukan.',
            ];
        }

        if (empty($installment->sap_ap_doc_num) || empty($installment->sap_ap_doc_entry)) {
            return [
                'success' => false,
                'message' => 'AP Invoice SAP belum dibuat. Buat AP terlebih dahulu sebelum Outgoing Payment.',
            ];
        }

        if ($installment->hasSapPayment()) {
            return [
                'success' => false,
                'message' => 'Outgoing Payment sudah dibuat (DocNum: '.$installment->sap_payment_doc_num.'). Tidak dapat membuat ulang.',
            ];
        }

        if ($installment->isPaid()) {
            return [
                'success' => false,
                'message' => 'Angsuran sudah lunas.',
            ];
        }

        $paymentMethodError = $this->validatePaymentMethodReadiness($installment, $opData);
        if ($paymentMethodError !== null) {
            return [
                'success' => false,
                'message' => $paymentMethodError,
            ];
        }

        $account = $this->resolveBankAccount($installment);
        if (! $account) {
            return [
                'success' => false,
                'message' => 'Akun bank debit wajib diisi (account_id di angsuran atau kontrak).',
            ];
        }

        if (empty($account->sap_account)) {
            return [
                'success' => false,
                'message' => "Akun '{$account->account_name}' belum memiliki mapping SAP (sap_account).",
            ];
        }

        $partner = $this->resolveVendorPartner($installment);
        if (! $partner) {
            return [
                'success' => false,
                'message' => 'Vendor SAP kreditur tidak ditemukan atau belum di-mapping.',
            ];
        }

        try {
            $apInvoice = $this->sapService->getPurchaseInvoiceByDocEntry($installment->sap_ap_doc_entry);
            if (! $apInvoice) {
                return [
                    'success' => false,
                    'message' => 'AP Invoice tidak ditemukan di SAP (DocEntry: '.$installment->sap_ap_doc_entry.').',
                ];
            }
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Gagal mengambil data AP dari SAP: '.$exception->getMessage(),
            ];
        }

        $invoicePayload = [
            'invoice_number' => (string) $installment->sap_ap_doc_num,
            'amount' => (float) $installment->bilyet_amount,
            'payment_date' => $opData['payment_date'],
            'remarks' => 'Installment #'.$installment->angsuran_ke,
        ];

        $builder = new SapVendorPaymentBuilder(
            $invoicePayload,
            $apInvoice,
            $partner,
            $account,
            (string) ($opData['payment_means'] ?? SapVendorPaymentBuilder::MEANS_TRANSFER),
            (string) $opData['payment_date'],
            (float) $installment->bilyet_amount,
            (string) ($opData['prepared_by'] ?? $user->name),
            (string) ($opData['approved_by'] ?? $user->name),
        );

        $errors = $builder->validate(requirePaymentAccount: true);
        if ($errors !== []) {
            return [
                'success' => false,
                'message' => implode(' ', $errors),
            ];
        }

        $payload = $builder->build();
        $paymentDate = Carbon::parse($opData['payment_date']);

        try {
            $sapResult = $this->sapService->createOutgoingPayment($payload);

            if (! ($sapResult['success'] ?? false)) {
                $message = $sapResult['message'] ?? 'Gagal membuat Outgoing Payment di SAP B1.';
                $this->logOpSubmission($installment, $user, 'failed', $message, null, (float) $installment->bilyet_amount);

                return [
                    'success' => false,
                    'message' => $message,
                ];
            }

            $installment->update([
                'sap_payment_doc_num' => $sapResult['doc_num'] ?? null,
                'sap_payment_doc_entry' => $sapResult['doc_entry'] ?? null,
                'sap_sync_status' => 'payment_created',
                'sap_error_message' => null,
                'status' => 'paid',
                'paid_date' => $paymentDate->format('Y-m-d'),
            ]);

            if ($installment->bilyet) {
                $installment->bilyet->update([
                    'status' => 'cair',
                    'cair_date' => $installment->bilyet->cair_date ?? $paymentDate->format('Y-m-d'),
                ]);
            }

            $this->logOpSubmission(
                $installment,
                $user,
                'success',
                null,
                $sapResult['data'] ?? $sapResult,
                (float) $installment->bilyet_amount,
                (string) ($sapResult['doc_num'] ?? ''),
                isset($sapResult['doc_entry']) ? (int) $sapResult['doc_entry'] : null,
            );

            return [
                'success' => true,
                'message' => 'Outgoing Payment berhasil dibuat di SAP B1.',
                'doc_num' => (string) ($sapResult['doc_num'] ?? ''),
            ];
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
            $this->logOpSubmission($installment, $user, 'failed', $message, null, (float) $installment->bilyet_amount);

            $installment->update([
                'sap_error_message' => $message,
            ]);

            Log::error('Installment OP submission failed', [
                'installment_id' => $installmentId,
                'error' => $message,
            ]);

            return [
                'success' => false,
                'message' => 'Gagal membuat Outgoing Payment: '.$message,
            ];
        }
    }

    /**
     * @param  list<int>  $installmentIds
     * @return array{results: list<array{id: int, angsuran_ke: int|null, success: bool, message: string, synced: bool}>}
     */
    public function syncPaidFromSap(array $installmentIds): array
    {
        $results = [];

        foreach ($installmentIds as $installmentId) {
            $installment = Installment::query()->find($installmentId);

            if (! $installment) {
                $results[] = [
                    'id' => (int) $installmentId,
                    'angsuran_ke' => null,
                    'success' => false,
                    'message' => 'Angsuran tidak ditemukan.',
                    'synced' => false,
                ];

                continue;
            }

            if (empty($installment->sap_ap_doc_entry)) {
                $results[] = [
                    'id' => $installment->id,
                    'angsuran_ke' => $installment->angsuran_ke,
                    'success' => false,
                    'message' => 'AP Invoice SAP belum ada.',
                    'synced' => false,
                ];

                continue;
            }

            if ($installment->isPaid() && $installment->sap_sync_status === 'completed') {
                $results[] = [
                    'id' => $installment->id,
                    'angsuran_ke' => $installment->angsuran_ke,
                    'success' => true,
                    'message' => 'Sudah tercatat lunas.',
                    'synced' => false,
                ];

                continue;
            }

            try {
                $apInvoice = $this->sapService->getPurchaseInvoiceByDocEntry($installment->sap_ap_doc_entry);

                if (! $apInvoice) {
                    $results[] = [
                        'id' => $installment->id,
                        'angsuran_ke' => $installment->angsuran_ke,
                        'success' => false,
                        'message' => 'AP tidak ditemukan di SAP.',
                        'synced' => false,
                    ];

                    continue;
                }

                if (! $this->isApFullyPaidInSap($apInvoice)) {
                    $results[] = [
                        'id' => $installment->id,
                        'angsuran_ke' => $installment->angsuran_ke,
                        'success' => true,
                        'message' => 'AP di SAP masih terbuka / belum lunas.',
                        'synced' => false,
                    ];

                    continue;
                }

                $paidDate = $this->resolvePaidDateFromAp($apInvoice);

                $installment->update([
                    'status' => 'paid',
                    'paid_date' => $paidDate,
                    'sap_sync_status' => 'completed',
                    'sap_error_message' => null,
                ]);

                SapSubmissionLog::create([
                    'document_type' => SapSubmissionLog::DOCUMENT_TYPE_AP_OUTGOING_INSTALLMENT,
                    'status' => 'success',
                    'action' => 'sync',
                    'sap_doc_num' => $installment->sap_payment_doc_num ?? ($apInvoice['DocNum'] ?? null),
                    'sap_doc_entry' => $installment->sap_payment_doc_entry ?? ($apInvoice['DocEntry'] ?? null),
                    'sap_response' => $apInvoice,
                    'amount' => (float) ($installment->bilyet_amount ?? 0),
                    'attempt_number' => 1,
                    'submitted_by' => auth()->id(),
                    'user_id' => auth()->id(),
                ]);

                $results[] = [
                    'id' => $installment->id,
                    'angsuran_ke' => $installment->angsuran_ke,
                    'success' => true,
                    'message' => 'Status lunas disinkronkan dari SAP.',
                    'synced' => true,
                ];
            } catch (Throwable $exception) {
                $results[] = [
                    'id' => $installment->id,
                    'angsuran_ke' => $installment->angsuran_ke,
                    'success' => false,
                    'message' => 'Gagal sync: '.$exception->getMessage(),
                    'synced' => false,
                ];
            }
        }

        return ['results' => $results];
    }

    /**
     * @param  array{confirm_bilyet_cair?: bool, confirm_auto_debit?: bool}  $opData
     */
    protected function validatePaymentMethodReadiness(Installment $installment, array $opData): ?string
    {
        if ($installment->payment_method === 'bilyet') {
            if (! $installment->bilyet_id && empty($installment->bilyet_no)) {
                return 'Angsuran metode bilyet belum memiliki bilyet terkait.';
            }

            $bilyet = $installment->bilyet;
            if ($bilyet && $bilyet->status !== 'cair') {
                if (! ($opData['confirm_bilyet_cair'] ?? false)) {
                    return 'Bilyet harus berstatus cair sebelum membuat Outgoing Payment.';
                }
            }

            return null;
        }

        if ($installment->payment_method === 'auto_debit') {
            if (! ($opData['confirm_auto_debit'] ?? false) && ! $installment->paid_date) {
                return 'Autodebet harus dikonfirmasi terlebih dahulu (confirm_auto_debit).';
            }

            return null;
        }

        return 'Outgoing Payment hanya didukung untuk metode bilyet atau autodebet.';
    }

    protected function resolveBankAccount(Installment $installment): ?Account
    {
        if ($installment->account_id) {
            return Account::query()->find($installment->account_id);
        }

        if ($installment->loan?->account_id) {
            return Account::query()->find($installment->loan->account_id);
        }

        return null;
    }

    protected function resolveVendorPartner(Installment $installment): ?SapBusinessPartner
    {
        $creditor = $installment->loan?->creditor;

        if (! $creditor) {
            return null;
        }

        if ($creditor->sapBusinessPartner) {
            return $creditor->sapBusinessPartner;
        }

        $code = trim((string) ($creditor->sap_code ?? ''));

        if ($code === '') {
            return null;
        }

        return SapBusinessPartner::query()
            ->suppliers()
            ->where('code', $code)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $apInvoice
     */
    protected function isApFullyPaidInSap(array $apInvoice): bool
    {
        $status = (string) ($apInvoice['DocumentStatus'] ?? '');
        if ($status === 'bost_Close') {
            return true;
        }

        $docTotal = (float) ($apInvoice['DocTotal'] ?? 0);
        $paidToDate = (float) ($apInvoice['PaidToDate'] ?? 0);

        if ($docTotal > 0 && $paidToDate >= $docTotal - SapVendorPaymentBuilder::AMOUNT_TOLERANCE) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $apInvoice
     */
    protected function resolvePaidDateFromAp(array $apInvoice): string
    {
        foreach (['DocDate', 'TaxDate', 'DocDueDate'] as $field) {
            if (! empty($apInvoice[$field])) {
                return Carbon::parse($apInvoice[$field])->format('Y-m-d');
            }
        }

        return Carbon::today()->format('Y-m-d');
    }

    /**
     * @param  array<string, mixed>|null  $sapResponse
     */
    protected function logOpSubmission(
        Installment $installment,
        User $user,
        string $status,
        ?string $errorMessage = null,
        ?array $sapResponse = null,
        ?float $amount = null,
        ?string $docNum = null,
        ?int $docEntry = null,
    ): void {
        SapSubmissionLog::create([
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_AP_OUTGOING_INSTALLMENT,
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
