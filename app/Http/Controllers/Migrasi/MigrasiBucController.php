<?php

namespace App\Http\Controllers\Migrasi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\Rab;
use App\Models\RealisasiAnggaran;
use Illuminate\Http\Request;

class MigrasiBucController extends Controller
{
    public function index()
    {
        return view('migrasi.rab.index');
    }

    public function migrasi_rab()
    {
        // get rabs tobe migrated
        $rabs = Rab::where('status', 'progress')
            ->where('date', '>=', '2023-01-01')
            ->orderBy('id', 'asc')
            ->get();

        // count success and failed
        $success_count = 0;
        $failed_count = 0;

        foreach ($rabs as $rab) {

            // generate nomomr anggaran
            $nomor = app(DocumentNumberController::class)->generate_document_number('rab', auth()->user()->project);

            // create anggaran
            $anggaran = Anggaran::create([
                'nomor' => $nomor,
                'rab_no' => $rab->rab_no,
                'old_rab_id' => $rab->id,
                'date' => $rab->date,
                'description' => $rab->description,
                'project' => $rab->project_code,
                'department_id' => $rab->department_id,
                'type' => 'buc',
                'amount' => $rab->budget,
                'status' => 'approved',
                'created_by' => 23, // dncdiv
            ]);

            // count success and failed
            if ($anggaran) {
                $success_count++;
            } else {
                $failed_count++;
            }
        }

        $result = [
            'record_count' => $rabs->count(),
            'success_count' => $success_count,
            'failed_count' => $failed_count,
        ];

        return redirect()->back()->with('success', 'records: ' . $result['record_count'] . ', success: ' . $result['success_count'] . ', failed: ' . $result['failed_count']);
    }

    public function realisasi_rab()
    {
        // get anggarans
        $anggarans = Anggaran::orderBy('id', 'asc')->get();

        foreach ($anggarans as $anggaran) {
            // get payreqs where rab_id === old_rab_id
            $payreqs = Payreq::where('rab_id', $anggaran->old_rab_id)->get();

            foreach ($payreqs as $payreq) {
                RealisasiAnggaran::create([
                    'anggaran_id' => $anggaran->id,
                    'payreq_id' => $payreq->id,
                ]);
            }
        }

        return redirect()->back()->with('success', 'realisasi rab success');
    }
}
