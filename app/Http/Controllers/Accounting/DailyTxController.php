<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Imports\DailyTxImport;
use App\Imports\Wtax23Import;
use App\Models\Customer;
use App\Models\DailyTx;
use App\Models\Faktur;
use App\Models\InvoiceCreation;
use App\Models\Wtax23;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DailyTxController extends Controller
{
    public function index()
    {
        if (!request()->query('page')) return view('accounting.daily-tx.index');

        return view('accounting.daily-tx.wtax23');
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

    public function uploadWtax23(Request $request)
    {
        $request->validate([
            'file_upload' => 'required|mimes:xls,xlsx',
        ]);

        $file = $request->file('file_upload');
        $filename = 'wtax23_' . uniqid() . '_' . $file->getClientOriginalName();
        $file->move(public_path('invoices'), $filename);

        Excel::import(new Wtax23Import, public_path('invoices/' . $filename));

        // Check for duplicate doc_num and delete them
        $importedRecords = Wtax23::where('batch_no', Wtax23::max('batch_no'))->get();
        foreach ($importedRecords as $record) {
            $exists = Wtax23::where('doc_num', $record->doc_num)->where('id', '!=', $record->id)->first();
            if ($exists) {
                $record->delete();
            }
        }

        unlink(public_path('invoices/' . $filename));

        $importedCount = $importedRecords->count();

        return redirect()->back()->with('success', "File uploaded successfully. $importedCount records imported.");
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

    public function wtax23data()
    {
        $documents = Wtax23::orderBy('posting_date', 'desc')
            ->get();

        return datatables()->of($documents)
            ->addIndexColumn()
            ->toJson();
    }

    public function copyToFakturs()
    {
        $documents = DailyTx::where('account', '11603001')
            ->where('debit', '>', 0)
            ->where('will_delete', 0)
            ->get();

        $totalRecords = $documents->count();
        $copiedRecords = 0;
        $createdVendors = 0;
        $batch_no = Faktur::max('batch_no') + 1;

        foreach ($documents as $document) {
            $exists = Faktur::where('doc_num', $document->doc_num)->first();
            if ($exists) {
                continue;
            }

            $vendor = Customer::firstOrCreate([
                'code' => $document->vendor_code,
            ], [
                'name' => $document->vendor_name,
                'type' => 'vendor',
            ]);

            // Check if the vendor was newly created
            if ($vendor->wasRecentlyCreated) {
                $createdVendors++;
            }

            Faktur::create([
                'customer_id' => $vendor->id,
                'create_date' => $document->create_date,
                'posting_date' => $document->posting_date,
                'doc_num' => $document->doc_num,
                'type' => 'purchase',
                'account' => '11603001',
                'faktur_no' => $document->faktur_no,
                'faktur_date' => $document->faktur_date,
                'dpp' => $document->debit / 0.11,
                'ppn' => $document->debit,
                'remarks' => $document->remarks,
                'user_code' => $document->user_code,
                'batch_no' => $batch_no,
                'uploaded_by' => $document->uploaded_by,
            ]);

            $copiedRecords++;
        }

        return redirect()->back()->with('success', "$copiedRecords out of $totalRecords records copied successfully. $createdVendors new vendor records created.");
    }
}
