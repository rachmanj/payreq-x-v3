<?php

namespace App\Http\Controllers\Cashier;

use App\Exports\PcbcExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\UserController;
use App\Http\Requests\StorePcbcRequest;
use App\Http\Requests\UpdatePcbcRequest;
use App\Models\Dokumen;
use App\Models\Pcbc;
use App\Models\Project;
use App\Services\PcbcService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PcbcController extends Controller
{
    protected $allowedRoles = ['admin', 'superadmin', 'cashier'];
    protected $projects;
    protected $years;
    protected $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
    protected $pcbcService;

    public function __construct(PcbcService $pcbcService)
    {
        $this->projects = Project::orderBy('code')->pluck('code');
        $this->years = $this->getAvailableYears();
        $this->pcbcService = $pcbcService;
    }

    protected function getAvailableYears(): array
    {
        $currentYear = (int) date('Y');
        return array_map('strval', range($currentYear - 2, $currentYear + 1));
    }

    protected function getProjects(array $userRoles): \Illuminate\Support\Collection
    {
        if (array_intersect($this->allowedRoles, $userRoles)) {
            return $this->projects;
        } else {
            return collect(explode(',', auth()->user()->project));
        }
    }

    public function index(Request $request)
    {
        $page = $request->query('page', 'dashboard');
        $userRoles = app(UserController::class)->getUserRoles();
        $months = $this->months;

        $views = [
            'dashboard' => 'cashier.pcbc.dashboard',
            'upload' => 'cashier.pcbc.upload',
            'list' => 'cashier.pcbc.list',
        ];

        if ($page === 'dashboard') {
            $year = request()->query('year', date('Y'));
            $data = $this->check_pcbc_files($year);

            return view($views['dashboard'], compact('data', 'year', 'months'));
        }

        return view($views[$page]);
    }


    public function check_pcbc_files(string $year): array
    {
        $projects = $this->getProjects(app(UserController::class)->getUserRoles());
        $months = $this->months;
        $result = [];

        foreach ($projects as $project) {
            $pcbcs = Dokumen::where('type', 'pcbc')
                ->where('project', $project)
                ->whereYear('dokumen_date', $year)
                ->whereIn(DB::raw('LPAD(MONTH(dokumen_date), 2, "0")'), $months)
                ->get()
                ->groupBy(function ($item) {
                    return \Carbon\Carbon::parse($item->dokumen_date)->format('m');
                });

            $months_data = array_map(function ($month) use ($pcbcs) {
                $pcbc = $pcbcs->get($month);

                return [
                    'month' => $month,
                    'month_name' => \Carbon\Carbon::create()->month($month)->format('F'),
                    'total_files' => $pcbc ? $pcbc->count() : 0,
                    'files' => $pcbc ? $pcbc->map(function ($file) {
                        return [
                            'filename' => $file->filename1,
                            'document_date' => $file->dokumen_date,
                        ];
                    })->toArray() : [],
                ];
            }, $months);

            $result[] = [
                'project_code' => $project,
                'months_data' => $months_data,
            ];
        }

        return [
            'year' => $year,
            'project_data' => $result,
        ];
    }

    public function upload(Request $request)
    {
        $this->validate($request, [
            'attachment' => 'required|mimes:pdf|max:1024',
            'dokumen_date' => 'required|date',
        ]);

        $filename = $this->uploadFile($request->file('attachment'));

        Dokumen::create([
            'filename1' => $filename,
            'type' => 'pcbc',
            'project' => $request->project ? $request->project : auth()->user()->project,
            'dokumen_date' => $request->dokumen_date,
            'remarks' => $request->remarks,
            'created_by' => auth()->user()->id,
        ]);

        return redirect()->back()->with('success', 'File uploaded successfully.');
    }

    public function create()
    {
        return view('cashier.pcbc.create');
    }

    public function update(Request $request, $id)
    {
        $dokumen = Dokumen::findOrFail($id);
        
        // Authorization check
        $userRoles = app(UserController::class)->getUserRoles();
        $isAuthorized = array_intersect($this->allowedRoles, $userRoles) 
            || $dokumen->created_by === auth()->id();
        
        if (!$isAuthorized) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->hasFile('attachment')) {
            // Delete the old file
            if ($dokumen->filename1 && file_exists(public_path('dokumens/' . $dokumen->filename1))) {
                try {
                    unlink(public_path('dokumens/' . $dokumen->filename1));
                    Log::info('PCBC file replaced', [
                        'old_file' => $dokumen->filename1,
                        'user_id' => auth()->id()
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to delete old PCBC file', [
                        'file' => $dokumen->filename1,
                        'error' => $e->getMessage(),
                        'user_id' => auth()->id()
                    ]);
                }
            }

            // Upload the new file
            $filename = $this->uploadFile($request->file('attachment'));
            $dokumen->filename1 = $filename;
        }

        $dokumen->update([
            'giro_id' => $request->giro_id,
            'project' => $request->project,
            'dokumen_date' => $request->dokumen_date ? $request->dokumen_date : Carbon::parse($dokumen->dokumen_date)->format('Y-m-d'),
            'remarks' => $request->remarks,
        ]);

        return redirect()->back()->with('success', 'Record updated successfully.');
    }

    private function uploadFile($file): string
    {
        return $this->pcbcService->uploadFile($file);
    }

    public function destroy($id)
    {
        $dokumen = Dokumen::findOrFail($id);
        
        // Delete physical file
        if ($dokumen->filename1) {
            $this->pcbcService->deleteFile($dokumen->filename1);
        }
        
        $dokumen->delete();

        return redirect()->back()->with('success', 'File deleted successfully.');
    }

    public function data(Request $request)
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $query = Dokumen::where('type', 'pcbc')
            ->with('createdBy')
            ->orderBy('dokumen_date', 'desc');

        if (!array_intersect($userRoles, ['superadmin', 'admin', 'cashier'])) {
            $query->where('project', auth()->user()->project);
        }

        // Advanced filtering
        if ($request->has('project') && $request->project) {
            $query->where('project', $request->project);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('dokumen_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('dokumen_date', '<=', $request->date_to);
        }

        $dokumens = $query->get();

        return datatables()->of($dokumens)
            ->editColumn('created_by', function ($dokumen) {
                return $dokumen->created_by_name;
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.pcbc.action')
            ->rawColumns(['action'])
            ->toJson();
    }

    public function your_data(Request $request)
    {
        $userId = auth()->id();
        $query = Pcbc::where('created_by', $userId)
            ->with('createdBy')
            ->orderBy('pcbc_date', 'desc');

        // Advanced filtering
        if ($request->has('project') && $request->project) {
            $query->where('project', $request->project);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('pcbc_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('pcbc_date', '<=', $request->date_to);
        }

        if ($request->has('has_variance') && $request->has_variance == '1') {
            $query->whereRaw('ABS(system_amount - fisik_amount) > 0.01 OR ABS(sap_amount - fisik_amount) > 0.01');
        }

        if ($request->has('amount_min') && $request->amount_min) {
            $query->where('fisik_amount', '>=', $request->amount_min);
        }

        if ($request->has('amount_max') && $request->amount_max) {
            $query->where('fisik_amount', '<=', $request->amount_max);
        }

        $pcbcs = $query->get();

        return datatables()->of($pcbcs)
            ->editColumn('created_by', function ($pcbc) {
                return $pcbc->createdBy->name;
            })
            ->editColumn('pcbc_date', function ($pcbc) {
                return Carbon::parse($pcbc->pcbc_date)->format('d M Y');
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.pcbc.pcbc_action')
            ->rawColumns(['action'])
            ->toJson();
    }

    public function store(StorePcbcRequest $request)
    {
        try {
            DB::beginTransaction();

            $nomor = $this->pcbcService->generateDocumentNumber($request->project);
            $fisik_amount = $this->pcbcService->calculateFisikAmount($request);

            $pcbc = new Pcbc();
            $this->fillBasicInfo($pcbc, $request, $nomor);
            $this->fillDenominations($pcbc, $request);
            $this->fillAmounts($pcbc, $request, $fisik_amount);
            $this->fillApprovalInfo($pcbc, $request);

            $pcbc->save();

            DB::commit();

            return redirect()
                ->route('cashier.pcbc.index', ['page' => 'list'])
                ->with('success', 'PCBC created successfully with number: ' . $nomor);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('PCBC Creation Error: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error creating PCBC: ' . $e->getMessage());
        }
    }


    private function fillBasicInfo(Pcbc $pcbc, Request $request, string $nomor): void
    {
        $pcbc->nomor = $nomor;
        $pcbc->pcbc_date = $request->pcbc_date;
        $pcbc->created_by = auth()->id();
        $pcbc->project = $request->project;
    }

    private function fillDenominations(Pcbc $pcbc, Request $request): void
    {
        // Paper money
        $pcbc->kertas_100rb = $request->kertas_100rb;
        $pcbc->kertas_50rb = $request->kertas_50rb;
        $pcbc->kertas_20rb = $request->kertas_20rb;
        $pcbc->kertas_10rb = $request->kertas_10rb;
        $pcbc->kertas_5rb = $request->kertas_5rb;
        $pcbc->kertas_2rb = $request->kertas_2rb;
        $pcbc->kertas_1rb = $request->kertas_1rb;
        $pcbc->kertas_500 = $request->kertas_500;
        $pcbc->kertas_100 = $request->kertas_100;

        // Coins
        $pcbc->logam_1rb = $request->logam_1rb;
        $pcbc->logam_500 = $request->logam_500;
        $pcbc->logam_200 = $request->logam_200;
        $pcbc->logam_100 = $request->logam_100;
        $pcbc->logam_50 = $request->logam_50;
        $pcbc->logam_25 = $request->logam_25;
    }

    private function fillAmounts(Pcbc $pcbc, Request $request, float $fisik_amount): void
    {
        $pcbc->system_amount = floatval(str_replace(',', '.', str_replace('.', '', $request->system_amount)));
        $pcbc->fisik_amount = $fisik_amount;
        $pcbc->sap_amount = floatval(str_replace(',', '.', str_replace('.', '', $request->sap_amount)));
    }

    private function fillApprovalInfo(Pcbc $pcbc, Request $request): void
    {
        $pcbc->pemeriksa1 = $request->pemeriksa1;
        $pcbc->pemeriksa2 = $request->pemeriksa2;
        $pcbc->approved_by = $request->approved_by;
        $pcbc->created_by = auth()->user()->id;
    }

    public function edit($id)
    {
        $pcbc = Pcbc::findOrFail($id);

        return view('cashier.pcbc.edit', compact('pcbc'));
    }

    public function update_pcbc(UpdatePcbcRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $pcbc = Pcbc::findOrFail($id);
            
            // Authorization check
            $userRoles = app(UserController::class)->getUserRoles();
            $isAuthorized = array_intersect($this->allowedRoles, $userRoles) 
                || $pcbc->created_by === auth()->id();
            
            if (!$isAuthorized) {
                abort(403, 'Unauthorized action.');
            }

            // Update basic info
            $pcbc->pcbc_date = $request->pcbc_date;

            // Update denominations
            $this->fillDenominations($pcbc, $request);

            // Calculate physical amount
            $fisik_amount = $this->pcbcService->calculateFisikAmount($request);

            // Update amounts
            $this->fillAmounts($pcbc, $request, $fisik_amount);

            // Update approval info
            $pcbc->pemeriksa1 = $request->pemeriksa1;
            $pcbc->approved_by = $request->approved_by;
            
            // Set audit trail
            $pcbc->updated_by = auth()->id();
            $pcbc->modified_at = now();

            $pcbc->save();

            DB::commit();

            return redirect()
                ->route('cashier.pcbc.index', ['page' => 'list'])
                ->with('success', 'PCBC updated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('PCBC Update Error: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error updating PCBC: ' . $e->getMessage());
        }
    }


    public function print($id)
    {
        $pcbc = Pcbc::findOrFail($id);
        $terbilang = app(ToolController::class)->terbilang($pcbc->fisik_amount);

        return view('cashier.pcbc.print', compact('pcbc', 'terbilang'));
    }

    public function destroy_pcbc($id)
    {
        $pcbc = Pcbc::findOrFail($id);
        
        // Authorization check
        $userRoles = app(UserController::class)->getUserRoles();
        $isAuthorized = array_intersect($this->allowedRoles, $userRoles) 
            || $pcbc->created_by === auth()->id();
        
        if (!$isAuthorized) {
            abort(403, 'Unauthorized action.');
        }
        
        $pcbc->delete();

        return redirect()->back()->with('success', 'PCBC deleted successfully');
    }

    public function export(Request $request)
    {
        try {
            $query = Pcbc::query();

            // Apply filters from request
            if ($request->filled('project')) {
                $query->where('project', $request->project);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('pcbc_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('pcbc_date', '<=', $request->date_to);
            }

            if ($request->filled('has_variance') && $request->has_variance == '1') {
                $query->whereRaw('ABS(system_amount - fisik_amount) > 0.01 OR ABS(sap_amount - fisik_amount) > 0.01');
            }

            // Generate filename with timestamp
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "pcbc_export_{$timestamp}.xlsx";

            return Excel::download(new PcbcExport($query), $filename);
        } catch (\Exception $e) {
            Log::error('PCBC Export Error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to export Excel file: ' . $e->getMessage());
        }
    }
}
