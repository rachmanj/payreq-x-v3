<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Anggaran;
use App\Models\PeriodeAnggaran;
use App\Models\Project;
use App\Services\AnggaranReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnggaranController extends Controller
{
    protected int $cacheTTL = 1800;

    public function __construct(
        protected AnggaranReleaseService $releaseService
    ) {}

    public function index()
    {
        $status = request()->query('status', 'active');

        switch ($status) {
            case 'active':
                return view('reports.anggaran.index');
            case 'inactive':
                return view('reports.anggaran.inactive');
            default:
                return view('reports.anggaran.index');
        }
    }

    public function inactive(): RedirectResponse
    {
        return redirect()->route('reports.anggaran.index', ['status' => 'inactive']);
    }

    public function dashboard()
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $project = auth()->user()->project;

        $query = Anggaran::query()->where('is_active', 1);

        if (! array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->where('project', $project);
        }

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->whereIn('status', ['approved']);
        }

        $approvedSubset = (clone $query)->where('status', 'approved');

        $stats = [
            'count_visible' => (clone $query)->count(),
            'count_approved' => (clone $approvedSubset)->count(),
            'sum_budget_approved' => (float) (clone $approvedSubset)->sum('amount'),
            'sum_balance_approved' => (float) (clone $approvedSubset)->sum('balance'),
        ];

        $stats['avg_utilization'] = $stats['sum_budget_approved'] > 0
            ? round(($stats['sum_balance_approved'] / $stats['sum_budget_approved']) * 100, 2)
            : 0.0;

        return view('reports.anggaran.dashboard', compact('stats'));
    }

    public function edit($id)
    {
        $anggaran = Cache::remember('anggaran_'.$id, $this->cacheTTL, function () use ($id) {
            return Anggaran::findOrFail($id);
        });

        $projects = Cache::remember('projects_all', $this->cacheTTL, function () {
            return Project::orderBy('code', 'asc')->get();
        });

        $project = auth()->user()->project;
        $periode_anggarans = Cache::remember('periode_anggarans_'.$project, $this->cacheTTL, function () use ($project) {
            return PeriodeAnggaran::orderBy('periode', 'asc')
                ->where('periode_type', 'anggaran')
                ->where('project', $project)
                ->get();
        });

        $periode_ofrs = Cache::remember('periode_ofrs_'.$project, $this->cacheTTL, function () use ($project) {
            return PeriodeAnggaran::orderBy('periode', 'asc')
                ->where('periode_type', 'ofr')
                ->where('project', $project)
                ->get();
        });

        if ($anggaran->filename) {
            $origin_filename = preg_replace('/^rab_\d+_/', '', $anggaran->filename);
        } else {
            $origin_filename = null;
        }

        return view('reports.anggaran.edit', compact('anggaran', 'projects', 'origin_filename', 'periode_anggarans', 'periode_ofrs'));
    }

    public function update(Request $request)
    {
        $anggaran = Anggaran::find($request->anggaran_id);

        DB::beginTransaction();
        try {
            $anggaran->update([
                'rab_no' => $request->rab_no,
                'date' => $request->date ? $request->date : date('Y-m-d'),
                'description' => $request->description,
                'rab_project' => $request->project,
                'type' => $request->rab_type,
                'amount' => $request->amount,
                'periode_anggaran' => $request->periode_anggaran,
                'periode_ofr' => $request->periode_ofr,
                'usage' => $request->usage,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active,
            ]);

            if ($request->file_upload) {
                $file = $request->file('file_upload');
                $filename = 'rab_'.rand().'_'.$file->getClientOriginalName();
                $file->move(public_path('file_upload'), $filename);
                $anggaran->update([
                    'filename' => $filename,
                ]);
            }

            DB::commit();

            $this->releaseService->forgetDetailCaches($anggaran->id);

            return redirect()->route('reports.anggaran.index')->with('success', 'Anggaran updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error updating anggaran: '.$e->getMessage());
        }
    }

    public function show($id)
    {
        $anggaran = Anggaran::findOrFail($id);
        $summary = $this->releaseService->progressSummary($anggaran);
        $progres_persen = $summary['persen'];
        $total_release = $summary['amount'];
        $statusColor = $this->statusColor((float) $progres_persen);

        return view('reports.anggaran.show', compact('anggaran', 'progres_persen', 'total_release', 'statusColor'));
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $get_status = request()->query('status', 'active');
        $project = auth()->user()->project;

        $customSearch = request()->has('custom_search');
        $searchNomor = request()->input('search_nomor');
        $searchCreator = request()->input('search_creator');
        $searchProject = request()->input('search_project');
        $searchDescription = request()->input('search_description');

        $query = Anggaran::query()
            ->with(['createdBy:id,name'])
            ->select([
                'id',
                'nomor',
                'rab_no',
                'date',
                'description',
                'amount',
                'balance',
                'persen',
                'rab_project',
                'usage',
                'created_by',
                'periode_anggaran',
                'periode_ofr',
                'is_active',
                'status',
                'project',
            ]);

        if ($get_status === 'active') {
            $query->where('is_active', 1);
        } else {
            $query->where('is_active', 0);
        }

        if (! array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->where('project', $project);
        }

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->whereIn('status', ['approved']);
        }

        if ($customSearch) {
            if (! empty($searchNomor)) {
                $query->where(function ($q) use ($searchNomor) {
                    $q->where('nomor', 'like', "%{$searchNomor}%")
                        ->orWhere('rab_no', 'like', "%{$searchNomor}%");
                });
            }

            if (! empty($searchProject)) {
                $query->where('rab_project', 'like', "%{$searchProject}%");
            }

            if (! empty($searchDescription)) {
                $query->where('description', 'like', "%{$searchDescription}%");
            }

            if (! empty($searchCreator)) {
                $query->whereHas('createdBy', function ($q) use ($searchCreator) {
                    $q->where('name', 'like', "%{$searchCreator}%");
                });
            }
        } else {
            $search = request()->input('search.value');
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nomor', 'like', "%{$search}%")
                        ->orWhere('rab_no', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('rab_project', 'like', "%{$search}%")
                        ->orWhere('amount', 'like', "%{$search}%");
                });
            }
        }

        $dataTable = datatables()->of($query);

        $dataTable
            ->addColumn('checkbox', function ($anggaran) {
                return '<input type="checkbox" name="id[]" value="'.$anggaran->id.'">';
            })
            ->editColumn('nomor', function ($anggaran) {
                $nomor = '<a href="'.route('reports.anggaran.show', $anggaran->id).'"><small>'.$anggaran->nomor.'</small></a>';
                $rab_no = $anggaran->rab_no ? '<a href="'.route('reports.anggaran.show', $anggaran->id).'"><small>'.$anggaran->rab_no.' <br> '.date('d-M-Y', strtotime((string) $anggaran->date)).'</small></a>' : '-';

                return $anggaran->rab_no ? $nomor.'<br>'.$rab_no : $nomor.'<br><small>'.date('d-M-Y', strtotime((string) $anggaran->date)).'</small>';
            })
            ->editColumn('description', function ($anggaran) {
                return '<small>'.$anggaran->description.'</small>';
            })
            ->editColumn('amount', function ($anggaran) {
                return number_format((float) $anggaran->amount, 2);
            })
            ->addColumn('progres', function ($anggaran) {
                $progres = $anggaran->persen ? $anggaran->persen : 0;
                $statusColor = $this->statusColor((float) $progres);

                return '<div class="text-center"><small>'.$progres.'%</small>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped '.$statusColor.'" role="progressbar" style="width: '.$progres.'%" aria-valuenow="'.$progres.'" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>';
            })
            ->editColumn('rab_project', function ($anggaran) {
                return $anggaran->rab_project.'<br><small>'.ucfirst((string) $anggaran->usage).'</small>';
            })
            ->addColumn('creator', function ($anggaran) {
                $name = explode(' ', $anggaran->createdBy->name);

                return '<small>'.$name[0].'</small>';
            })
            ->addColumn('periode', function ($anggaran) {
                $pa = $anggaran->periode_anggaran ? date('M Y', strtotime((string) $anggaran->periode_anggaran)) : '-';
                $ofr = $anggaran->periode_ofr ? date('M Y', strtotime((string) $anggaran->periode_ofr)) : '-';
                $radio = $anggaran->is_active == 1 ? '<span class="badge bg-success">1</span>' : '<span class="badge bg-danger">0</span>';

                return '<small>'.$pa.'<br>'.$ofr.'</small>'.'<br>'.$radio;
            })
            ->addIndexColumn()
            ->addColumn('action', 'reports.anggaran.action')
            ->rawColumns(['checkbox', 'action', 'nomor', 'description', 'progres', 'rab_project', 'creator', 'periode']);

        return $dataTable->toJson();
    }

    public function recalculate(): RedirectResponse
    {
        $this->authorize('recalculate_release');

        try {
            $this->releaseService->syncAllApprovedStoredTotals();
            $this->releaseService->flushAllReportingCaches();

            return redirect()->route('reports.anggaran.index')->with('success', 'Release Anggaran berhasil diupdate');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error recalculating: '.$e->getMessage());
        }
    }

    public function statusColor(float $progress): string
    {
        if ($progress > 100) {
            return 'bg-danger';
        }
        if ($progress > 90) {
            return 'bg-warning';
        }

        return 'bg-success';
    }

    public function update_many(Request $request): RedirectResponse
    {
        $this->authorize('anggaran_bulk_activate_deactivate');

        $ids = $this->authorizedBulkIds($request);

        if (count($ids) > 0) {
            DB::beginTransaction();
            try {
                $chunks = array_chunk($ids, 100);
                foreach ($chunks as $chunk) {
                    Anggaran::whereIn('id', $chunk)->update(['is_active' => 0]);

                    foreach ($chunk as $id) {
                        $this->releaseService->forgetDetailCaches((int) $id);
                    }
                }

                DB::commit();

                $this->releaseService->flushListingCaches();

                return redirect()->back()->with('success', 'Selected records have been inactivated.');
            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()->back()->with('error', 'Error updating records: '.$e->getMessage());
            }
        }

        return redirect()->back()->with('info', 'No records were selected.');
    }

    public function activate_many(Request $request): RedirectResponse
    {
        $this->authorize('anggaran_bulk_activate_deactivate');

        $ids = $this->authorizedBulkIds($request);

        if (count($ids) > 0) {
            DB::beginTransaction();
            try {
                $chunks = array_chunk($ids, 100);
                foreach ($chunks as $chunk) {
                    Anggaran::whereIn('id', $chunk)->update(['is_active' => 1]);

                    foreach ($chunk as $id) {
                        $this->releaseService->forgetDetailCaches((int) $id);
                    }
                }

                DB::commit();

                $this->releaseService->flushListingCaches();

                return redirect()->back()->with('success', 'Selected records have been activated.');
            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()->back()->with('error', 'Error updating records: '.$e->getMessage());
            }
        }

        return redirect()->back()->with('info', 'No records were selected.');
    }

    /**
     * @return array<int, int>
     */
    protected function authorizedBulkIds(Request $request): array
    {
        $requested = array_map('intval', $request->input('id', []));
        $requested = array_values(array_filter($requested));

        if ($requested === []) {
            return [];
        }

        $userRoles = app(UserController::class)->getUserRoles();
        $project = auth()->user()->project;

        $query = Anggaran::query()->whereIn('id', $requested);

        if (! array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->where('project', $project);
        }

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->whereIn('status', ['approved']);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
