<?php

namespace App\Exports;

use App\Models\Rekap;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RekapExport implements FromView
{
    public function view(): View
    {
        $rekaps = Rekap::orderBy('posting_date', 'desc')->get();
        return view('rekaps.export', compact('rekaps'));
    }
}
