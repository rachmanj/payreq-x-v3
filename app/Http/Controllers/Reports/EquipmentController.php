<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
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
        $unit_histories = RealizationDetail::select('id', 'unit_no', 'type', 'qty', 'description', 'km_position', 'amount', 'created_at', 'uom')
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
                if ($this->getLastKM($expense_by_unit->unit_no) !== null) {
                    return number_format($this->getLastKM($expense_by_unit->unit_no)->km_position, 0, ',', '.');
                }
                return 0;
            })
            ->addColumn('fuel_cost_per_km', function ($expense_by_unit) {
                $total_km = '<small>total km: ' . $this->fuelCostPerKM($expense_by_unit->unit_no)['total_km'] . ' km</small>';
                $total_cost = '<small>total fuel cost: Rp.' . number_format($this->fuelCostPerKM($expense_by_unit->unit_no)['total_cost'], 0, ',', '.') . '</small>';
                $cost_per_km = '<small> FCPKM: Rp.' . number_format($this->fuelCostPerKM($expense_by_unit->unit_no)['cost_per_km'], 0, ',', '.') . '</small>';
                return $total_km . '<br>' . $total_cost . '<br>' . $cost_per_km;
            })
            ->addColumn('project', function ($expense_by_unit) {
                $equipment = Equipment::where('unit_code', $expense_by_unit->unit_no)->first();
                return $equipment ? $equipment->project : '-';
            })
            ->editColumn('total_amount', function ($expense_by_unit) {
                return number_format($expense_by_unit->total_amount, 0, ',', '.');
            })
            ->addIndexColumn()
            ->rawColumns(['unit_no', 'fuel_cost_per_km'])
            ->toJson();
    }

    public function getLastKM($unit_no)
    {
        $last_km = RealizationDetail::where('unit_no', $unit_no)
            ->orderBy('km_position', 'desc')
            ->first();

        return $last_km;
    }

    public function fuelCostPerKM($unit_no)
    {
        $km_positions = $this->unit_histories($unit_no)->pluck('km_position')->toArray();
        $filtered_km_positions = array_diff($km_positions, [null, 0]);

        if (!empty($filtered_km_positions)) {
            $lowest_km = min($filtered_km_positions);
        } else {
            // Handle the case where there are no valid km positions
            $lowest_km = 0; // or any other default value
        }

        $highest_km = !empty($km_positions) ? max($km_positions) : 0;
        $total_km = $highest_km - $lowest_km;
        $total_cost = $this->unit_histories($unit_no)->where('type', 'fuel')->sum('amount');

        return [
            'total_km' => $total_km,
            'total_cost' => $total_cost,
            'cost_per_km' => $total_km > 0 ? $total_cost / $total_km : 0
        ];
    }

    public function km_array($unit_no)
    {
        return $this->unit_histories($unit_no)->pluck('km_position', 'id')->toArray();
    }
}
