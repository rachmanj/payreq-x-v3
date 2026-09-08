<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\SapSubmissionLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstallmentSapSubmissionService
{
    public function __construct(protected SapService $sapService) {}

    /**
     * @return array{success: bool, message: string, doc_num?: string}
     */
    public function submitAp(int $installmentId, User $user): array
    {
        $installment = Installment::with(['loan.creditor.sapBusinessPartner'])->find($installmentId);

        if (! $installment) {
            return [
                'success' => false,
                'message' => 'Angsuran tidak ditemukan.',
            ];
        }

        if ($installment->hasSapAp()) {
            return [
                'success' => false,
                'message' => 'AP Invoice sudah dibuat (DocNum: '.$installment->sap_ap_doc_num.'). Tidak dapat submit ulang.',
            ];
        }

        if ($installment->isPaid()) {
            return [
                'success' => false,
                'message' => 'Angsuran sudah lunas. Tidak dapat membuat AP Invoice.',
            ];
        }

        $builder = new SapInstallmentApInvoiceBuilder($installment);
        $validationErrors = $builder->validate();

        if ($validationErrors !== []) {
            return [
                'success' => false,
                'message' => implode(' ', $validationErrors),
            ];
        }

        $numAtCard = $builder->buildReference();
        $recovered = $this->recoverExistingApFromSap($installment, $numAtCard, $user);

        if ($recovered !== null) {
            return $recovered;
        }

        $payload = $builder->build();

        try {
            $sapResult = $this->sapService->createApInvoice($payload);

            if (! ($sapResult['success'] ?? false)) {
                $message = $sapResult['message'] ?? 'Gagal membuat AP Invoice di SAP B1.';
                $this->logSubmission($installment, $user, 'failed', $message);

                return [
                    'success' => false,
                    'message' => $message,
                ];
            }

            $installment->update([
                'sap_ap_doc_num' => $sapResult['doc_num'] ?? null,
                'sap_ap_doc_entry' => $sapResult['doc_entry'] ?? null,
                'sap_sync_status' => 'ap_created',
                'sap_error_message' => null,
            ]);

            $this->logSubmission(
                $installment,
                $user,
                'success',
                null,
                $sapResult['data'] ?? $sapResult,
                $sapResult['doc_num'] ?? null,
                $sapResult['doc_entry'] ?? null,
            );

            return [
                'success' => true,
                'message' => 'AP Invoice berhasil dibuat di SAP B1.',
                'doc_num' => (string) ($sapResult['doc_num'] ?? ''),
            ];
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
            $this->logSubmission($installment, $user, 'failed', $message);

            $installment->update([
                'sap_error_message' => $message,
            ]);

            Log::error('Installment AP submission failed', [
                'installment_id' => $installmentId,
                'error' => $message,
            ]);

            if ($this->looksLikePostSuccessFailure($message)) {
                return [
                    'success' => false,
                    'message' => 'Respon SAP tidak lengkap setelah submit. Silakan cek-balik di SAP (NumAtCard: '.$numAtCard.') lalu coba lagi — sistem akan memulihkan dokumen jika sudah ada.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal submit AP ke SAP: '.$message,
            ];
        }
    }

    /**
     * @param  list<int>  $installmentIds
     * @return array{results: list<array{id: int, angsuran_ke: int|null, success: bool, message: string, doc_num?: string}>}
     */
    public function submitApBulk(array $installmentIds, User $user): array
    {
        $results = [];

        foreach ($installmentIds as $installmentId) {
            $installment = Installment::query()->find($installmentId);
            $result = $this->submitAp((int) $installmentId, $user);

            $results[] = [
                'id' => (int) $installmentId,
                'angsuran_ke' => $installment?->angsuran_ke,
                'success' => $result['success'],
                'message' => $result['message'],
                'doc_num' => $result['doc_num'] ?? null,
            ];
        }

        return ['results' => $results];
    }

    /**
     * @return array{success: bool, message: string, doc_num?: string}|null
     */
    protected function recoverExistingApFromSap(Installment $installment, string $numAtCard, User $user): ?array
    {
        try {
            $document = $this->sapService->getPurchaseInvoiceByNumAtCard($numAtCard);

            if (! $document) {
                return null;
            }

            if (strtoupper((string) ($document['Cancelled'] ?? 'N')) === 'Y'
                || strtoupper((string) ($document['Cancelled'] ?? 'N')) === 'TYES') {
                return null;
            }

            $docNum = $document['DocNum'] ?? null;
            $docEntry = $document['DocEntry'] ?? null;

            if ($docNum === null || $docEntry === null) {
                return null;
            }

            $installment->update([
                'sap_ap_doc_num' => $docNum,
                'sap_ap_doc_entry' => $docEntry,
                'sap_sync_status' => 'ap_created',
                'sap_error_message' => null,
            ]);

            $this->logSubmission(
                $installment,
                $user,
                'success',
                'Recovered from SAP by NumAtCard',
                $document,
                (string) $docNum,
                (int) $docEntry,
            );

            return [
                'success' => true,
                'message' => 'AP Invoice sudah pernah dibuat (doc '.$docNum.') — dipulihkan.',
                'doc_num' => (string) $docNum,
            ];
        } catch (Throwable $exception) {
            Log::warning('Installment AP recovery lookup failed', [
                'installment_id' => $installment->id,
                'num_at_card' => $numAtCard,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function looksLikePostSuccessFailure(string $message): bool
    {
        $needles = ['timeout', 'timed out', 'connection', 'empty response', 'could not resolve'];

        foreach ($needles as $needle) {
            if (stripos($message, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $sapResponse
     */
    protected function logSubmission(
        Installment $installment,
        User $user,
        string $status,
        ?string $errorMessage = null,
        ?array $sapResponse = null,
        ?string $docNum = null,
        ?int $docEntry = null,
    ): void {
        SapSubmissionLog::create([
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_AP_INVOICE_INSTALLMENT,
            'status' => $status,
            'action' => 'submission',
            'error_message' => $errorMessage,
            'sap_error' => $errorMessage,
            'sap_response' => $sapResponse,
            'sap_doc_num' => $docNum,
            'sap_doc_entry' => $docEntry,
            'amount' => (float) ($installment->bilyet_amount ?? 0),
            'attempt_number' => 1,
            'submitted_by' => $user->id,
            'user_id' => $user->id,
        ]);
    }
}
