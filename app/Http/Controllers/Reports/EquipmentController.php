<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\RealizationDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EquipmentController extends Controller
{
    // Cache TTL in seconds (30 minutes)
    protected $cacheTTL = 1800;

    public function index()
    {
        return view('reports.equipment.index');
    }

    public function detail(Request $request)
    {
        $unit_no = $request->unit_no;
        $cacheKey = 'equipment_detail_' . $unit_no;

        // Cache the equipment detail data
        $result = Cache::remember($cacheKey, $this->cacheTTL, function () use ($unit_no) {
            // Use eager loading and select specific fields
            $fuelDetails = $this->unit_histories($unit_no)
                ->where('type', 'fuel')
                ->get();

            $serviceDetails = $this->unit_histories($unit_no)
                ->where('type', 'service')
                ->get();

            $otherDetails = $this->unit_histories($unit_no)
                ->where(function ($query) {
                    $query->whereNull('type')
                        ->orWhere('type', 'other');
                })
                ->get();

            return [
                'fuel' => [
                    'total' => $fuelDetails->sum('amount'),
                    'details' => $fuelDetails
                ],
                'service' => [
                    'total' => $serviceDetails->sum('amount'),
                    'details' => $serviceDetails
                ],
                'other' => [
                    'total' => $otherDetails->sum('amount'),
                    'details' => $otherDetails
                ],
            ];
        });

        return view('reports.equipment.detail', compact('unit_no', 'result'));
    }

    public function unit_histories($unit_no)
    {
        // Optimize the query by selecting only needed fields
        return RealizationDetail::select('id', 'unit_no', 'type', 'qty', 'description', 'km_position', 'amount', 'created_at', 'uom')
            ->where('unit_no', $unit_no)
            ->orderBy('created_at', 'desc');
    }

    public function data()
    {
        // Create a unique cache key based on query parameters
        $user_id = auth()->user()->id;
        $project = auth()->user()->project;
        $cacheKey = 'equipment_data_' . $project . '_' . $user_id . '_' .
            request()->input('draw', 0) . '_' .
            request()->input('start', 0) . '_' .
            request()->input('length', 10) . '_' .
            request()->input('search.value', '') . '_' .
            request()->input('order.0.column', 0) . '_' .
            request()->input('order.0.dir', 'asc');

        return Cache::remember($cacheKey, 60, function () {
            // Use a more efficient query with grouping and indexing
            $expense_by_unit = DB::table('realization_details')
                ->select('unit_no', DB::raw('SUM(amount) as total_amount'))
                ->whereNotNull('unit_no')
                ->groupBy('unit_no')
                ->get();

            return datatables()->of($expense_by_unit)
                ->editColumn('unit_no', function ($expense_by_unit) {
                    return '<a href="' . route('reports.equipment.detail') . '?unit_no=' . $expense_by_unit->unit_no . '" style="color: black" title="Click to see detail" target="_blank">' . $expense_by_unit->unit_no . '</a>';
                })
                ->addColumn('last_km', function ($expense_by_unit) {
                    // Cache the last KM for each unit to avoid repeated queries
                    $cacheKey = 'equipment_last_km_' . $expense_by_unit->unit_no;
                    $lastKM = Cache::remember($cacheKey, $this->cacheTTL, function () use ($expense_by_unit) {
                        return $this->getLastKM($expense_by_unit->unit_no);
                    });

                    if ($lastKM !== null) {
                        return number_format($lastKM->km_position, 0, ',', '.');
                    }
                    return 0;
                })
                ->addColumn('fuel_cost_per_km', function ($expense_by_unit) {
                    // Cache the fuel cost per KM for each unit
                    $cacheKey = 'equipment_fcpkm_' . $expense_by_unit->unit_no;
                    $fuelCost = Cache::remember($cacheKey, $this->cacheTTL, function () use ($expense_by_unit) {
                        return $this->fuelCostPerKM($expense_by_unit->unit_no);
                    });

                    $total_km = '<small>total km: ' . $fuelCost['total_km'] . ' km</small>';
                    $total_cost = '<small>total fuel cost: Rp.' . number_format($fuelCost['total_cost'], 0, ',', '.') . '</small>';
                    $cost_per_km = '<small> FCPKM: Rp.' . number_format($fuelCost['cost_per_km'], 0, ',', '.') . '</small>';
                    return $total_km . '<br>' . $total_cost . '<br>' . $cost_per_km;
                })
                ->addColumn('project', function ($expense_by_unit) {
                    // Cache the equipment project for each unit
                    $cacheKey = 'equipment_project_' . $expense_by_unit->unit_no;
                    return Cache::remember($cacheKey, $this->cacheTTL, function () use ($expense_by_unit) {
                        $equipment = Equipment::where('unit_code', $expense_by_unit->unit_no)->first();
                        return $equipment ? $equipment->project : '-';
                    });
                })
                ->editColumn('total_amount', function ($expense_by_unit) {
                    return number_format($expense_by_unit->total_amount, 0, ',', '.');
                })
                ->addIndexColumn()
                ->rawColumns(['unit_no', 'fuel_cost_per_km'])
                ->toJson();
        });
    }

    public function getLastKM($unit_no)
    {
        // Use a more efficient query with specific column selection
        return RealizationDetail::select('km_position')
            ->where('unit_no', $unit_no)
            ->whereNotNull('km_position')
            ->orderBy('km_position', 'desc')
            ->first();
    }

    public function fuelCostPerKM($unit_no)
    {
        // Cache the KM array for better performance
        $cacheKey = 'km_array_' . $unit_no;
        $km_positions = Cache::remember($cacheKey, $this->cacheTTL, function () use ($unit_no) {
            return $this->km_array($unit_no);
        });

        $filtered_km_positions = array_filter($km_positions, function ($value) {
            return $value !== null && $value > 0;
        });

        if (!empty($filtered_km_positions)) {
            $lowest_km = min($filtered_km_positions);
        } else {
            $lowest_km = 0;
        }

        $highest_km = !empty($filtered_km_positions) ? max($filtered_km_positions) : 0;
        $total_km = $highest_km - $lowest_km;

        // Get total fuel cost with efficient query
        $total_cost = Cache::remember('fuel_cost_' . $unit_no, $this->cacheTTL, function () use ($unit_no) {
            return RealizationDetail::where('unit_no', $unit_no)
                ->where('type', 'fuel')
                ->sum('amount');
        });

        return [
            'total_km' => $total_km,
            'total_cost' => $total_cost,
            'cost_per_km' => $total_km > 0 ? $total_cost / $total_km : 0
        ];
    }

    public function km_array($unit_no)
    {
        // Optimize the query by selecting only needed fields
        return RealizationDetail::where('unit_no', $unit_no)
            ->whereNotNull('km_position')
            ->pluck('km_position', 'id')
            ->toArray();
    }

    /**
     * Clear equipment caches for a specific unit
     */
    public function clearEquipmentCache($unit_no)
    {
        Cache::forget('equipment_detail_' . $unit_no);
        Cache::forget('equipment_last_km_' . $unit_no);
        Cache::forget('equipment_fcpkm_' . $unit_no);
        Cache::forget('equipment_project_' . $unit_no);
        Cache::forget('km_array_' . $unit_no);
        Cache::forget('fuel_cost_' . $unit_no);
    }

    /**
     * Clear all equipment data caches
     */
    public function clearAllEquipmentCaches()
    {
        // Clear all equipment data list caches
        $prefix = 'equipment_data_';
        $keys = Cache::getPrefix() . $prefix . '*';

        // Using low-level cache clear by pattern if available
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags(['equipment'])->flush();
        } else {
            // Get all unit numbers and clear their individual caches
            $unit_nos = RealizationDetail::distinct('unit_no')
                ->whereNotNull('unit_no')
                ->pluck('unit_no')
                ->toArray();

            foreach ($unit_nos as $unit_no) {
                $this->clearEquipmentCache($unit_no);
            }

            // Clear any project-specific equipment caches
            $projects = DB::table('users')->distinct('project')->pluck('project')->toArray();
            foreach ($projects as $project) {
                Cache::forget('equipment_data_' . $project);
            }
        }
    }
}
