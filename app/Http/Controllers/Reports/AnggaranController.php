<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\PeriodeAnggaran;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnggaranController extends Controller
{
    // Cache TTL in seconds (30 minutes)
    protected $cacheTTL = 1800;

    public function index()
    {
        $status = request()->query('status', 'active');

        switch ($status) {
            case 'active':
                return view('reports.anggaran.index');
            case 'inactive':
                return view('reports.anggaran.inactive');
            default:
                return view('reports.anggaran.index'); // Default view if no status or unknown status is provided
        }
    }

    public function edit($id)
    {
        // Cache the anggaran data with related models
        $anggaran = Cache::remember('anggaran_' . $id, $this->cacheTTL, function () use ($id) {
            return Anggaran::find($id);
        });

        // Cache all projects
        $projects = Cache::remember('projects_all', $this->cacheTTL, function () {
            return Project::orderBy('code', 'asc')->get();
        });

        // Cache periode data for current project
        $project = auth()->user()->project;
        $periode_anggarans = Cache::remember('periode_anggarans_' . $project, $this->cacheTTL, function () use ($project) {
            return PeriodeAnggaran::orderBy('periode', 'asc')
                ->where('periode_type', 'anggaran')
                ->where('project', $project)
                ->get();
        });

        $periode_ofrs = Cache::remember('periode_ofrs_' . $project, $this->cacheTTL, function () use ($project) {
            return PeriodeAnggaran::orderBy('periode', 'asc')
                ->where('periode_type', 'ofr')
                ->where('project', $project)
                ->get();
        });

        // rab_45654654654_filename.pdf convert this to filename.pdf
        if ($anggaran->filename) {
            $origin_filename = preg_replace('/^rab_\d+_/', '', $anggaran->filename); // this to convert to original filename
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
                $filename = 'rab_' . rand() . '_' . $file->getClientOriginalName();
                $file->move(public_path('file_upload'), $filename);
                $anggaran->update([
                    'filename' => $filename,
                ]);
            }

            DB::commit();

            // Clear specific anggaran cache
            $this->clearAnggaranCache($anggaran->id);

            return redirect()->route('reports.anggaran.index')->with('success', 'Anggaran updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating anggaran: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        // Cache the anggaran data with calculations
        $cacheKey = 'anggaran_show_' . $id;

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($id) {
            $anggaran = Anggaran::find($id);
            $progres_persen = $anggaran->persen;
            $total_release = $anggaran->balance;
            $statusColor = $this->statusColor($anggaran->persen);

            return [
                'anggaran' => $anggaran,
                'progres_persen' => $progres_persen,
                'total_release' => $total_release,
                'statusColor' => $statusColor
            ];
        });

        return view('reports.anggaran.show', $data);
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $get_status = request()->query('status', 'active');
        $project = auth()->user()->project;

        // Get custom search parameters from the request
        $customSearch = request()->has('custom_search');
        $searchNomor = request()->input('search_nomor');
        $searchCreator = request()->input('search_creator');
        $searchProject = request()->input('search_project');
        $searchDescription = request()->input('search_description');

        // Build the base query
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
                'project'
            ]);

        // Apply status filter
        if ($get_status == 'active') {
            $query->where('is_active', 1);
        } else {
            $query->where('is_active', 0);
        }

        // Apply role-based filters
        if (!array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->where('project', $project);
        }

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->whereIn('status', ['approved']);
        }

        // Apply custom search filters if they exist
        if ($customSearch) {
            if (!empty($searchNomor)) {
                $query->where(function ($q) use ($searchNomor) {
                    $q->where('nomor', 'like', "%{$searchNomor}%")
                        ->orWhere('rab_no', 'like', "%{$searchNomor}%");
                });
            }

            if (!empty($searchProject)) {
                $query->where('rab_project', 'like', "%{$searchProject}%");
            }

            if (!empty($searchDescription)) {
                $query->where('description', 'like', "%{$searchDescription}%");
            }

            if (!empty($searchCreator)) {
                $query->whereHas('createdBy', function ($q) use ($searchCreator) {
                    $q->where('name', 'like', "%{$searchCreator}%");
                });
            }
        } else {
            // If no custom search, handle standard DataTables search
            $search = request()->input('search.value');
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nomor', 'like', "%{$search}%")
                        ->orWhere('rab_no', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('rab_project', 'like', "%{$search}%")
                        ->orWhere('amount', 'like', "%{$search}%");
                });
            }
        }

        // Create DataTables instance
        $dataTable = datatables()->of($query);

        $dataTable
            ->addColumn('checkbox', function ($anggaran) {
                return '<input type="checkbox" name="id[]" value="' . $anggaran->id . '">';
            })
            ->editColumn('nomor', function ($anggaran) {
                $nomor = '<a href="' . route('reports.anggaran.show', $anggaran->id) . '"><small>' . $anggaran->nomor . '</small></a>';
                $rab_no = $anggaran->rab_no ? '<a href="' . route('reports.anggaran.show', $anggaran->id) . '"><small>' . $anggaran->rab_no . ' <br> ' . date('d-M-Y', strtotime($anggaran->date)) . '</small></a>' : '-';
                return $anggaran->rab_no ? $nomor . '<br>' . $rab_no : $nomor . '<br><small>' . date('d-M-Y', strtotime($anggaran->date)) . '</small>';
            })
            ->editColumn('description', function ($anggaran) {
                return '<small>' . $anggaran->description . '</small>';
            })
            ->editColumn('amount', function ($anggaran) {
                return number_format($anggaran->amount, 2);
            })
            ->addColumn('progres', function ($anggaran) {
                $progres = $anggaran->persen ? $anggaran->persen : 0;
                $statusColor = $this->statusColor($progres);
                return '<div class="text-center"><small>' . $progres . '%</small>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped ' . $statusColor . '" role="progressbar" style="width: ' . $progres . '%" aria-valuenow="' . $progres . '" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>';
            })
            ->editColumn('rab_project', function ($anggaran) {
                $content = $anggaran->rab_project . '<br><small>' . ucfirst($anggaran->usage) . '</small>';
                return $content;
            })
            ->addColumn('creator', function ($anggaran) {
                $name = explode(' ', $anggaran->createdBy->name);
                return '<small>' . $name[0] . '</small>';
            })
            ->addColumn('periode', function ($anggaran) {
                $pa = $anggaran->periode_anggaran ? date('M Y', strtotime($anggaran->periode_anggaran)) : '-';
                $radio = $anggaran->is_active == 1 ? '<span class="badge bg-success">1</span>' : '<span class="badge bg-danger">0</span>';
                return '<small>' . $pa . '<br>' . date('M Y', strtotime($anggaran->periode_ofr)) . '</small>' . '<br>' . $radio;
            })
            ->addIndexColumn()
            ->addColumn('action', 'reports.anggaran.action')
            ->rawColumns(['checkbox', 'action', 'nomor', 'description', 'progres', 'rab_project', 'creator', 'periode']);

        return $dataTable->toJson();
    }

    public function recalculate()
    {
        DB::beginTransaction();
        try {
            // Use chunk to process records in batches to avoid memory issues
            Anggaran::where('status', 'approved')
                ->chunk(100, function ($anggarans) {
                    foreach ($anggarans as $anggaran) {
                        $total_release = $this->release_to_date($anggaran->id);
                        $persen = $total_release > 0 ? number_format((($total_release / $anggaran->amount) * 100), 2) : 0;

                        $anggaran->update([
                            'balance' => $total_release,
                            'persen' => $persen,
                        ]);
                    }
                });

            DB::commit();

            // Clear all anggaran caches
            $this->clearAllAnggaranCaches();

            return redirect()->route('reports.anggaran.index')->with('success', 'Release Anggaran berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error recalculating: ' . $e->getMessage());
        }
    }

    public function release_to_date($id)
    {
        $cacheKey = 'anggaran_release_' . $id;

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($id) {
            $anggaran = Anggaran::find($id);

            // Optimize query by using eager loading and specific selects
            $payreqs = Payreq::where('rab_id', $anggaran->id)
                ->whereHas('outgoings')
                ->with([
                    'realization' => function ($query) {
                        $query->select('id', 'payreq_id');
                    },
                    'realization.realizationDetails' => function ($query) {
                        $query->select('id', 'realization_id', 'amount');
                    },
                    'outgoings' => function ($query) {
                        $query->select('id', 'payreq_id', 'amount');
                    }
                ])
                ->select('id', 'type', 'rab_id')
                ->get();

            $total_release = 0;

            foreach ($payreqs as $payreq) {
                if ($payreq->type === 'advance' && $payreq->realization && $payreq->realization->realizationDetails->count() > 0) {
                    $total_release += $payreq->realization->realizationDetails->sum('amount');
                } else {
                    $total_release += $payreq->outgoings->sum('amount');
                }
            }

            return $total_release;
        });
    }

    public function statusColor($progress)
    {
        // if progress > 100 then red, if progress > 90 then yellow, else green
        if ($progress > 100) {
            return 'bg-danger';
        } elseif ($progress > 90) {
            return 'bg-warning';
        } else {
            return 'bg-success';
        }
    }

    public function update_many(Request $request)
    {
        $ids = $request->input('id', []);

        if (count($ids) > 0) {
            DB::beginTransaction();
            try {
                // Update in batches for better performance
                $chunks = array_chunk($ids, 100);
                foreach ($chunks as $chunk) {
                    Anggaran::whereIn('id', $chunk)->update(['is_active' => 0]);

                    // Clear individual caches for each updated record
                    foreach ($chunk as $id) {
                        $this->clearAnggaranCache($id);
                    }
                }

                DB::commit();

                // Clear data listing caches
                $this->clearAnggaranListCaches();

                return redirect()->back()->with('success', 'Selected records have been inactivated.');
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Error updating records: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('info', 'No records were selected.');
    }

    public function activate_many(Request $request)
    {
        $ids = $request->input('id', []);

        if (count($ids) > 0) {
            DB::beginTransaction();
            try {
                // Update in batches for better performance
                $chunks = array_chunk($ids, 100);
                foreach ($chunks as $chunk) {
                    Anggaran::whereIn('id', $chunk)->update(['is_active' => 1]);

                    // Clear individual caches for each updated record
                    foreach ($chunk as $id) {
                        $this->clearAnggaranCache($id);
                    }
                }

                DB::commit();

                // Clear data listing caches
                $this->clearAnggaranListCaches();

                return redirect()->back()->with('success', 'Selected records have been activated.');
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Error updating records: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('info', 'No records were selected.');
    }

    /**
     * Clear specific anggaran cache
     */
    private function clearAnggaranCache($id)
    {
        Cache::forget('anggaran_' . $id);
        Cache::forget('anggaran_show_' . $id);
        Cache::forget('anggaran_release_' . $id);
    }

    /**
     * Clear anggaran list caches (for data tables)
     */
    private function clearAnggaranListCaches()
    {
        // Clear all data lists caches
        $prefix = 'anggarans_data_';
        $keys = Cache::getPrefix() . $prefix . '*';

        // Using low-level cache clear by pattern if available
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags(['anggarans'])->flush();
        } else {
            // Fallback: Clear specific known caches
            $projects = Project::pluck('code')->toArray();
            $statuses = ['active', 'inactive'];

            foreach ($projects as $project) {
                foreach ($statuses as $status) {
                    Cache::forget('anggarans_data_' . $status . '_' . $project);
                }
            }
        }
    }

    /**
     * Clear all anggaran caches
     */
    private function clearAllAnggaranCaches()
    {
        // Clear anggaran list caches
        $this->clearAnggaranListCaches();

        // Clear specific cache keys related to anggarans
        $projects = Project::pluck('code')->toArray();

        // Clear project-related caches
        foreach ($projects as $project) {
            Cache::forget('periode_anggarans_' . $project);
            Cache::forget('periode_ofrs_' . $project);
        }

        // Clear other caches
        Cache::forget('projects_all');

        // Clear all anggaran detail caches
        $anggarans = Anggaran::pluck('id')->toArray();
        foreach ($anggarans as $id) {
            $this->clearAnggaranCache($id);
        }
    }
}
