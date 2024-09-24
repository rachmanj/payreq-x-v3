<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPayreq\UserAnggaranController;
use App\Models\Anggaran;
use App\Models\PeriodeAnggaran;
use App\Models\Project;
use Illuminate\Http\Request;

class AnggaranController extends Controller
{
    public function index()
    {
        return view('reports.anggaran.index');
    }

    public function edit($id)
    {
        $anggaran = Anggaran::find($id);
        $projects = Project::orderBy('code', 'asc')->get();

        $periode_anggarans = PeriodeAnggaran::orderBy('periode', 'asc')
            ->where('periode_type', 'anggaran')
            ->where('project', auth()->user()->project)
            // ->where('is_active', 1)
            ->get();

        $periode_ofrs = PeriodeAnggaran::orderBy('periode', 'asc')
            ->where('periode_type', 'ofr')
            ->where('project', auth()->user()->project)
            // ->where('is_active', 1)
            ->get();

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

        return view('reports.anggaran.index');
    }

    public function show($id)
    {
        $anggaran = Anggaran::find($id);
        $progres_persen = app(UserAnggaranController::class)->progress($anggaran->id)['persen'];
        $total_release = app(UserAnggaranController::class)->progress($anggaran->id)['amount'];
        $statusColor = app(UserAnggaranController::class)->statusColor($progres_persen);

        return view('reports.anggaran.show', compact('anggaran', 'progres_persen', 'statusColor', 'total_release'));
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $anggarans = Anggaran::orderBy('date', 'desc')
                ->where('status', 'approved')
                ->limit(300)
                ->get();
        } else {
            $anggarans = Anggaran::where('project', auth()->user()->project)
                ->orderBy('date', 'desc')
                ->where('status', 'approved')
                ->limit(300)
                ->get();
        }

        return datatables()->of($anggarans)
            ->editColumn('nomor', function ($anggaran) {
                $nomor = '<small>' . $anggaran->nomor . '</small>';
                $rab_no = $anggaran->rab_no ? '<small>' . $anggaran->rab_no . ' <br></small>' : '';
                return '<a href="' . route('reports.anggaran.show', $anggaran->id) . '">' . $nomor . '<br>' . $rab_no . '<small>' . date('d-M-Y', strtotime($anggaran->date)) . '</small></a>';
            })
            ->editColumn('description', function ($anggaran) {
                return '<small>' . $anggaran->description . '</small>';
            })
            ->editColumn('budget', function ($anggaran) {
                return number_format($anggaran->amount, 2);
            })
            ->addColumn('periode', function ($anggaran) {
                $pa = $anggaran->periode_anggaran ? date('M Y', strtotime($anggaran->periode_anggaran)) : '-';
                $radio = $anggaran->is_active == 1 ? '<span class="badge bg-success">1</span>' : '<span class="badge bg-danger">0</span>';
                return '<small>' . $pa . '<br>' . date('M Y', strtotime($anggaran->periode_ofr)) . '</small>' . '<br>' . $radio;
            })
            ->editColumn('rab_project', function ($anggaran) {
                $usage = $anggaran->usage == 'department' ? $anggaran->createdBy->department->akronim : ucfirst($anggaran->usage);
                $content = '<small>' . $anggaran->rab_project . '<br>' . $usage . '<br>' . ucfirst($anggaran->type) . '</small>';
                return $content;
            })
            ->addColumn('creator', function ($anggaran) {
                $name = explode(' ', $anggaran->createdBy->name);
                return '<small>' . $name[0] . '</small>';
            })
            ->addIndexColumn()
            ->addColumn('action', 'reports.anggaran.action')
            ->rawColumns(['action', 'nomor', 'description', 'rab_project', 'periode', 'radio', 'creator'])
            ->toJson();
    }

    public function data_full()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $anggarans = Anggaran::orderBy('date', 'desc')
                ->limit(300)
                ->get();
        } else {
            $anggarans = Anggaran::where('project', auth()->user()->project)
                ->orderBy('date', 'desc')
                ->limit(300)
                ->get();
        }

        return datatables()->of($anggarans)
            ->editColumn('nomor', function ($anggaran) {
                $nomor = '<a href="' . route('reports.anggaran.show', $anggaran->id) . '"><small>' . $anggaran->nomor . '</small></a>';
                $rab_no = $anggaran->rab_no ? '<a href="' . route('reports.anggaran.show', $anggaran->id) . '"><small>' . $anggaran->rab_no . ' <br> ' . date('d-M-Y', strtotime($anggaran->date)) . '</small></a>' : '-';
                return $anggaran->rab_no ? $nomor . '<br>' . $rab_no : $nomor . '<br><small>' . date('d-M-Y', strtotime($anggaran->date)) . '</small>';
            })
            ->editColumn('description', function ($anggaran) {
                return '<small>' . $anggaran->description . '</small>';
            })
            ->editColumn('budget', function ($anggaran) {
                return number_format($anggaran->amount, 2);
            })
            ->editColumn('realisasi', function ($anggaran) {
                return number_format(app(UserAnggaranController::class)->progress($anggaran->id)['amount'], 2);
            })
            ->addColumn('progres', function ($anggaran) {
                $progres = app(UserAnggaranController::class)->progress($anggaran->id)['persen'];
                $statusColor = app(UserAnggaranController::class)->statusColor($progres);
                $progres_bar = '<div class="progress" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped ' . $statusColor . '" role="progressbar" style="width: ' . $progres . '%" aria-valuenow="' . $progres . '" aria-valuemin="0" aria-valuemax="100">' . $progres . '%</div>
                                </div>';
                if ($anggaran->status === 'approved') {
                    return $progres > 0 ? $progres_bar : 'approved';
                } else {
                    return $anggaran->status;
                }
            })
            ->editColumn('rab_project', function ($anggaran) {
                $content = $anggaran->rab_project . '<br><small>' . ucfirst($anggaran->usage) . '</small>';
                return $content;
            })
            ->addIndexColumn()
            ->addColumn('action', 'reports.anggarans.action')
            ->rawColumns(['action', 'nomor', 'description', 'progres', 'rab_project'])
            ->toJson();
    }
}
