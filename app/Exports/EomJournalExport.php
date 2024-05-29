<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class EomJournalExport implements FromView
{
    private $eom_journal_details;

    public function __construct($eom_journal_details)
    {
        $this->eom_journal_details = $eom_journal_details;
    }

    public function view(): View
    {
        $data = $this->eom_journal_details;

        return view('reports.eom.export', compact('data'));
    }
}
