<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Imports\DailyTxImport;
use App\Models\DailyTx;
use App\Models\InvoiceCreation;
use App\Models\Wtax23;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DailyTxController extends Controller
{
    public function index()
    {
        return view('accounting.daily-tx.index');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file_upload' => 'required|mimes:xls,xlsx',
        ]);

        $file = $request->file('file_upload');
        $filename = 'daily_' . uniqid() . '_' . $file->getClientOriginalName();
        $file->move(public_path('invoices'), $filename);

        Excel::import(new DailyTxImport, public_path('invoices/' . $filename));
        unlink(public_path('invoices/' . $filename));

        return redirect()->back()->with('success', 'File uploaded successfully');
    }

    public function truncate()
    {
        DailyTx::truncate();

        return redirect()->back()->with('success', 'Table truncated successfully');
    }

    // copy certains records from daily_tx to witax23s table
    public function copyToWtax23()
    {
        $documents = DailyTx::where('account', '21701005')
            ->where('credit', '>', 0)
            ->get();

        $lastBatchNumber = Wtax23::max('batch_no');
        $batch_no = $lastBatchNumber + 1;

        $totalRecords = $documents->count();
        $copiedRecords = 0;

        foreach ($documents as $document) {
            // check if the record already exists by checking the doc_num, if it exists, skip to the next record
            $exists = Wtax23::where('doc_num', $document->doc_num)->first();
            if ($exists) {
                continue;
            }

            Wtax23::create([
                'create_date' => $document->create_date,
                'posting_date' => $document->posting_date,
                'duration' => $document->duration,
                'doc_num' => $document->doc_num,
                'doc_type' => $document->doc_type,
                'project' => $document->project,
                'account' => $document->account,
                'amount' => $document->credit,
                'remarks' => $document->remarks,
                'user_code' => $document->user_code,
                'batch_no' => $batch_no,
            ]);

            $copiedRecords++;
        }

        return redirect()->back()->with('success', "$copiedRecords out of $totalRecords records copied successfully");
    }

    public function data()
    {
        $documents = DailyTx::orderBy('create_date', 'desc')
            ->orderBy('duration', 'desc')
            ->get();

        return datatables()->of($documents)
            ->addIndexColumn()
            ->toJson();
    }

    public function copyToInvoiceCreation()
    {
        $documents = DailyTx::whereIn('doc_type', ['Outgoing Payments', 'AP Invoice'])
            ->where('will_delete', 0)
            ->get();

        $lastBatchNumber = InvoiceCreation::max('batch_number');
        $batch_no = $lastBatchNumber + 1;

        $totalRecords = $documents->count();
        $copiedRecords = 0;

        foreach ($documents as $document) {

            $exists = InvoiceCreation::where('document_number', $document->doc_num)->first();
            if ($exists) {
                continue;
            }

            InvoiceCreation::create([
                'create_date' => $document->create_date,
                'posting_date' => $document->posting_date,
                'duration' => $document->duration,
                'document_number' => $document->doc_num,
                'doc_type' => $document->doc_type == 'Outgoing Payments' ? 'outgoing' : 'invoice',
                'user_code' => $document->user_code,
                'batch_number' => $batch_no,
                'uploaded_by' => $document->uploaded_by,
                'will_delete' => $document->will_delete,
            ]);

            $copiedRecords++;
        }

        return redirect()->back()->with('success', "$copiedRecords out of $totalRecords records copied successfully");
    }
}
