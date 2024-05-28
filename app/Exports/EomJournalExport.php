<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class EomJournalExport implements FromView
{
    private $eom_journal;

    public function __construct($eom_journal)
    {
        $this->eom_journal = $eom_journal;
    }

    public function view(): View
    {
        $data = $this->eom_journal;

        return view('reports.eom.export', compact('data'));
    }
}
