<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\UserController;
use App\Models\Dokumen;
use App\Models\Pcbc;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PcbcController extends Controller
{
    protected $allowedRoles = ['admin', 'superadmin', 'cashier'];
    protected $projects = ['000H', '001H', '017C', '021C', '022C', '023C'];
    protected $years = ['2024', '2025'];
    protected $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

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

    public function getProjects($userRoles)
    {
        if (array_intersect($this->allowedRoles, $userRoles)) {
            return $this->projects;
        } else {
            return explode(',', auth()->user()->project);
        }
    }

    public function check_pcbc_files($year)
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
        $dokumen = Dokumen::find($id);

        if ($request->hasFile('attachment')) {
            // Delete the old file
            if (file_exists(public_path('dokumens/' . $dokumen->filename1))) {
                unlink(public_path('dokumens/' . $dokumen->filename1));
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

    private function uploadFile($file)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = 'pcbc' . rand() . '.' . $extension;
        $file->move(public_path('dokumens'), $filename);
        return $filename;
    }

    public function destroy($id)
    {
        $dokumen = Dokumen::find($id);
        $dokumen->delete();

        return redirect()->back()->with('success', 'File deleted successfully.');
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $query = Dokumen::where('type', 'pcbc')->orderBy('dokumen_date', 'desc');

        if (!array_intersect($userRoles, ['superadmin', 'admin', 'cashier'])) {
            $query->where('project', auth()->user()->project);
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

    public function your_data()
    {
        $userId = auth()->id();
        $query = Pcbc::where('created_by', $userId)->orderBy('pcbc_date', 'desc');

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

    public function store(Request $request)
    {
        $this->validatePcbcRequest($request);

        try {
            DB::beginTransaction();

            $nomor = $this->generatePcbcNumber($request->project);
            $fisik_amount = $this->calculateFisikAmount($request);

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

    private function validatePcbcRequest(Request $request)
    {
        return $request->validate([
            'pcbc_date' => 'required|date',
            'project' => 'required|string',
            'kertas_100rb' => 'nullable|integer|min:0',
            'kertas_50rb' => 'nullable|integer|min:0',
            'kertas_20rb' => 'nullable|integer|min:0',
            'kertas_10rb' => 'nullable|integer|min:0',
            'kertas_5rb' => 'nullable|integer|min:0',
            'kertas_2rb' => 'nullable|integer|min:0',
            'kertas_1rb' => 'nullable|integer|min:0',
            'kertas_500' => 'nullable|integer|min:0',
            'kertas_100' => 'nullable|integer|min:0',
            'logam_1rb' => 'nullable|integer|min:0',
            'logam_500' => 'nullable|integer|min:0',
            'logam_200' => 'nullable|integer|min:0',
            'logam_100' => 'nullable|integer|min:0',
            'logam_50' => 'nullable|integer|min:0',
            'logam_25' => 'nullable|integer|min:0',
            'system_amount' => 'nullable|string',
            'sap_amount' => 'nullable|string',
            'pemeriksa1' => 'required|string',
            'pemeriksa2' => 'nullable|string',
            'approved_by' => 'nullable|string',
        ]);
    }

    private function generatePcbcNumber($project)
    {
        return app(DocumentNumberController::class)->generate_document_number('pcbc', $project);
    }

    private function calculateFisikAmount(Request $request)
    {
        return ($request->kertas_100rb * 100000) +
            ($request->kertas_50rb * 50000) +
            ($request->kertas_20rb * 20000) +
            ($request->kertas_10rb * 10000) +
            ($request->kertas_5rb * 5000) +
            ($request->kertas_2rb * 2000) +
            ($request->kertas_1rb * 1000) +
            ($request->kertas_500 * 500) +
            ($request->kertas_100 * 100) +
            ($request->logam_1rb * 1000) +
            ($request->logam_500 * 500) +
            ($request->logam_200 * 200) +
            ($request->logam_100 * 100) +
            ($request->logam_50 * 50) +
            ($request->logam_25 * 25);
    }

    private function fillBasicInfo(Pcbc $pcbc, Request $request, $nomor)
    {
        $pcbc->nomor = $nomor;
        $pcbc->pcbc_date = $request->pcbc_date;
        $pcbc->created_by = auth()->id();
        $pcbc->project = $request->project;
    }

    private function fillDenominations(Pcbc $pcbc, Request $request)
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

    private function fillAmounts(Pcbc $pcbc, Request $request, $fisik_amount)
    {
        $pcbc->system_amount = floatval(str_replace(',', '.', str_replace('.', '', $request->system_amount)));
        $pcbc->fisik_amount = $fisik_amount;
        $pcbc->sap_amount = floatval(str_replace(',', '.', str_replace('.', '', $request->sap_amount)));
    }

    private function fillApprovalInfo(Pcbc $pcbc, Request $request)
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

    public function update_pcbc(Request $request, $id)
    {
        $request->validate([
            'pcbc_date' => 'required|date',
            'kertas_100rb' => 'nullable|integer|min:0',
            'kertas_50rb' => 'nullable|integer|min:0',
            'kertas_20rb' => 'nullable|integer|min:0',
            'kertas_10rb' => 'nullable|integer|min:0',
            'kertas_5rb' => 'nullable|integer|min:0',
            'kertas_2rb' => 'nullable|integer|min:0',
            'kertas_1rb' => 'nullable|integer|min:0',
            'kertas_500' => 'nullable|integer|min:0',
            'kertas_100' => 'nullable|integer|min:0',
            'logam_1rb' => 'nullable|integer|min:0',
            'logam_500' => 'nullable|integer|min:0',
            'logam_200' => 'nullable|integer|min:0',
            'logam_100' => 'nullable|integer|min:0',
            'logam_50' => 'nullable|integer|min:0',
            'logam_25' => 'nullable|integer|min:0',
            'system_amount' => 'nullable|string',
            'sap_amount' => 'nullable|string',
            'pemeriksa1' => 'required|string',
            'approved_by' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $pcbc = Pcbc::findOrFail($id);

            // Update basic info
            $pcbc->pcbc_date = $request->pcbc_date;

            // Update denominations
            $this->fillDenominations($pcbc, $request);

            // Calculate physical amount
            $fisik_amount = $this->calculatePhysicalAmount($request);

            // Update amounts
            $this->fillAmounts($pcbc, $request, $fisik_amount);

            // Update approval info
            $pcbc->pemeriksa1 = $request->pemeriksa1;
            $pcbc->approved_by = $request->approved_by;

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

    private function calculatePhysicalAmount(Request $request)
    {
        return ($request->kertas_100rb * 100000) +
            ($request->kertas_50rb * 50000) +
            ($request->kertas_20rb * 20000) +
            ($request->kertas_10rb * 10000) +
            ($request->kertas_5rb * 5000) +
            ($request->kertas_2rb * 2000) +
            ($request->kertas_1rb * 1000) +
            ($request->kertas_500 * 500) +
            ($request->kertas_100 * 100) +
            ($request->logam_1rb * 1000) +
            ($request->logam_500 * 500) +
            ($request->logam_200 * 200) +
            ($request->logam_100 * 100) +
            ($request->logam_50 * 50) +
            ($request->logam_25 * 25);
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
        $pcbc->delete();

        return redirect()->back()->with('success', 'PCBC deleted successfully');
    }
}
