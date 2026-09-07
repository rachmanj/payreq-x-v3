<?php

namespace App\Services;

use App\Models\BpjsApInvoice;
use App\Models\SapBusinessPartner;
use Carbon\Carbon;

class SapBpjsApInvoiceBuilder
{
    private const INDONESIAN_MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function __construct(protected BpjsApInvoice $invoice) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $docDate = Carbon::parse($this->invoice->doc_date);
        $dueDate = Carbon::parse($this->invoice->due_date);
        $amount = (float) $this->invoice->amount;
        $label = $this->invoice->label;

        return [
            'CardCode' => $this->cardCode(),
            'DocType' => 'dDocument_Service',
            'DocDate' => $docDate->format('Y-m-d'),
            'DocDueDate' => $dueDate->format('Y-m-d'),
            'TaxDate' => $docDate->format('Y-m-d'),
            'DocCurrency' => 'IDR',
            'DocRate' => 1.0,
            'NumAtCard' => $this->invoice->num_at_card,
            'Comments' => $label,
            'U_MIS_CCDepartement' => '20',
            'U_MIS_FPDate' => $docDate->format('Y-m-d'),
            'DocumentLines' => [
                [
                    'AccountCode' => $this->accountCode(),
                    'ItemDescription' => $label,
                    'Quantity' => 1,
                    'UnitPrice' => $amount,
                    'LineTotal' => $amount,
                    'VatGroup' => 'B100',
                    'TaxCode' => 'B100',
                    'WTLiable' => 'tNO',
                    'CostingCode' => '20',
                    'ProjectCode' => $this->invoice->unit,
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function validate(): array
    {
        $errors = [];

        if (! in_array($this->invoice->jenis, [
            BpjsApInvoice::JENIS_KESEHATAN,
            BpjsApInvoice::JENIS_KETENAGAKERJAAN,
        ], true)) {
            $errors[] = 'Jenis BPJS tidak valid.';
        }

        if (empty($this->invoice->unit)) {
            $errors[] = 'Unit wajib dipilih.';
        }

        if (empty($this->invoice->periode)) {
            $errors[] = 'Periode wajib diisi.';
        }

        if ((float) $this->invoice->amount <= 0) {
            $errors[] = 'Nominal harus lebih dari nol.';
        }

        if (empty($this->invoice->doc_date)) {
            $errors[] = 'Tanggal dokumen wajib diisi.';
        }

        if (empty($this->invoice->due_date)) {
            $errors[] = 'Tanggal jatuh tempo wajib diisi.';
        }

        $partner = $this->resolvePartner();
        $jenisLabel = BpjsApInvoice::JENIS_LABELS[$this->invoice->jenis] ?? strtoupper($this->invoice->jenis);

        if (! $partner) {
            $errors[] = "Vendor SAP untuk {$jenisLabel} belum di-mapping.";
        } else {
            if (! $partner->active) {
                $errors[] = "SAP Business Partner '{$partner->code}' ({$partner->name}) tidak aktif.";
            }

            if (! in_array($partner->type, ['S', SapBusinessPartner::TYPE_SUPPLIER], true)) {
                $errors[] = "SAP Business Partner '{$partner->code}' harus bertipe Supplier/Vendor.";
            }
        }

        $numAtCard = trim((string) $this->invoice->num_at_card);
        if ($numAtCard === '') {
            $errors[] = 'Vendor Ref. No. (NumAtCard) wajib diisi.';
        } else {
            $duplicate = BpjsApInvoice::query()
                ->where('num_at_card', $numAtCard)
                ->when($this->invoice->id, fn ($query) => $query->where('id', '!=', $this->invoice->id))
                ->exists();

            if ($duplicate) {
                $errors[] = "Vendor Ref. No. '{$numAtCard}' sudah dipakai.";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreviewData(): array
    {
        $partner = $this->resolvePartner();
        $docDate = Carbon::parse($this->invoice->doc_date);
        $dueDate = Carbon::parse($this->invoice->due_date);
        $amount = (float) $this->invoice->amount;

        return [
            'jenis' => $this->invoice->jenis,
            'jenis_label' => BpjsApInvoice::JENIS_LABELS[$this->invoice->jenis] ?? strtoupper($this->invoice->jenis),
            'unit' => $this->invoice->unit,
            'unit_label' => BpjsApInvoice::unitLabel($this->invoice->unit),
            'periode' => $this->invoice->periode,
            'vendor' => [
                'code' => $partner?->code ?? $this->cardCode(),
                'name' => $partner?->name ?? (BpjsApInvoice::SUPPLIER_NAMES[$this->invoice->jenis] ?? '-'),
            ],
            'account_code' => $this->accountCode(),
            'costing_code' => '20',
            'project_code' => $this->invoice->unit,
            'dates' => [
                'doc_date' => $docDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'tax_date' => $docDate->format('Y-m-d'),
            ],
            'num_at_card' => $this->invoice->num_at_card,
            'label' => $this->invoice->label,
            'tax_code' => 'B100',
            'amount' => $amount,
            'invoice_number' => $this->invoice->invoiceNumber(),
        ];
    }

    public static function buildLabel(string $jenis, string $unit, string $periode): string
    {
        $jenisLabel = BpjsApInvoice::JENIS_LABELS[$jenis] ?? strtoupper($jenis);
        $unitLabel = BpjsApInvoice::unitLabel($unit);
        $period = Carbon::createFromFormat('Y-m', $periode);
        $monthName = self::indonesianMonthName((int) $period->format('n'));

        return trim("{$jenisLabel} {$unitLabel} per {$monthName} {$period->format('Y')}");
    }

    public static function buildNumAtCard(string $periode, ?int $exceptId = null): string
    {
        $period = Carbon::createFromFormat('Y-m', $periode);
        $base = $period->format('n').'/'.$period->format('y');
        $candidate = $base;
        $suffix = 2;

        while (BpjsApInvoice::query()
            ->where('num_at_card', $candidate)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    public static function indonesianMonthName(int $month): string
    {
        return self::INDONESIAN_MONTHS[$month] ?? '';
    }

    protected function cardCode(): string
    {
        return BpjsApInvoice::CARD_CODES[$this->invoice->jenis] ?? '';
    }

    protected function accountCode(): string
    {
        return BpjsApInvoice::ACCOUNT_CODES[$this->invoice->jenis] ?? '';
    }

    protected function resolvePartner(): ?SapBusinessPartner
    {
        return SapBusinessPartner::query()
            ->suppliers()
            ->where('code', $this->cardCode())
            ->first();
    }
}
