<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Dokumen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
