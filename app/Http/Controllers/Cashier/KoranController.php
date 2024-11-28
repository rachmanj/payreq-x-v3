<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Dokumen;
use App\Models\Giro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KoranController extends Controller
{
    protected $allowedRoles = ['admin', 'superadmin', 'cashier', 'approver_bo', 'cashier_bo'];

    public function index()
    {
        $page = request()->query('page', 'dashboard');
        $userRoles = app(UserController::class)->getUserRoles();

        $giros = $this->getGiros($userRoles);

        $views = [
            'dashboard' => 'cashier.koran.dashboard',
            'upload' => 'cashier.koran.upload',
        ];

        if ($page === 'dashboard') {
            $year = request()->query('year', date('Y'));
            $korans = $this->check_koran_files($year);
            return view($views['dashboard'], compact('giros', 'year', 'korans'));
        }

        return view($views['upload'], compact('giros'));
    }

    public function upload(Request $request)
    {
        $this->validate($request, [
            'file_upload' => 'required|mimes:pdf',
        ]);

        $filename = $this->uploadFile($request->file('file_upload'));

        Dokumen::create([
            'filename1' => $filename,
            'giro_id' => $request->giro_id,
            'type' => 'koran',
            'project' => $request->project,
            'periode' => $request->periode ? $request->periode . '-01' : null,
            'remarks' => $request->remarks,
            'created_by' => auth()->user()->id,
        ]);

        return redirect()->back()->with('success', 'File uploaded successfully.');
    }

    private function getGiros($userRoles)
    {
        if (array_intersect($this->allowedRoles, $userRoles)) {
            return Giro::all();
        } else {
            return Giro::where('project', auth()->user()->project)->get();
        }
    }

    private function uploadFile($file)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = 'koran_' . rand() . '.' . $extension;
        $file->move(public_path('file_upload'), $filename);
        return $filename;
    }

    public function check_koran_files($year)
    {
        $giros = $this->getGiros(app(UserController::class)->getUserRoles());
        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $result = [];

        foreach ($giros as $giro) {
            $korans = Dokumen::where('type', 'koran')
                ->where('giro_id', $giro->id)
                ->whereYear('periode', $year)
                ->whereIn(DB::raw('LPAD(MONTH(periode), 2, "0")'), $months)
                ->get()
                ->keyBy(function ($item) {
                    return \Carbon\Carbon::parse($item->periode)->format('m');
                });

            $giro_data = array_map(function ($month) use ($korans) {
                $koran = $korans->get($month);
                return [
                    'month' => $month,
                    'status' => $koran && $koran->filename1 !== null,
                    'filename1' => $koran ? $koran->filename1 : null,
                ];
            }, $months);

            $year_data[] = [
                'giro_id' => $giro->id,
                'acc_name' => $giro->acc_no . ' - ' . $giro->acc_name . ' - ' . $giro->project,
                'data' => $giro_data,
            ];
        }

        $result[] = [
            'year' => $year,
            'giros' => $year_data,
        ];

        return $result;
    }

    public function giroList()
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $giroIdsExlcude = [];
        $allowedRoles = ['admin', 'superadmin', 'cashier', 'approver_bo', 'cashier_bo'];

        $query = Giro::select('id', 'acc_no', 'acc_name', 'project')
            ->whereNotIn('id', $giroIdsExlcude);

        if (!array_intersect($allowedRoles, $userRoles)) {
            $query->where('project', auth()->user()->project);
        }

        return $query->get();
    }

    public function data()
    {
        $dokumens = Dokumen::where('type', 'koran')->orderBy('periode', 'desc')->get();

        return datatables()->of($dokumens)
            ->editColumn('created_by', function ($dokumen) {
                return $dokumen->created_by_name;
            })
            ->addColumn('account', function ($dokumen) {
                return $dokumen->giro_id === null
                    ? '<small>No Giro Assigned</small>'
                    : '<small>' . $dokumen->giro->acc_no . ' - ' . $dokumen->giro->acc_name . '</small>';
            })
            ->addColumn('account_project', function ($dokumen) {
                return $dokumen->giro_id === null
                    ? '<small>No Project Assigned</small>'
                    : $dokumen->giro->project;
            })
            ->addColumn('reconciled', function ($dokumen) {
                return $dokumen->filename2 !== null && $dokumen->verified_by !== null
                    ? '<i class="fas fa-check" style="color: green;"></i>'
                    : '<i class="fas fa-times" style="color: red;"></i>';
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.koran.action')
            ->rawColumns(['action', 'account', 'reconciled'])
            ->toJson();
    }
}
