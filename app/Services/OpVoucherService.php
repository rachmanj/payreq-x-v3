<?php

namespace App\Services;

use App\Http\Controllers\ToolController;
use App\Models\Project;
use App\Models\SapSubmissionLog;
use Carbon\Carbon;

class OpVoucherService
{
    /** @var array<string, string>|null */
    protected ?array $sapProjectNames = null;

    public function __construct(protected SapService $sapService) {}

    /**
     * @return array{
     *     header: array<string, mixed>,
     *     lines: array<int, array{account: string, description: string, debit: float, credit: float}>,
     *     totals: array{debit: float, credit: float},
     *     say: string,
     *     signatures: array<string, string|null>
     * }
     */
    public function build(SapSubmissionLog $opLog): array
    {
        $docEntry = trim((string) ($opLog->sap_doc_entry ?? ''));
        if ($docEntry === '') {
            throw new \RuntimeException('Log pembayaran tidak memiliki sap_doc_entry.');
        }

        $payment = $this->sapService->getVendorPaymentByDocEntry($docEntry);
        if ($payment === null || empty($payment['DocEntry'])) {
            throw new \RuntimeException('Header Outgoing Payment tidak ditemukan di SAP (DocEntry: '.$docEntry.').');
        }

        $paymentHeaderSql = $this->sapService->getPaymentHeaderFromSql($docEntry);

        $lines = $this->sapService->getPaymentGlLines($docEntry);
        if ($lines === []) {
            throw new \RuntimeException('Baris akun GL untuk Outgoing Payment tidak ditemukan di SAP (DocEntry: '.$docEntry.').');
        }

        $totalDebit = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));
        $paymentMethod = $this->resolvePaymentMethod($payment);
        $bankAccount = $this->resolveBankAccount($payment, $paymentMethod);

        $header = [
            'payment_for' => trim((string) ($payment['CardName'] ?? '')),
            'voucher_no' => (string) ($payment['DocNum'] ?? ''),
            'voucher_date' => $this->formatVoucherDate($payment['DocDate'] ?? null),
            'project' => $this->resolveProjectLabelFromCode($paymentHeaderSql['prj_code']),
            'payment_method' => $paymentMethod,
            'currency' => strtoupper(trim((string) ($payment['DocCurrency'] ?? 'IDR'))) ?: 'IDR',
            'bank_acc_no' => $bankAccount,
            'check_bg_no' => trim((string) ($payment['CheckBgNo'] ?? '')),
            'remarks' => trim((string) ($payment['JournalRemarks'] ?? '')),
        ];

        if ($header['payment_for'] === '' || $header['voucher_no'] === '') {
            throw new \RuntimeException('Data header Outgoing Payment tidak lengkap di SAP.');
        }

        return [
            'header' => $header,
            'lines' => $lines,
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
            ],
            'say' => ucfirst(app(ToolController::class)->terbilang((int) round($totalDebit))),
            'signatures' => [
                'reviewed_by_name' => 'Rachman J',
                'reviewed_by_signature' => 'sign_rj2.png',
                'checked_by_signature' => 'sign_checked.png',
                'paid_by_name' => $opLog->submittedBy?->name,
                'paid_by_signature' => 'sign_paid.png',
                'checked_by_name' => null,
                'received_by_name' => null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    protected function resolvePaymentMethod(array $payment): string
    {
        $cashSum = (float) ($payment['CashSum'] ?? 0);
        $transferSum = (float) ($payment['TransferSum'] ?? 0);

        if ($cashSum > 0) {
            return 'CASH';
        }

        if ($transferSum > 0) {
            return 'TRANSFER';
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    protected function resolveBankAccount(array $payment, string $paymentMethod): string
    {
        if ($paymentMethod === 'CASH') {
            return trim((string) ($payment['CashAccount'] ?? ''));
        }

        if ($paymentMethod === 'TRANSFER') {
            return trim((string) ($payment['TransferAccount'] ?? ''));
        }

        return trim((string) ($payment['TransferAccount'] ?? $payment['CashAccount'] ?? ''));
    }

    protected function resolveProjectLabelFromCode(string $code): string
    {
        $code = trim($code);
        if ($code === '') {
            return '';
        }

        $localProject = Project::query()
            ->where('sap_code', $code)
            ->orWhere('code', $code)
            ->first();

        if ($localProject !== null) {
            return trim($code.' - '.$localProject->name);
        }

        $sapName = $this->lookupSapProjectName($code);
        if ($sapName !== null && $sapName !== '') {
            return trim($code.' - '.$sapName);
        }

        return $code;
    }

    protected function lookupSapProjectName(string $code): ?string
    {
        if ($this->sapProjectNames === null) {
            $this->sapProjectNames = [];

            try {
                foreach ($this->sapService->getProjects() as $project) {
                    if (! is_array($project)) {
                        continue;
                    }

                    $projectCode = trim((string) ($project['ProjectCode'] ?? $project['Code'] ?? ''));
                    $projectName = trim((string) ($project['ProjectName'] ?? $project['Name'] ?? ''));
                    if ($projectCode !== '' && $projectName !== '') {
                        $this->sapProjectNames[$projectCode] = $projectName;
                    }
                }
            } catch (\Throwable) {
                $this->sapProjectNames = [];
            }
        }

        return $this->sapProjectNames[$code] ?? null;
    }

    protected function formatVoucherDate(mixed $docDate): string
    {
        if ($docDate === null || trim((string) $docDate) === '') {
            return '';
        }

        try {
            return Carbon::parse($docDate)->format('d-F-Y');
        } catch (\Throwable) {
            return (string) $docDate;
        }
    }
}
