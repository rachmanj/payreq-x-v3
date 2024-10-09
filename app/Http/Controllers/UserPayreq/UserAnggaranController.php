<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\UserController;
use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\PeriodeAnggaran;
use App\Models\Project;
use Illuminate\Http\Request;

class UserAnggaranController extends Controller
{
    public function index()
    {
        return view('user-payreqs.anggarans.index');
    }

    public function create()
    {
        $nomor = app(DocumentNumberController::class)->generate_draft_document_number(auth()->user()->project);
        $projects = Project::orderBy('code', 'asc')->get();
        $periode_anggarans = PeriodeAnggaran::orderBy('periode', 'asc')
            ->where('periode_type', 'anggaran')
            ->where('project', auth()->user()->project)
            ->where('is_active', 1)
            ->get();

        return view('user-payreqs.anggarans.create', compact('projects', 'periode_anggarans', 'nomor'));
    }

    public function proses(Request $request)
    {
        if ($request->button_type === 'create') {
            $response = $this->store($request);

            if ($response) {
                return redirect()->route('user-payreqs.anggarans.index')->with('success', 'Anggaran berhasil dibuat sebagai draft');
            } else {
                return redirect()->back()->with('error', 'There is an error in the form');
            }
        } elseif ($request->button_type === 'edit') {
            $response = $this->update($request);

            if ($response) {
                return redirect()->route('user-payreqs.anggarans.index')->with('success', 'Anggaran berhasil diupdate sebagai draft');
            } else {
                return redirect()->back()->with('error', 'There is an error in the form');
            }
        } elseif ($request->button_type === 'create_submit') {
            $response = $this->store($request);

            if ($response) {
                $this->submit($response->id);
                return redirect()->route('user-payreqs.anggarans.index')->with('success', 'Anggaran berhasil diajukan');
            } else {
                return redirect()->back()->with('error', 'There is an error in the form');
            }
        } elseif ($request->button_type === 'edit_submit') {
            $response = $this->update($request);

            if ($response) {
                $this->submit($response->id);
                return redirect()->route('user-payreqs.anggarans.index')->with('success', 'Anggaran berhasil diajukan');
            } else {
                return redirect()->back()->with('error', 'There is an error in the form');
            }
        } else {
            return redirect()->back()->with('error', 'There is an error in the form');
        }
    }

    public function submit($id)
    {
        $response = app(ApprovalPlanController::class)->create_approval_plan('rab', $id);

        if ($response) {
            $anggaran = Anggaran::find($id);
            $anggaran->update([
                'status' => 'submitted',
            ]);
            return redirect()->route('user-payreqs.anggarans.index')->with('success', 'Anggaran berhasil diajukan');
        } else {
            return redirect()->route('user-payreqs.anggarans.index')->with('error', 'Anggaran gagal disubmit. Hubungi Administrator');
        }
    }

    public function store($data)
    {
        $data->validate([
            'description' => 'required',
            'amount' => 'required',
        ]);

        if ($data->file_upload) {
            $file = $data->file('file_upload');
            $filename = 'rab_' . rand() . '_' . $file->getClientOriginalName();
            $file->move(public_path('file_upload'), $filename);
        } else {
            $filename = null;
        }

        $anggaran = Anggaran::create([
            'nomor' => $data->nomor,
            'rab_no' => $data->rab_no,
            'date' => $data->date ? $data->date : date('Y-m-d'),
            'description' => $data->description,
            'project' => auth()->user()->project,
            'rab_project' => $data->project,
            'department_id' => auth()->user()->department_id,
            'type' => $data->rab_type,
            'amount' => $data->amount,
            'periode_anggaran' => $data->periode_anggaran,
            'filename' => $filename,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'created_by' => auth()->user()->id,
        ]);

        return $anggaran;
    }

    public function update($data)
    {
        $anggaran = Anggaran::find($data->anggaran_id);

        $anggaran->update([
            'rab_no' => $data->rab_no,
            'date' => $data->date ? $data->date : date('Y-m-d'),
            'description' => $data->description,
            'rab_project' => $data->project,
            'type' => $data->rab_type,
            'amount' => $data->amount,
            'periode_anggaran' => $data->periode_anggaran,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
        ]);

        if ($data->file_upload) {
            $file = $data->file('file_upload');
            $filename = 'rab_' . rand() . '_' . $file->getClientOriginalName();
            $file->move(public_path('file_upload'), $filename);
            $anggaran->update([
                'filename' => $filename,
            ]);
        }

        return $anggaran;
    }

    public function edit($id)
    {
        $anggaran = Anggaran::find($id);
        $projects = Project::orderBy('code', 'asc')->get();

        $periode_anggarans = PeriodeAnggaran::orderBy('periode', 'asc')
            ->where('periode_type', 'anggaran')
            ->where('project', auth()->user()->project)
            ->where('is_active', 1)
            ->get();

        // rab_45654654654_filename.pdf convert this to filename.pdf
        if ($anggaran->filename) {
            $origin_filename = preg_replace('/^rab_\d+_/', '', $anggaran->filename); // this to convert to original filename
        } else {
            $origin_filename = null;
        }

        return view('user-payreqs.anggarans.edit', compact('anggaran', 'projects', 'periode_anggarans', 'origin_filename'));
    }

    public function show($id)
    {
        $anggaran = Anggaran::find($id);
        $progres_persen = $anggaran->persen;
        $total_release = $anggaran->balance;
        $statusColor = $this->statusColor($progres_persen);

        return view('user-payreqs.anggarans.show', compact('anggaran', 'progres_persen', 'statusColor', 'total_release'));
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $anggarans = Anggaran::orderBy('date', 'desc')
                ->limit(300)
                ->get();
        } elseif (in_array('cashier', $userRoles)) {
            $anggarans = Anggaran::where('project', auth()->user()->project)
                ->orderBy('date', 'desc')
                ->limit(300)
                ->get();
        } else {
            $anggarans = $this->getRabsData();
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
            ->addIndexColumn()
            ->addColumn('action', 'user-payreqs.anggarans.action')
            ->rawColumns(['action', 'nomor', 'description', 'progres', 'rab_project'])
            ->toJson();
    }

    public function payreqs_data($anggaran_id)
    {
        $payreqs = $this->progress($anggaran_id)['payreqs'];

        return datatables()->of($payreqs)
            ->editColumn('approved_at', function ($payreq) {
                return $payreq->approved_at ? date('d-M-Y', strtotime($payreq->approved_at)) : '-';
            })
            ->addColumn('employee', function ($payreq) {
                return $payreq->requestor->name;
            })
            ->editColumn('amount', function ($payreq) {
                return number_format($payreq->amount, 2);
            })
            ->addIndexColumn()
            ->rawColumns(['nomor'])
            ->toJson();
    }

    public function progress($id)
    {
        $anggaran = Anggaran::find($id);

        // cek payreqs dgn status paid
        if ($anggaran->old_rab_id !== null) {
            $payreqs = Payreq::where('rab_id', $anggaran->old_rab_id)
                ->whereHas('outgoings')
                ->get();
        } else {
            $payreqs = Payreq::where('rab_id', $anggaran->id)
                ->whereHas('outgoings')
                ->get();
        }

        $payreq_list = [];
        // if payreq has realization then payreq->nomor = realization->nomor
        foreach ($payreqs as $payreq) {
            if ($payreq->type === 'advance') {
                if ($payreq->realization && $payreq->realization->realizationDetails->count() > 0) {
                    $payreq->nomor = $payreq->realization->nomor;
                    $payreq->amount = $payreq->realization->realizationDetails->sum('amount');
                    $payreq_list[] = $payreq;
                } else {
                    $payreq->amount = $payreq->outgoings->sum('amount');
                    $payreq_list[] = $payreq;
                }
            } else {
                $payreq->amount = $payreq->outgoings->sum('amount');
                $payreq_list[] = $payreq;
            }
        }

        // cek payreq yg status paid dan sum the amount, jika payreq status realisasi maka hitung realization_detail amount nya
        $total_release = 0;
        foreach ($payreqs as $payreq) {
            // if payreq has realization then sum the realization_detail amount
            if ($payreq->realization && $payreq->realization->realizationDetails->count() > 0) {
                $total_release += $payreq->realization->realizationDetails->sum('amount');
                $payreq->cek = 'ada realisasi';
            } else {
                $total_release += $payreq->outgoings->sum('amount');
                $payreq->cek = 'tidak ada realisasi';
            }
        }

        $progress = [
            'amount' => $total_release,
            'persen' => $total_release > 0 ? number_format((($total_release / $anggaran->amount) * 100), 2) : 0,
            'payreqs' => $payreq_list,
            'payreq_count' => $payreqs->count(),
        ];

        return $progress;
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

    public function getAvailableRabs()
    {
        $project_rabs = Anggaran::where('usage', 'project')
            ->where('project', auth()->user()->project)
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->get();

        $department_rabs = Anggaran::where('usage', 'department')
            ->where('department_id', auth()->user()->department_id)
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->get();

        $user_rabs = Anggaran::where('usage', 'user')
            ->where('created_by', auth()->user()->id)
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->get();

        $rabs = $project_rabs->merge($department_rabs)->merge($user_rabs);

        return $rabs;
    }

    public function getRabsData()
    {
        $project_rabs = Anggaran::where('usage', 'project')
            ->where('project', auth()->user()->project)
            ->whereIn('status', ['approved', 'close'])
            ->where('is_active', 1)
            ->get();

        $department_rabs = Anggaran::where('usage', 'department')
            ->where('department_id', auth()->user()->department_id)
            ->whereIn('status', ['approved', 'close'])
            ->where('is_active', 1)
            ->get();

        $user_rabs = Anggaran::where('usage', 'user')
            ->where('created_by', auth()->user()->id)
            // ->where('is_active', 1)
            ->limit(300)
            ->get();

        $rabs = $project_rabs->merge($department_rabs)->merge($user_rabs);

        return $rabs;
    }
}
