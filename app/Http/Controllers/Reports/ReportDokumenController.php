<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Dokumen;
use App\Models\Giro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportDokumenController extends Controller
{
    public function index()
    {
        $page_type = request()->query('type');
        $year = request()->query('year');
        // return $year;

        if ($page_type == 'koran') {

            $korans = $this->check_koran_files($year);

            return view('reports.dokumen.index', compact('korans', 'year'));
        } else {
            return view('reports.dokumen.pcbc');
        }
    }

    public function upload_page()
    {
        return redirect()->route('cashier.dokumen.index');
    }

    public function check_koran_files($year)
    {
        $giros = $this->giroList();
        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $result = [];

        $year_data = [];

        foreach ($giros as $giro) {
            $giro_data = [];

            $korans = Dokumen::where('type', 'koran')
                ->where('giro_id', $giro->id)
                ->whereYear('periode', $year)
                ->whereIn(DB::raw('LPAD(MONTH(periode), 2, "0")'), $months)
                ->get()
                ->keyBy(function ($item) {
                    return \Carbon\Carbon::parse($item->periode)->format('m');
                });

            foreach ($months as $month) {
                $koran = $korans->get($month);
                $giro_data[] = [
                    'month' => $month,
                    'status' => $koran && $koran->filename1 !== null ? true : false,
                    'filename1' => $koran && $koran->filename1 ? $koran->filename1 : null,
                ];
            }

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

        $query = Giro::select('id', 'acc_no', 'acc_name', 'project')
            ->whereNotIn('id', $giroIdsExlcude);

        if (!array_intersect(['admin', 'superadmin', 'cashier', 'approver_bo', 'cashier_bo'], $userRoles)) {
            $query->where('project', auth()->user()->project);
        }

        return $query->get();
    }
}
