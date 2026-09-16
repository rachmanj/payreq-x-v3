<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bilyet;
use App\Models\GeneralOutgoingPayment;
use App\Models\Giro;
use Carbon\Carbon;

/**
 * Outgoing Payment Umum (pinbuk bank → cash).
 *
 * Sisi bank memakai TransferAccount/TransferSum (bukan PaymentChecks).
 * Integrasi cheque register SAP (PaymentChecks) belum didukung — ditolak SL (ODBC -2028).
 * Nomor bilyet dicatat di TransferReference dan remarks.
 */
class SapGeneralOutgoingPaymentBuilder
{
    public const AMOUNT_TOLERANCE = 0.5;

    public const SYSTEM_DEFAULT_PROFIT_CENTER = '30';

    /**
     * @param  list<array{account: Account, amount: float|int, description?: string|null, profit_center?: string|null}>  $destinationAccounts
     */
    public function __construct(
        protected Giro $giro,
        protected Bilyet $bilyet,
        protected float $amount,
        protected string $docDate,
        protected string $project,
        protected ?string $remarks,
        protected array $destinationAccounts,
        protected ?string $preparedBy = null,
        protected ?string $approvedBy = null,
        protected ?string $defaultProfitCenter = null,
        protected string $systemDefaultProfitCenter = self::SYSTEM_DEFAULT_PROFIT_CENTER,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $docDate = Carbon::parse($this->docDate)->format('Y-m-d');
        $firstAccount = $this->destinationAccounts[0]['account'];
        $remarks = $this->buildRemarksWithBilyet();
        $journalRemarks = $this->buildJournalRemarks();
        $transferReference = $this->buildTransferReference();

        $paymentAccounts = [];
        foreach ($this->destinationAccounts as $line) {
            $account = $line['account'];
            $lineDescription = mb_substr(trim((string) ($line['description'] ?? $remarks)), 0, 254);
            $profitCenter = $this->resolveLineProfitCenter($line);

            $paymentAccounts[] = [
                'AccountCode' => (string) $account->sap_account,
                'SumPaid' => (float) $line['amount'],
                'Decription' => $lineDescription,
                'ProjectCode' => $this->project,
                'ProfitCenter' => $profitCenter,
                'U_MIS_CCDepartment' => $profitCenter,
            ];
        }

        return [
            'DocType' => 'rAccount',
            'CardCode' => (string) $firstAccount->sap_account,
            'DocDate' => $docDate,
            'DocCurrency' => 'IDR',
            'ProjectCode' => $this->project,
            'Remarks' => $remarks,
            'PaymentAccounts' => $paymentAccounts,
            'TransferAccount' => (string) $this->giro->sap_account,
            'TransferSum' => $this->amount,
            'TransferDate' => $docDate,
            'TransferReference' => $transferReference,
            'JournalRemarks' => $journalRemarks,
            'U_MIS_Signature1' => $this->trimmedSignature($this->preparedBy),
            'U_MIS_Signature2' => $this->trimmedSignature($this->approvedBy),
        ];
    }

    /**
     * @return list<string>
     */
    public function validate(): array
    {
        $errors = [];

        if (empty(trim((string) ($this->giro->sap_account ?? '')))) {
            $errors[] = 'Giro bank belum memiliki mapping SAP (sap_account).';
        }

        if ($this->bilyet->giro_id !== $this->giro->id) {
            $errors[] = 'Bilyet tidak termasuk dalam giro yang dipilih.';
        }

        if ($this->bilyet->status !== 'onhand') {
            $errors[] = "Bilyet harus berstatus onhand (status saat ini: {$this->bilyet->status}).";
        }

        if (GeneralOutgoingPayment::query()
            ->where('bilyet_id', $this->bilyet->id)
            ->where('status', GeneralOutgoingPayment::STATUS_SUCCESS)
            ->exists()) {
            $errors[] = 'Bilyet sudah digunakan pada Outgoing Payment lain.';
        }

        if ($this->destinationAccounts === []) {
            $errors[] = 'Daftar akun tujuan tidak boleh kosong.';
        }

        $seenAccountIds = [];
        $totalDestination = 0.0;

        foreach ($this->destinationAccounts as $line) {
            $account = $line['account'];
            $lineAmount = (float) ($line['amount'] ?? 0);

            if ($account->type !== 'cash') {
                $errors[] = "Akun '{$account->account_name}' harus bertipe cash.";
            }

            if (empty(trim((string) ($account->sap_account ?? '')))) {
                $errors[] = "Akun '{$account->account_name}' belum memiliki mapping SAP (sap_account).";
            }

            if ($lineAmount <= 0) {
                $errors[] = "Nominal akun '{$account->account_name}' harus lebih dari nol.";
            }

            if (in_array($account->id, $seenAccountIds, true)) {
                $errors[] = "Akun tujuan '{$account->account_name}' tidak boleh duplikat.";
            }

            if ($this->resolveLineProfitCenter($line) === '') {
                $errors[] = "Profit Center wajib diisi untuk akun '{$account->account_name}'.";
            }

            $seenAccountIds[] = $account->id;
            $totalDestination += $lineAmount;
        }

        if (abs($totalDestination - $this->amount) > self::AMOUNT_TOLERANCE) {
            $errors[] = 'Total nominal akun tujuan ('.number_format($totalDestination, 0, ',', '.')
                .') harus sama dengan nominal OP ('.number_format($this->amount, 0, ',', '.').').';
        }

        if ($this->amount <= 0) {
            $errors[] = 'Nominal OP harus lebih dari nol.';
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreviewData(): array
    {
        return [
            'giro' => [
                'id' => $this->giro->id,
                'acc_no' => $this->giro->acc_no,
                'acc_name' => $this->giro->acc_name,
                'sap_account' => $this->giro->sap_account,
            ],
            'bilyet' => [
                'id' => $this->bilyet->id,
                'prefix' => $this->bilyet->prefix,
                'nomor' => $this->bilyet->nomor,
                'full_nomor' => $this->bilyet->full_nomor,
                'status' => $this->bilyet->status,
            ],
            'amount' => $this->amount,
            'doc_date' => Carbon::parse($this->docDate)->format('Y-m-d'),
            'project' => $this->project,
            'remarks' => $this->remarks,
            'destination_accounts' => array_map(fn (array $line) => [
                'account_id' => $line['account']->id,
                'account_name' => $line['account']->account_name,
                'sap_account' => $line['account']->sap_account,
                'amount' => (float) $line['amount'],
                'description' => $line['description'] ?? null,
                'profit_center' => $this->resolveLineProfitCenter($line),
            ], $this->destinationAccounts),
            'prepared_by' => $this->trimmedSignature($this->preparedBy),
            'approved_by' => $this->trimmedSignature($this->approvedBy),
        ];
    }

    /**
     * @param  array{account: Account, amount: float|int, description?: string|null, profit_center?: string|null}  $line
     */
    protected function resolveLineProfitCenter(array $line): string
    {
        $lineProfitCenter = trim((string) ($line['profit_center'] ?? ''));
        if ($lineProfitCenter !== '') {
            return $lineProfitCenter;
        }

        $defaultProfitCenter = trim((string) ($this->defaultProfitCenter ?? ''));
        if ($defaultProfitCenter !== '') {
            return $defaultProfitCenter;
        }

        return trim($this->systemDefaultProfitCenter);
    }

    protected function buildTransferReference(): string
    {
        return mb_substr(trim($this->bilyet->prefix.' '.$this->bilyet->nomor), 0, 254);
    }

    protected function buildRemarksWithBilyet(): string
    {
        $base = trim((string) ($this->remarks ?? ''));
        $bilyetLabel = trim($this->bilyet->prefix.' '.$this->bilyet->nomor);
        $suffix = 'Bilyet '.$bilyetLabel;

        if ($base === '') {
            return mb_substr($suffix, 0, 254);
        }

        if (str_contains($base, $bilyetLabel)) {
            return mb_substr($base, 0, 254);
        }

        return mb_substr($base.' - '.$suffix, 0, 254);
    }

    protected function buildJournalRemarks(): string
    {
        $bilyetLabel = trim($this->bilyet->prefix.' '.$this->bilyet->nomor);
        $base = trim((string) ($this->remarks ?? ''));
        $journal = $base !== '' ? $base : 'OP Umum Bilyet '.$bilyetLabel;

        return mb_substr($journal, 0, 254);
    }

    protected function trimmedSignature(?string $value): string
    {
        return mb_substr(trim((string) $value), 0, 100);
    }
}
