<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Imports\InvoiceCreationImport;
use App\Models\InvoiceCreation;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceCreationController extends Controller
{
    public function index()
    {
        $dashboard_data = $this->dashboard_data();

        return view('accounting.invoice-creation.index', compact('dashboard_data'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file_upload' => 'required|mimes:xls,xlsx',
        ]);

        // get the file
        $file = $request->file('file_upload');

        // rename the file name to prevent duplication
        $filename = 'inv-tx_' . uniqid() . '_' . $file->getClientOriginalName();

        // move the file to the folder
        $file->move(public_path('invoices'), $filename);

        // import data from the file to the database
        Excel::import(new InvoiceCreationImport, public_path('invoices/' . $filename));

        // delete the file after importing
        unlink(public_path('invoices/' . $filename));

        // run function deleteWhenTrue
        $this->deleteWhenTrue();

        // run function remove duplicate
        $this->deleteDuplicate();

        // return to the index page with success message
        return redirect()->back()->with('success', 'File uploaded successfully');
    }

    public function dashboard_data()
    {
        $months = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec'
        ];
        $years = [2024, 2025];
        $result = [];

        foreach ($years as $year) {
            $year_data = [];

            foreach ($months as $month => $month_name) {
                $invoiceData = $this->get_invoice_data($month, $year);
                $year_data[] = [
                    'month' => $month_name,
                    'invoice_count' => $invoiceData['count'],
                    'sum_duration' => $invoiceData['sum'],
                    'average_duration' => $invoiceData['average'],
                ];
            }

            $result[] = [
                'year' => $year,
                'data' => $year_data,
            ];
        }

        return $result;
    }

    private function get_invoice_data($month, $year)
    {
        $query = InvoiceCreation::selectRaw('COUNT(DISTINCT document_number) as count, SUM(duration) as sum')
            ->whereMonth('create_date', $month)
            ->whereYear('create_date', $year)
            ->whereIn('user_code', $this->include_user());

        $data = $query->first();

        $count = $data->count;
        $sum = $data->sum;
        $average = $count > 0 ? number_format($sum / $count, 2) : 0;

        return compact('count', 'sum', 'average');
    }

    private function include_user()
    {
        return ['accbpn1', 'accbpn2', 'accbpn3', 'accbpn4', 'accbpn5', 'accbpn6'];
    }

    private function deleteWhenTrue()
    {
        // delete record where will_delete is true
        InvoiceCreation::where('will_delete', 1)->delete();

        return true;
    }

    private function deleteDuplicate()
    {
        // Retrieve all document_numbers that have duplicates
        $duplicates = InvoiceCreation::select('document_number')
            ->groupBy('document_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('document_number');

        foreach ($duplicates as $document_number) {
            // Get all records with the same document_number
            $records = InvoiceCreation::where('document_number', $document_number)->get();

            // Keep the first record and delete the rest
            $records->shift();
            InvoiceCreation::whereIn('id', $records->pluck('id'))->delete();
        }

        return true;
    }
}
