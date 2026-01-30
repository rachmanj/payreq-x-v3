<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Imports\InvoiceCreationImport;
use App\Models\InvoiceCreation;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceCreationController extends Controller
{
    private $months = [
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

    private $years = [2024, 2025, 2026];

    private $users;

    public function __construct(Request $request)
    {
        $this->setUsersBasedOnProject($request->query('project'));
    }

    private function setUsersBasedOnProject($project)
    {
        if ($project == '000H') {
            $this->users = ['accbpn2', 'accbpn3', 'accbpn5', 'accbpn6'];
        } elseif ($project == '001H') {
            $this->users = ['accjkt1', 'accjkt2', 'accjkt4', 'accjkt5'];
        } else {
            $this->users = []; // Default to an empty array or handle other projects as needed
        }
    }

    public function index()
    {
        $dashboard_data = $this->dashboard_data();
        $project = request()->query('project');

        return view('accounting.invoice-creation.index', compact('dashboard_data', 'project'));
    }

    public function detail()
    {
        return view('accounting.invoice-creation.detail');
    }

    public function by_user()
    {
        $dashboard_data = $this->dashboard_data_by_user();

        return view('accounting.invoice-creation.by_user', compact('dashboard_data'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file_upload' => 'required|mimes:xls,xlsx',
        ]);

        $file = $request->file('file_upload');
        $filename = 'inv-tx_' . uniqid() . '_' . $file->getClientOriginalName();
        $file->move(public_path('invoices'), $filename);

        Excel::import(new InvoiceCreationImport, public_path('invoices/' . $filename));
        unlink(public_path('invoices/' . $filename));

        $this->deleteWhenTrue();
        $this->deleteDuplicate();

        return redirect()->back()->with('success', 'File uploaded successfully');
    }

    public function data()
    {
        $invoices = InvoiceCreation::select('document_number', 'create_date', 'posting_date', 'user_code', 'duration')
            ->whereIn('user_code', $this->users)
            ->orderBy('create_date', 'desc')
            ->orderBy('duration', 'desc')
            ->get();

        return datatables()->of($invoices)
            ->addIndexColumn()
            ->toJson();
    }

    public function dashboard_data()
    {
        $result = $this->generate_dashboard_data($this->years, $this->months, $this->users);


        return $result;
    }

    public function dashboard_data_by_user()
    {
        return $this->generate_dashboard_data_by_user($this->years, $this->months, $this->users);
    }

    private function generate_dashboard_data($years, $months, $users)
    {
        $result = [];

        foreach ($years as $year) {
            $year_data = [];

            foreach ($months as $month => $month_name) {
                $invoiceData = $this->get_invoice_data($month, $year, $users);
                $year_data[] = [
                    'month' => $month_name,
                    'invoice_count' => $invoiceData['count'],
                    'sum_duration' => $invoiceData['sum'],
                    'average_duration' => $invoiceData['average'],
                ];
            }

            $currentYearData = $this->get_invoice_data_for_year($year, $this->users);

            $result[] = [
                'year' => $year,
                'invoice_count' => $currentYearData['count'],
                'average_duration' => $currentYearData['average'],
                'data' => $year_data,
            ];
        }

        return $result;
    }

    private function generate_dashboard_data_by_user($years, $months, $users)
    {
        $result = [];

        foreach ($years as $year) {
            $year_data = [];

            foreach ($users as $user_code) {
                $user_data = [];

                foreach ($months as $month => $month_name) {
                    $invoiceData = $this->get_invoice_data_by_user($month, $year, $user_code);
                    $user_data[] = [
                        'month' => $month_name,
                        'invoice_count' => $invoiceData['count'],
                        'sum_duration' => $invoiceData['sum'],
                        'average_duration' => $invoiceData['average'],
                    ];
                }

                $year_data[] = [
                    'user_code' => $user_code,
                    'data' => $user_data,
                ];
            }

            $result[] = [
                'year' => $year,
                'data' => $year_data,
            ];
        }

        return $result;
    }

    private function get_invoice_data($month, $year, $users)
    {
        $data = InvoiceCreation::selectRaw('COUNT(DISTINCT document_number) as count, SUM(duration) as sum')
            ->whereMonth('create_date', $month)
            ->whereYear('create_date', $year)
            ->whereIn('user_code', $users)
            ->first();

        $count = $data->count;
        $sum = $data->sum;
        $average = $count > 0 ? number_format($sum / $count, 2) : 0;

        return compact('count', 'sum', 'average');
    }

    private function get_invoice_data_by_user($month, $year, $user_code)
    {
        $data = InvoiceCreation::selectRaw('COUNT(DISTINCT document_number) as count, SUM(duration) as sum')
            ->whereMonth('create_date', $month)
            ->whereYear('create_date', $year)
            ->where('user_code', $user_code)
            ->first();

        $count = $data->count;
        $sum = $data->sum;
        $average = $count > 0 ? number_format($sum / $count, 2) : 0;

        return compact('count', 'sum', 'average');
    }

    private function get_invoice_data_for_year($year, $users)
    {
        $data = InvoiceCreation::selectRaw('COUNT(DISTINCT document_number) as count, SUM(duration) as sum')
            ->whereYear('create_date', $year)
            ->whereIn('user_code', $users)
            ->first();

        $count = $data->count;
        $sum = $data->sum;
        $average = $count > 0 ? number_format($sum / $count, 2) : 0;

        return compact('count', 'sum', 'average');
    }

    private function deleteWhenTrue()
    {
        InvoiceCreation::where('will_delete', 1)->delete();
    }

    private function deleteDuplicate()
    {
        $duplicates = InvoiceCreation::select('document_number')
            ->groupBy('document_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('document_number');

        foreach ($duplicates as $document_number) {
            $records = InvoiceCreation::where('document_number', $document_number)->get();
            $records->shift();
            InvoiceCreation::whereIn('id', $records->pluck('id'))->delete();
        }
    }
}
