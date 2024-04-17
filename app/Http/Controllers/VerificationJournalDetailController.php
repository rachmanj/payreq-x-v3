<?php

namespace App\Http\Controllers;

use App\Models\VerificationJournalDetail;
use Illuminate\Http\Request;

class VerificationJournalDetailController extends Controller
{
    public function store($data)
    {
        $record = VerificationJournalDetail::create($data);

        return $record;
    }
}
