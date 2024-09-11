<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class BilyetTemplateExport implements FromView
{
    public function view(): View
    {
        return view('cashier.bilyets.export_template');
    }
}
