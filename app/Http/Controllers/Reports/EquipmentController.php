<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\RealizationDetail; // Add this import statement
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index()
    {
        return view('reports.equipment.index');
    }

    public function detail(Request $request)
    {
        $unit_no = $request->unit_no;

        $others = $this->unit_histories($unit_no)->where(function ($query) {
            $query->whereNull('type')
                ->orWhere('type', 'other');
        })->get();

        $result = [
            'fuel' => [
                'total' => $this->unit_histories($unit_no)->where('type', 'fuel')->sum('amount'),
                'details' => $this->unit_histories($unit_no)->where('type', 'fuel')->get()
            ],
            'service' => [
                'total' => $this->unit_histories($unit_no)->where('type', 'service')->sum('amount'),
                'details' => $this->unit_histories($unit_no)->where('type', 'service')->get()
            ],
            'other' => [
                'total' => $this->unit_histories($unit_no)->where(function ($query) {
                    $query->whereNull('type')
                        ->orWhere('type', 'other');
                })->sum('amount'),
                'details' => $this->unit_histories($unit_no)->where(function ($query) {
                    $query->whereNull('type')
                        ->orWhere('type', 'other');
                })->get()
            ],
        ];

        return view('reports.equipment.detail', compact('unit_no', 'result'));
    }

    public function unit_histories($unit_no)
    {
        $unit_histories = RealizationDetail::select('unit_no', 'type', 'qty', 'description', 'km_position', 'amount', 'created_at', 'uom')
            ->where('unit_no', $unit_no)
            ->orderBy('created_at', 'desc');

        return $unit_histories;
    }

    public function data()
    {
        $expense_by_unit = RealizationDetail::selectRaw('unit_no, SUM(amount) as total_amount')
            ->whereNotNull('unit_no')
            ->groupBy('unit_no')
            ->get();

        return datatables()->of($expense_by_unit)
            ->editColumn('unit_no', function ($expense_by_unit) {
                return '<a href="' . route('reports.equipment.detail') . '?unit_no=' . $expense_by_unit->unit_no . '" style="color: black" title="Click to see detail" target="_blank">' . $expense_by_unit->unit_no . '</a>';
            })
            ->addColumn('last_km', function ($expense_by_unit) {
                if ($this->getLastKM($expense_by_unit->unit_no) == null)
                    return 0;
                else {
                    return number_format($this->getLastKM($expense_by_unit->unit_no)->km_position, 0, ',', '.');
                }
            })
            ->editColumn('total_amount', function ($expense_by_unit) {
                return number_format($expense_by_unit->total_amount, 0, ',', '.');
            })
            ->addIndexColumn()
            ->rawColumns(['unit_no'])
            ->toJson();
    }

    public function getLastKM($unit_no)
    {
        $last_km = RealizationDetail::where('unit_no', $unit_no)
            ->orderBy('km_position', 'desc')
            ->first();

        return $last_km;
    }
}
