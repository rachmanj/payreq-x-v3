<?php

namespace App\Http\Controllers\Reports;

use App\Exports\SummaryUnitExpenseExport;
use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\RealizationDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class EquipmentController extends Controller
{
    protected $cacheTTL = 1800;

    public function index()
    {
        $years = range(date('Y'), date('Y') - 5);
        return view('reports.equipment.index', compact('years'));
    }

    public function detail(Request $request)
    {
        $unit_no = $request->unit_no;
        $year = $request->get('year', date('Y'));
        $cacheKey = 'equipment_detail_' . $unit_no . '_' . $year;

        $result = Cache::remember($cacheKey, $this->cacheTTL, function () use ($unit_no, $year) {
            $fuelDetails = $this->unit_histories($unit_no, $year)
                ->where('realization_details.type', 'fuel')
                ->get();

            $serviceDetails = $this->unit_histories($unit_no, $year)
                ->where('realization_details.type', 'service')
                ->get();

            $otherDetails = $this->unit_histories($unit_no, $year)
                ->where(function ($query) {
                    $query->whereNull('realization_details.type')
                        ->orWhere('realization_details.type', 'other');
                })
                ->get();

            $taxDetails = $this->unit_histories($unit_no, $year)
                ->where('realization_details.type', 'tax')
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
                'tax' => [
                    'total' => $taxDetails->sum('amount'),
                    'details' => $taxDetails
                ],
            ];
        });

        return view('reports.equipment.detail', compact('unit_no', 'year', 'result'));
    }

    public function unit_histories($unit_no, $year = null)
    {
        $query = RealizationDetail::select('realization_details.id', 'realization_details.unit_no', 'realization_details.type', 'realization_details.qty', 'realization_details.description', 'realization_details.km_position', 'realization_details.amount', 'realization_details.created_at', 'realization_details.uom')
            ->join('verification_journals', 'realization_details.verification_journal_id', '=', 'verification_journals.id')
            ->whereNotNull('realization_details.verification_journal_id')
            ->where('realization_details.unit_no', $unit_no);

        if ($year) {
            $query->whereRaw('YEAR(verification_journals.sap_posting_date) = ?', [$year]);
        }

        return $query->orderBy('realization_details.created_at', 'desc');
    }

    public function data(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $cacheKey = 'equipment_data_' . $year . '_' .
            request()->input('draw', 0) . '_' .
            request()->input('start', 0) . '_' .
            request()->input('length', 10) . '_' .
            request()->input('search.value', '') . '_' .
            request()->input('order.0.column', 0) . '_' .
            request()->input('order.0.dir', 'asc');

        return Cache::remember($cacheKey, 60, function () use ($year) {
            $expense_by_unit = DB::table('realization_details')
                ->join('verification_journals', 'realization_details.verification_journal_id', '=', 'verification_journals.id')
                ->whereNotNull('realization_details.verification_journal_id')
                ->whereNotNull('realization_details.unit_no')
                ->whereRaw('YEAR(verification_journals.sap_posting_date) = ?', [$year])
                ->select(
                    'realization_details.unit_no',
                    DB::raw("SUM(CASE WHEN realization_details.type = 'fuel' THEN realization_details.amount ELSE 0 END) as fuel_amount"),
                    DB::raw("SUM(CASE WHEN realization_details.type = 'fuel' AND realization_details.qty > 0 THEN realization_details.qty ELSE 0 END) as fuel_qty"),
                    DB::raw("SUM(CASE WHEN realization_details.type = 'service' THEN realization_details.amount ELSE 0 END) as service_amount"),
                    DB::raw("SUM(CASE WHEN realization_details.type = 'other' OR realization_details.type IS NULL THEN realization_details.amount ELSE 0 END) as other_amount"),
                    DB::raw("SUM(CASE WHEN realization_details.type = 'tax' THEN realization_details.amount ELSE 0 END) as tax_amount"),
                    DB::raw('SUM(realization_details.amount) as total_amount')
                )
                ->groupBy('realization_details.unit_no')
                ->orderBy('realization_details.unit_no')
                ->get();

            return datatables()->of($expense_by_unit)
                ->editColumn('unit_no', function ($row) use ($year) {
                    $url = route('reports.equipment.detail', ['unit_no' => $row->unit_no, 'year' => $year]);
                    return '<a href="' . $url . '" style="color: black" title="Click to see detail" target="_blank">' . e($row->unit_no) . '</a>';
                })
                // FCPKM, Est. FCPL, Last KM - commented for later use
                // ->addColumn('last_km', function ($row) use ($year) {
                //     $cacheKey = 'equipment_last_km_' . $row->unit_no . '_' . $year;
                //     $lastKM = Cache::remember($cacheKey, $this->cacheTTL, function () use ($row, $year) {
                //         return $this->getLastKM($row->unit_no, $year);
                //     });
                //     return $lastKM !== null ? number_format($lastKM->km_position, 0, ',', '.') : 0;
                // })
                // ->addColumn('fuel_cost_per_km', function ($row) use ($year) {
                //     $cacheKey = 'equipment_fcpkm_' . $row->unit_no . '_' . $year;
                //     $fuelCost = Cache::remember($cacheKey, $this->cacheTTL, function () use ($row, $year) {
                //         return $this->fuelCostPerKM($row->unit_no, $year);
                //     });
                //     $total_km = '<small>total km: ' . $fuelCost['total_km'] . ' km</small>';
                //     $total_cost = '<small>total fuel cost: Rp.' . number_format($fuelCost['total_cost'], 0, ',', '.') . '</small>';
                //     $cost_per_km = '<small> FCPKM: Rp.' . number_format($fuelCost['cost_per_km'], 0, ',', '.') . '</small>';
                //     return $total_km . '<br>' . $total_cost . '<br>' . $cost_per_km;
                // })
                ->addColumn('project', function ($row) use ($year) {
                    $cacheKey = 'equipment_project_' . $row->unit_no . '_' . $year;
                    return Cache::remember($cacheKey, $this->cacheTTL, function () use ($row) {
                        $equipment = Equipment::where('unit_code', $row->unit_no)->first();
                        return $equipment ? $equipment->project : '-';
                    });
                })
                ->editColumn('fuel_amount', fn ($row) => number_format($row->fuel_amount, 0, ',', '.'))
                // ->addColumn('estimated_fcpl', function ($row) {
                //     $fuelQty = (float) ($row->fuel_qty ?? 0);
                //     $fuelAmount = (float) ($row->fuel_amount ?? 0);
                //     if ($fuelQty > 0) {
                //         return number_format($fuelAmount / $fuelQty, 0, ',', '.');
                //     }
                //     return '-';
                // })
                ->editColumn('service_amount', fn ($row) => number_format($row->service_amount, 0, ',', '.'))
                ->editColumn('other_amount', fn ($row) => number_format($row->other_amount, 0, ',', '.'))
                ->editColumn('tax_amount', fn ($row) => number_format($row->tax_amount, 0, ',', '.'))
                ->editColumn('total_amount', fn ($row) => number_format($row->total_amount, 0, ',', '.'))
                ->addIndexColumn()
                ->rawColumns(['unit_no'])
                ->toJson();
        });
    }

    public function getLastKM($unit_no, $year = null)
    {
        $query = RealizationDetail::select('realization_details.km_position')
            ->join('verification_journals', 'realization_details.verification_journal_id', '=', 'verification_journals.id')
            ->whereNotNull('realization_details.verification_journal_id')
            ->where('realization_details.unit_no', $unit_no)
            ->whereNotNull('realization_details.km_position');

        if ($year) {
            $query->whereRaw('YEAR(verification_journals.sap_posting_date) = ?', [$year]);
        }

        return $query->orderBy('realization_details.km_position', 'desc')->first();
    }

    public function fuelCostPerKM($unit_no, $year = null)
    {
        $cacheKey = 'km_array_' . $unit_no . '_' . ($year ?? 'all');
        $km_positions = Cache::remember($cacheKey, $this->cacheTTL, function () use ($unit_no, $year) {
            return $this->km_array($unit_no, $year);
        });

        $filtered_km_positions = array_filter($km_positions, fn ($v) => $v !== null && $v > 0);
        $lowest_km = !empty($filtered_km_positions) ? min($filtered_km_positions) : 0;
        $highest_km = !empty($filtered_km_positions) ? max($filtered_km_positions) : 0;
        $total_km = $highest_km - $lowest_km;

        $cacheKeyCost = 'fuel_cost_' . $unit_no . '_' . ($year ?? 'all');
        $total_cost = Cache::remember($cacheKeyCost, $this->cacheTTL, function () use ($unit_no, $year) {
            $query = RealizationDetail::where('realization_details.unit_no', $unit_no)
                ->where('realization_details.type', 'fuel')
                ->join('verification_journals', 'realization_details.verification_journal_id', '=', 'verification_journals.id')
                ->whereNotNull('realization_details.verification_journal_id');
            if ($year) {
                $query->whereRaw('YEAR(verification_journals.sap_posting_date) = ?', [$year]);
            }
            return $query->sum('realization_details.amount');
        });

        return [
            'total_km' => $total_km,
            'total_cost' => $total_cost,
            'cost_per_km' => $total_km > 0 ? $total_cost / $total_km : 0
        ];
    }

    public function km_array($unit_no, $year = null)
    {
        $query = RealizationDetail::where('realization_details.unit_no', $unit_no)
            ->whereNotNull('realization_details.km_position')
            ->join('verification_journals', 'realization_details.verification_journal_id', '=', 'verification_journals.id')
            ->whereNotNull('realization_details.verification_journal_id');

        if ($year) {
            $query->whereRaw('YEAR(verification_journals.sap_posting_date) = ?', [$year]);
        }

        return $query->pluck('realization_details.km_position', 'realization_details.id')->toArray();
    }

    public function export(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        if ($year < 2000 || $year > 2100) {
            return redirect()->back()->with('error', 'Invalid year.');
        }
        $filename = 'summary_unit_expense_' . $year . '.xlsx';
        return Excel::download(new SummaryUnitExpenseExport($year), $filename);
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
