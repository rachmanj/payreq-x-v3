<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Wtax23;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Wtax23Controller extends Controller
{
    public function index()
    {
        $page = request('page');
        $status = request('status');

        $amount_data = $this->generate_amount_data();
        $count_data = $this->generate_count_data();

        $views = [
            'dashboard' => 'accounting.wtax23.dashboard',
            'purchase' => $status == 'outstanding' ? 'accounting.wtax23.ap.outstanding' : 'accounting.wtax23.ap.complete',
            'default' => $status == 'outstanding' ? 'accounting.wtax23.ar.outstanding' : 'accounting.wtax23.ar.complete'
        ];

        if ($page === 'dashboard') {
            return view($views[$page], compact('amount_data', 'count_data'));
        }

        return view($views[$page] ?? $views['default']);
    }

    public function update(Request $request, $id)
    {
        $existingDocument = Wtax23::where('bupot_no', $request->input('bupot_no'))->first();
        if ($existingDocument && $existingDocument->id != $id) {
            return redirect()->back()->with('error', 'Bupot number already exists.');
        }

        $document = Wtax23::findOrFail($id);
        $document->bupot_no = $request->input('bupot_no');
        $document->bupot_date = $request->input('bupot_date');
        $document->bupot_by = auth()->user()->name;
        $document->bupot_at = now();

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $extension = $file->getClientOriginalExtension();
            $filename = 'wtax_' . uniqid() . '.' . $extension;
            $file->move(public_path('wtax'), $filename);
            $document->filename = $filename;
        }

        $document->save();

        return redirect()->back()->with('success', 'Bupot number and attachment updated successfully');
    }

    public function data()
    {
        $page = request()->query('page');
        $status = request()->query('status');

        $query = Wtax23::query();

        if ($page === 'purchase') {
            $query->where('doc_type', 'out');
            $action_button = $status === 'outstanding' ? 'accounting.wtax23.ap.action' : 'accounting.wtax23.ap.action_complete';
        } else {
            $query->where('doc_type', 'in');
            $action_button = $status === 'outstanding' ? 'accounting.wtax23.ar.action' : 'accounting.wtax23.ar.action_complete';
        }

        if ($status === 'outstanding') {
            $query->whereNull('bupot_no')->orderBy('posting_date', 'asc');
        } else {
            $query->whereNotNull('bupot_no')->orderBy('updated_at', 'desc');
        }

        $documents = $query->get();

        return datatables()->of($documents)
            ->editColumn('amount', function ($document) {
                return number_format($document->amount, 2);
            })
            ->editColumn('create_date', function ($document) {
                return date('d-M-Y', strtotime($document->create_date));
            })
            ->editColumn('posting_date', function ($document) {
                return date('d-M-Y', strtotime($document->posting_date));
            })
            ->editColumn('remarks', function ($document) {
                return '<small>' . strtolower($document->remarks) . '</small>';
            })
            // add column name days that count the difference between posting_date and today
            ->editColumn('days', function ($document) {
                $today = date('Y-m-d');
                $diff = date_diff(date_create($document->posting_date), date_create($today));
                return $diff->format('%a');
            })
            ->editColumn('bupot_by', function ($document) {
                $bupotAt = Carbon::parse($document->bupot_at)->addHours(8)->format('d-M-Y H:i');
                return '<small>' . $document->bupot_by . '</small><br><small>at ' . $bupotAt . '</small>';
            })
            ->addColumn('doc_date', function ($document) {
                $createDate = Carbon::parse($document->create_date)->format('d-M-Y');
                $postingDate = Carbon::parse($document->posting_date)->format('d-M-Y');
                return $createDate . '<br>' . $postingDate;
            })
            ->addColumn('action', $action_button)
            ->addIndexColumn()
            ->rawColumns(['remarks', 'action', 'bupot_by', 'doc_date'])
            ->toJson();
    }

    public function generate_amount_data()
    {
        $years = DB::table('wtax23s')
            ->select(DB::raw('DISTINCT YEAR(posting_date) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

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

        $data = [];

        foreach ($years as $year) {
            $yearData = [
                'year' => $year,
                'in' => 0,
                'out' => 0,
                'data' => []
            ];

            foreach ($months as $month => $monthName) {

                $monthData = [
                    'month' => $month,
                    'month_name' => $monthName,
                    'in' => number_format($this->sum_amount_monthly($year, $month, 'in') / 1000, 2),
                    'out' => number_format($this->sum_amount_monthly($year, $month, 'out') / 1000, 2)
                ];

                $yearData['data'][] = $monthData;
            }

            // Format jumlah tahunan
            $yearData['in'] = number_format($this->sum_amount_yearly($year, 'in') / 1000, 2);
            $yearData['out'] = number_format($this->sum_amount_yearly($year, 'out') / 1000, 2);

            $data[] = $yearData;
        }

        return $data;
    }

    public function generate_count_data()
    {
        $years = DB::table('wtax23s')
            ->select(DB::raw('DISTINCT YEAR(create_date) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

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

        $data = [];

        foreach ($years as $year) {
            $yearData = [
                'year' => $year,
                'in' => 0,
                'out' => 0,
                'data' => []
            ];

            foreach ($months as $month => $monthName) {

                $out_outstanding = $this->count_outstanding_monthly($year, $month, 'out');
                $out_complete = $this->count_complete_monthly($year, $month, 'out');
                $in_outstanding = $this->count_outstanding_monthly($year, $month, 'in');
                $in_complete = $this->count_complete_monthly($year, $month, 'in');

                $monthData = [
                    'month' => $month,
                    'month_name' => $monthName,
                    'in' => [
                        'outstanding' => $in_outstanding,
                        'complete' => $in_complete
                    ],
                    'out' => [
                        'outstanding' => $out_outstanding,
                        'complete' => $out_complete
                    ]
                ];

                $yearData['data'][] = $monthData;

                // Tambahkan jumlah bulanan ke jumlah tahunan
                $yearData['in'] = $yearData['in'] + $in_outstanding + $in_complete;
                $yearData['out'] = $yearData['out'] + $out_outstanding + $out_complete;
            }

            $data[] = $yearData;
        }

        return $data;
    }

    public function test_count()
    {
        $result = Wtax23::where('doc_type', 'out')
            ->whereYear('posting_date', 2024)
            ->sum('amount');

        return number_format($result, 2);
    }

    private function sum_amount_monthly($year, $month, $doc_type)
    {
        return Wtax23::whereYear('posting_date', $year)
            ->whereMonth('posting_date', $month)
            ->where('doc_type', $doc_type)
            ->sum('amount');
    }

    private function sum_amount_yearly($year, $doc_type)
    {
        return Wtax23::whereYear('posting_date', $year)
            ->where('doc_type', $doc_type)
            ->sum('amount');
    }

    private function count_complete_monthly($year, $month, $doc_type)
    {
        return Wtax23::whereYear('create_date', $year)
            ->whereMonth('create_date', $month)
            ->where('doc_type', $doc_type)
            ->whereNotNull('bupot_no')
            ->count();
    }

    private function count_outstanding_monthly($year, $month, $doc_type)
    {
        return Wtax23::whereYear('create_date', $year)
            ->whereMonth('create_date', $month)
            ->where('doc_type', $doc_type)
            ->whereNull('bupot_no')
            ->count();
    }
}
