<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPayreq\UserAnggaranController;
use App\Models\Anggaran;
use Illuminate\Http\Request;

class AnggaranController extends Controller
{
    public function index()
    {
        return view('reports.anggaran.index');
    }

    public function data()
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
                $nomor = '<a href="' . route('user-payreqs.anggarans.show', $anggaran->id) . '"><small>' . $anggaran->nomor . '</small></a>';
                $rab_no = $anggaran->rab_no ? '<a href="' . route('user-payreqs.anggarans.show', $anggaran->id) . '"><small>' . $anggaran->rab_no . ' <br> ' . date('d-M-Y', strtotime($anggaran->date)) . '</small></a>' : '-';
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
