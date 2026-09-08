<?php

namespace App\Services;

use App\Models\Installment;
use Carbon\Carbon;

class SapInstallmentApInvoiceBuilder
{
    private const DEFAULT_PRINCIPAL_ACCOUNT = '22201001';

    private const INTEREST_ACCOUNT = '71201004';

    private const AMOUNT_TOLERANCE = 1.00;

    public function __construct(protected Installment $installment) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $loan = $this->installment->loan;
        $creditor = $loan->creditor;
        $dueDate = Carbon::parse($this->installment->due_date);
        $reference = $this->buildReference();

        return [
            'CardCode' => $this->cardCode(),
            'DocType' => 'dDocument_Service',
            'DocDate' => $dueDate->format('Y-m-d'),
            'DocDueDate' => $dueDate->format('Y-m-d'),
            'TaxDate' => $dueDate->format('Y-m-d'),
            'DocCurrency' => 'IDR',
            'DocRate' => 1.0,
            'NumAtCard' => $reference,
            'Comments' => $reference,
            'DocumentLines' => $this->buildDocumentLines(),
        ];
    }

    /**
     * @return list<string>
     */
    public function validate(): array
    {
        $errors = [];

        if (! $this->installment->loan_id) {
            $errors[] = 'Angsuran harus terhubung ke kontrak pinjaman.';
        }

        $loan = $this->installment->loan;
        if (! $loan) {
            $errors[] = 'Kontrak pinjaman tidak ditemukan.';

            return $errors;
        }

        if (! $loan->creditor_id || ! $loan->creditor) {
            $errors[] = 'Kontrak harus memiliki kreditur/leasing.';
        } elseif ($this->cardCode() === '') {
            $errors[] = 'Kreditur belum memiliki CardCode SAP (sap_business_partner_id).';
        }

        if (! $this->installment->due_date) {
            $errors[] = 'Tanggal jatuh tempo angsuran wajib diisi.';
        }

        if ($this->installment->principal_amount === null) {
            $errors[] = 'Pokok angsuran belum diisi. Isi split pokok/bunga terlebih dahulu (import/manual).';
        }

        if ($this->installment->interest_amount === null) {
            $errors[] = 'Bunga angsuran belum diisi. Isi split pokok/bunga terlebih dahulu (import/manual).';
        }

        if ($this->installment->principal_amount !== null && $this->installment->interest_amount !== null) {
            $splitTotal = (float) $this->installment->principal_amount + (float) $this->installment->interest_amount;
            $bilyetAmount = (float) ($this->installment->bilyet_amount ?? 0);

            if (abs($splitTotal - $bilyetAmount) > self::AMOUNT_TOLERANCE) {
                $errors[] = sprintf(
                    'Total pokok (Rp %s) + bunga (Rp %s) = Rp %s tidak sama dengan nominal angsuran (Rp %s). Selisih melebihi Rp 1.',
                    number_format((float) $this->installment->principal_amount, 2, ',', '.'),
                    number_format((float) $this->installment->interest_amount, 2, ',', '.'),
                    number_format($splitTotal, 2, ',', '.'),
                    number_format($bilyetAmount, 2, ',', '.'),
                );
            }
        }

        if (empty($this->installment->bilyet_amount) || (float) $this->installment->bilyet_amount <= 0) {
            $errors[] = 'Nominal angsuran harus lebih dari nol.';
        }

        if (trim($this->shortName()) === '') {
            $errors[] = 'Nama singkat kreditur (nama_singkat) wajib diisi untuk referensi AP.';
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreviewData(): array
    {
        $loan = $this->installment->loan;
        $creditor = $loan->creditor;
        $dueDate = Carbon::parse($this->installment->due_date);
        $principal = (float) ($this->installment->principal_amount ?? 0);
        $interest = (float) ($this->installment->interest_amount ?? 0);

        return [
            'installment_id' => $this->installment->id,
            'angsuran_ke' => $this->installment->angsuran_ke,
            'loan_code' => $loan->loan_code,
            'tenor' => $loan->tenor,
            'vendor' => [
                'code' => $this->cardCode(),
                'name' => $creditor->name,
                'short_name' => $this->shortName(),
            ],
            'dates' => [
                'doc_date' => $dueDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'tax_date' => $dueDate->format('Y-m-d'),
            ],
            'principal' => $principal,
            'interest' => $interest,
            'total' => $principal + $interest,
            'bilyet_amount' => (float) ($this->installment->bilyet_amount ?? 0),
            'reference' => $this->buildReference(),
            'principal_account' => $this->principalAccountCode(),
            'interest_account' => self::INTEREST_ACCOUNT,
            'costing_code' => $loan->costing_code ?? '60',
            'project_code' => $loan->project_code,
            'lines' => [
                [
                    'type' => 'principal',
                    'description' => $this->lineDescription('Principal'),
                    'amount' => $principal,
                ],
                [
                    'type' => 'interest',
                    'description' => $this->lineDescription('Interest'),
                    'amount' => $interest,
                ],
            ],
        ];
    }

    public function buildReference(): string
    {
        $loan = $this->installment->loan;

        $parts = array_filter([
            $this->installment->angsuran_ke.' of '.$loan->tenor,
            $this->shortName(),
            $loan->kode_unit,
            '('.$loan->loan_code.')',
        ], fn ($part) => $part !== null && trim((string) $part) !== '');

        return implode(' ', $parts);
    }

    public static function ordinal(int $number): string
    {
        $suffix = match ($number % 100) {
            11, 12, 13 => 'th',
            default => match ($number % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            },
        };

        return $number.$suffix;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildDocumentLines(): array
    {
        $loan = $this->installment->loan;
        $costingCode = $loan->costing_code ?? '60';
        $projectCode = $loan->project_code;

        $principalLine = $this->buildGlLine(
            $this->principalAccountCode(),
            $this->lineDescription('Principal'),
            (float) $this->installment->principal_amount,
            $costingCode,
            $projectCode,
        );

        $interestLine = $this->buildGlLine(
            self::INTEREST_ACCOUNT,
            $this->lineDescription('Interest'),
            (float) $this->installment->interest_amount,
            $costingCode,
            $projectCode,
        );

        return [$principalLine, $interestLine];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildGlLine(
        string $accountCode,
        string $description,
        float $amount,
        string $costingCode,
        ?string $projectCode,
    ): array {
        $line = [
            'AccountCode' => $accountCode,
            'ItemDescription' => $description,
            'Quantity' => 1,
            'UnitPrice' => $amount,
            'LineTotal' => $amount,
            'CostingCode' => $costingCode,
            'UseBaseUnits' => 'N',
        ];

        if ($projectCode !== null && trim($projectCode) !== '') {
            $line['ProjectCode'] = $projectCode;
        }

        return $line;
    }

    protected function lineDescription(string $type): string
    {
        return self::ordinal((int) $this->installment->angsuran_ke)
            .' installment '
            .$this->shortName()
            .' ( '.$type.' )';
    }

    protected function principalAccountCode(): string
    {
        $loan = $this->installment->loan;
        $creditor = $loan->creditor;

        if (! empty($loan->akun_pokok_gl)) {
            return $loan->akun_pokok_gl;
        }

        if (! empty($creditor->akun_pokok_default)) {
            return $creditor->akun_pokok_default;
        }

        return self::DEFAULT_PRINCIPAL_ACCOUNT;
    }

    protected function shortName(): string
    {
        $creditor = $this->installment->loan?->creditor;

        return trim((string) ($creditor?->nama_singkat ?? ''));
    }

    protected function cardCode(): string
    {
        $creditor = $this->installment->loan?->creditor;

        return trim((string) ($creditor?->sap_code ?? $creditor?->sapBusinessPartner?->code ?? ''));
    }
}
