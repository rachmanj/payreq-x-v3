<?php

namespace App\Exports;

use App\Models\Faktur;
use App\Models\TaxPeriod;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PpnMasaPajakExport implements FromView, ShouldAutoSize
{
    public function __construct(
        private string $masaPajak,
        private ?TaxPeriod $period,
    ) {}

    public function view(): View
    {
        $masukan = Faktur::query()
            ->with('customer')
            ->where('type', 'purchase')
            ->where('masa_pajak', $this->masaPajak)
            ->orderBy('faktur_date')
            ->get();

        $keluaran = Faktur::query()
            ->with('customer')
            ->where('type', 'sales')
            ->where('masa_pajak', $this->masaPajak)
            ->orderBy('faktur_date')
            ->get();

        return view('accounting.tax.ppn.exports.masa-pajak', [
            'masaPajak' => $this->masaPajak,
            'period' => $this->period,
            'masukan' => $masukan,
            'keluaran' => $keluaran,
        ]);
    }
}
