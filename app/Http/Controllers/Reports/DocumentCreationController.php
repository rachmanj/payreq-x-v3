<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\InvoiceCreation;
use Illuminate\Http\Request;

class DocumentCreationController extends Controller
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

    private $years = [2026, 2025, 2024];

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
            $this->users = ['accjkt1', 'accjkt2', 'accjkt3', 'accjkt4', 'accjkt5'];
        } else {
            $this->users = []; // Default to an empty array or handle other projects as needed
        }
    }

    private function determineProject()
    {
        $userRoles = app(UserController::class)->getUserRoles();
        if (array_intersect(['superadmin', 'admin', 'cashier'], $userRoles)) {
            return request()->query('project');
        } else {
            return auth()->user()->project;
        }
    }

    public function index()
    {
        $dashboard_data = $this->dashboard_data();
        $project = $this->determineProject();

        if ($project == '000H') {
            return view('reports.document-creation.000H.index', compact('dashboard_data', 'project'));
        } elseif ($project == '001H') {
            return view('reports.document-creation.001H.index', compact('dashboard_data', 'project'));
        } else {
            return view('reports.document-creation.index_default', compact('dashboard_data', 'project'));
        }
    }

    public function detail()
    {
        $project = $this->determineProject();

        if ($project == '000H') {
            return view('reports.document-creation.000H.detail', compact('project'));
        } elseif ($project == '001H') {
            return view('reports.document-creation.001H.detail', compact('project'));
        }
    }

    public function by_user()
    {
        $dashboard_data = $this->dashboard_data_by_user();
        $project = $this->determineProject();

        if ($project == '000H') {
            return view('reports.document-creation.000H.by_user', compact('dashboard_data', 'project'));
        } elseif ($project == '001H') {
            return view('reports.document-creation.001H.by_user', compact('dashboard_data', 'project'));
        } else {
            return view('reports.document-creation.000H.by_user', compact('dashboard_data', 'project'));
        }
    }

    public function data()
    {
        $invoices = InvoiceCreation::select('document_number', 'create_date', 'posting_date', 'user_code', 'duration', 'doc_type')
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
        return $this->generate_dashboard_data($this->years, $this->months, $this->users);
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
                    'invoice_count' => $invoiceData['count'] ?? 0,
                    'sum_duration' => $invoiceData['sum'] ?? 0,
                    'average_duration' => $invoiceData['average'] ?? 0,
                ];
            }

            $year_summary = $this->get_invoice_data_for_year($year, $users);
            $result[] = [
                'year' => $year,
                'data' => $year_data,
                'year_summary' => $year_summary,
            ];
        }

        return $result;
    }

    public function generate_dashboard_data_by_user($years, $months, $users)
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
                        'invoice_count' => $invoiceData['count'] ?? 0,
                        'sum_duration' => $invoiceData['sum'] ?? 0,
                        'average_duration' => $invoiceData['average'] ?? 0,
                    ];
                }

                $year_summary = $this->get_invoice_data_for_year_by_user($year, $user_code);
                $year_data[] = [
                    'user_code' => $user_code,
                    'data' => $user_data,
                    'year_summary' => $year_summary,
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

        $count = $data->count ?? 0;
        $sum = $data->sum ?? 0;
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

        $count = $data->count ?? 0;
        $sum = $data->sum ?? 0;
        $average = $count > 0 ? number_format($sum / $count, 2) : 0;

        return compact('count', 'sum', 'average');
    }

    private function get_invoice_data_for_year($year, $users)
    {
        $data = InvoiceCreation::selectRaw('COUNT(DISTINCT document_number) as count, SUM(duration) as sum')
            ->whereYear('create_date', $year)
            ->whereIn('user_code', $users)
            ->first();

        $count = $data->count ?? 0;
        $sum = $data->sum ?? 0;
        $average = $count > 0 ? number_format($sum / $count, 2) : 0;

        return compact('count', 'sum', 'average');
    }

    private function get_invoice_data_for_year_by_user($year, $user_code)
    {
        $data = InvoiceCreation::selectRaw('COUNT(DISTINCT document_number) as count, SUM(duration) as sum')
            ->whereYear('create_date', $year)
            ->where('user_code', $user_code)
            ->first();

        $count = $data->count ?? 0;
        $sum = $data->sum ?? 0;
        $average = $count > 0 ? number_format($sum / $count, 2) : 0;

        return compact('count', 'sum', 'average');
    }
}
