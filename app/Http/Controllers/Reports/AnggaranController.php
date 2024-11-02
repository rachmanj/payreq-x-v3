<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPayreq\UserAnggaranController;
use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\PeriodeAnggaran;
use App\Models\Project;
use Illuminate\Http\Request;

class AnggaranController extends Controller
{
    public function index()
    {
        $status = request()->query('status');

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
        $progres_persen = $anggaran->persen;
        $total_release = $anggaran->balance;
        $statusColor = $this->statusColor($anggaran->persen);

        return view('reports.anggaran.show', compact('anggaran', 'progres_persen', 'statusColor', 'total_release'));
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $get_status = request()->query('status');

        if ($get_status == 'active') {
            $status = 1;
        } else {
            $status = 0;
        }

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $anggarans = Anggaran::orderBy('date', 'desc')
                ->whereIn('status', ['approved'])
                ->where('is_active', $status)
                ->limit(300)
                ->get();
        } else {
            $anggarans = Anggaran::where('project', auth()->user()->project)
                ->orderBy('date', 'desc')
                ->where('is_active', $status)
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
                return number_format($anggaran->balance, 2);
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
            ->rawColumns(['action', 'nomor', 'description', 'progres', 'rab_project', 'creator', 'periode'])
            ->toJson();
    }

    public function recalculate()
    {
        $anggarans = Anggaran::where('status', 'approved')
            // ->where('is_active', 1)
            ->get();

        foreach ($anggarans as $anggaran) {
            $total_release = $this->release_to_date($anggaran->id);
            $persen = $total_release > 0 ? number_format((($total_release / $anggaran->amount) * 100), 2) : 0;

            // $anggaran->persen = $persen;
            // $anggaran->balance = $total_release;

            $anggaran->update([
                'balance' => $total_release,
                'persen' => $persen,
            ]);
        }

        return redirect()->route('reports.anggaran.index')->with('success', 'Release Anggaran berhasil diupdate');
    }

    public function release_to_date($id)
    {
        $anggaran = Anggaran::find($id);

        // cek payreqs yg sudah outgoings
        $payreqs = Payreq::where('rab_id', $anggaran->id)
            ->whereHas('outgoings')
            ->with(['realization.realizationDetails', 'outgoings'])
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
}
