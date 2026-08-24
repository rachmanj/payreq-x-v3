<?php

namespace App\Services;

use App\Models\SapBusinessPartner;
use App\Models\UtilityApInvoice;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use App\Models\UtilityVendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SapUtilityApInvoiceBuilder
{
    /**
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * @param  Collection<int, UtilityBill>  $bills
     */
    public function __construct(
        protected Collection $bills,
        protected UtilityVendor $vendor,
        protected ?string $numAtCard = null,
    ) {
        $this->config = config('services.sap.ap_invoice.utility', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $partner = $this->vendor->sapBusinessPartner;
        $postingDate = Carbon::today();
        $dueDate = $this->resolveDueDate();

        return [
            'CardCode' => $partner?->code,
            'DocType' => 'dDocument_Service',
            'DocDate' => $postingDate->format('Y-m-d'),
            'DocDueDate' => $dueDate->format('Y-m-d'),
            'TaxDate' => $postingDate->format('Y-m-d'),
            'DocCurrency' => 'IDR',
            'DocRate' => 1.0,
            'NumAtCard' => $this->resolveNumAtCard(),
            'Comments' => $this->buildComments(),
            'U_MIS_CCDepartment' => $this->headerDepartment(),
            'DocumentLines' => $this->buildDocumentLines(),
        ];
    }

    /**
     * @return list<string>
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->bills->isEmpty()) {
            $errors[] = 'Tidak ada tagihan yang dipilih.';

            return $errors;
        }

        $jenisValues = $this->bills
            ->map(fn (UtilityBill $bill) => $bill->customer?->jenis_utilitas)
            ->unique()
            ->values();

        if ($jenisValues->count() !== 1) {
            $errors[] = 'Tagihan harus dari satu jenis utilitas yang sama.';
        }

        $jenis = $jenisValues->first();
        if ($jenis && $this->vendor->jenis_utilitas !== $jenis) {
            $errors[] = 'Vendor mapping tidak sesuai dengan jenis utilitas tagihan.';
        }

        foreach ($this->bills as $bill) {
            $customer = $bill->customer;
            $label = $customer?->nama ?? ('#'.$bill->id);

            if (($customer?->tipe ?? 'postpaid') !== 'postpaid') {
                $errors[] = "Hanya tagihan pascabayar yang bisa dibuatkan AP Invoice. ({$label})";
            }

            if ($bill->tanggal_bayar) {
                $errors[] = "Hanya tagihan yang belum lunas yang bisa dibuatkan AP Invoice. ({$label})";
            }

            if ($bill->payreq_id) {
                $errors[] = "Tagihan {$label} sudah masuk payreq reimburse.";
            }

            if ($bill->utility_ap_invoice_id) {
                $errors[] = "Tagihan {$label} sudah masuk AP Invoice SAP.";
            }

            if (empty($customer?->account_id) || ! $customer?->account) {
                $errors[] = "ID pelanggan {$label} belum punya akun COA.";
            }

            if (empty($customer?->department)) {
                $errors[] = "ID pelanggan {$label} belum punya department/cost center.";
            }

            if (empty($customer?->project)) {
                $errors[] = "ID pelanggan {$label} belum punya project.";
            }

            if ((float) $bill->jumlah_tagihan <= 0) {
                $errors[] = "Jumlah tagihan {$label} harus lebih dari nol.";
            }
        }

        $partner = $this->vendor->sapBusinessPartner;
        $jenisLabel = UtilityCustomer::JENIS_UTILITAS[$this->vendor->jenis_utilitas] ?? strtoupper($this->vendor->jenis_utilitas);

        if (! $partner) {
            $errors[] = "Vendor SAP untuk {$jenisLabel} belum di-mapping.";
        } else {
            if (! $partner->active) {
                $errors[] = "SAP Business Partner '{$partner->code}' ({$partner->name}) tidak aktif.";
            }

            if (! in_array($partner->type, ['S', SapBusinessPartner::TYPE_SUPPLIER], true)) {
                $errors[] = "SAP Business Partner '{$partner->code}' harus bertipe Supplier/Vendor (sekarang: {$partner->type}).";
            }

            if (empty($partner->code)) {
                $errors[] = 'SAP Business Partner yang di-mapping tidak punya CardCode.';
            }
        }

        $override = trim((string) $this->numAtCard);
        if ($override !== '') {
            $duplicate = UtilityApInvoice::query()
                ->where('num_at_card', $override)
                ->exists();

            if ($duplicate) {
                $errors[] = "Vendor Ref. No. '{$override}' sudah dipakai.";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreviewData(): array
    {
        $partner = $this->vendor->sapBusinessPartner;
        $postingDate = Carbon::today();
        $dueDate = $this->resolveDueDate();
        $jenis = $this->bills->first()?->customer?->jenis_utilitas ?? $this->vendor->jenis_utilitas;

        $lines = $this->bills->map(function (UtilityBill $bill) {
            $customer = $bill->customer;

            return [
                'bill_id' => $bill->id,
                'id_pelanggan' => $customer?->id_pelanggan,
                'nama' => $customer?->nama,
                'lokasi' => $customer?->lokasi,
                'project' => $customer?->project,
                'department' => $customer?->department,
                'account_number' => $customer?->account?->account_number,
                'account_name' => $customer?->account?->account_name,
                'periode' => $bill->periode,
                'jumlah_tagihan' => (float) $bill->jumlah_tagihan,
                'line_memo' => $this->buildLineDescription($bill),
            ];
        })->values()->all();

        return [
            'vendor' => [
                'code' => $partner?->code,
                'name' => $partner?->name,
            ],
            'jenis_utilitas' => $jenis,
            'jenis_label' => UtilityCustomer::JENIS_UTILITAS[$jenis] ?? strtoupper((string) $jenis),
            'dates' => [
                'posting_date' => $postingDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'tax_date' => $postingDate->format('Y-m-d'),
            ],
            'num_at_card' => $this->resolveNumAtCard(),
            'tax_code' => $this->taxCode(),
            'header_department' => $this->headerDepartment(),
            'total' => (float) $this->bills->sum('jumlah_tagihan'),
            'line_count' => $this->bills->count(),
            'periode_summary' => $this->periodeSummary(),
            'lines' => $lines,
        ];
    }

    public function buildReferenceNumber(): string
    {
        $earliest = $this->bills->pluck('periode')->filter()->sort()->first();
        $jenis = strtoupper((string) ($this->bills->first()?->customer?->jenis_utilitas ?? $this->vendor->jenis_utilitas));

        if ($earliest) {
            $period = Carbon::createFromFormat('Y-m', $earliest);
            $base = sprintf('%s %s/%s', $jenis, $period->format('n'), $period->format('y'));
        } else {
            $base = $jenis.' '.now()->format('n/y');
        }

        $candidate = $base;
        $suffix = 2;
        while (UtilityApInvoice::query()->where('num_at_card', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    public function periodeSummary(): string
    {
        return $this->bills->pluck('periode')->unique()->sort()->values()->implode(', ');
    }

    public function taxCode(): string
    {
        return (string) ($this->config['default_tax_code'] ?? 'B100');
    }

    public function headerDepartment(): string
    {
        return (string) ($this->config['header_department'] ?? '30');
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildDocumentLines(): array
    {
        $taxCode = $this->taxCode();

        return $this->bills->map(function (UtilityBill $bill) use ($taxCode) {
            $customer = $bill->customer;
            $amount = (float) $bill->jumlah_tagihan;

            $line = [
                'AccountCode' => $customer?->account?->account_number,
                'ItemDescription' => $this->buildLineDescription($bill),
                'Quantity' => 1,
                'UnitPrice' => $amount,
                'LineTotal' => $amount,
                'VatGroup' => $taxCode,
                'TaxCode' => $taxCode,
                'WTLiable' => 'tNO',
                'CostingCode' => $customer?->department,
                'ProjectCode' => $customer?->project,
                'U_MIS_QtyService' => 1,
                'U_MIS_PriceService' => $amount,
                'UseBaseUnits' => 'N',
            ];

            return $line;
        })->values()->all();
    }

    protected function buildComments(): string
    {
        $jenis = strtoupper((string) ($this->bills->first()?->customer?->jenis_utilitas ?? $this->vendor->jenis_utilitas));

        return trim($jenis.' periode '.$this->periodeSummary());
    }

    protected function buildLineDescription(UtilityBill $bill): string
    {
        $customer = $bill->customer;
        $jenis = strtoupper((string) ($customer?->jenis_utilitas ?? ''));
        $project = (string) ($customer?->project ?? '');
        $idPelanggan = (string) ($customer?->id_pelanggan ?? '');
        $period = $this->formatPeriodMonthYear($bill->periode);

        return trim(implode(' ', array_filter([$jenis, $project, $period, $idPelanggan], fn ($part) => $part !== '')));
    }

    protected function formatPeriodMonthYear(?string $periode): string
    {
        if (! $periode) {
            return now()->format('M-Y');
        }

        return Carbon::createFromFormat('Y-m', $periode)->format('M-Y');
    }

    protected function resolveDueDate(): Carbon
    {
        $latest = $this->bills
            ->pluck('tanggal_jatuh_tempo')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date))
            ->sort()
            ->last();

        return $latest instanceof Carbon ? $latest : Carbon::today();
    }

    protected function resolveNumAtCard(): string
    {
        $override = trim((string) $this->numAtCard);

        return $override !== '' ? $override : $this->buildReferenceNumber();
    }
}
