<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PpnMissingFakturExport implements FromView, ShouldAutoSize
{
    /**
     * @param  list<array<string, mixed>>  $groups
     */
    public function __construct(
        private string $masaPajak,
        private array $groups,
    ) {}

    public function view(): View
    {
        return view('accounting.tax.ppn.exports.belum-diterima', [
            'masaPajak' => $this->masaPajak,
            'groups' => $this->groups,
        ]);
    }
}
