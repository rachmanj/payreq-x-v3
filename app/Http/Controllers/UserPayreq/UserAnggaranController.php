<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\UserController;
use App\Http\Requests\UserPayreq\ProcessAnggaranRequest;
use App\Models\Anggaran;
use App\Models\PeriodeAnggaran;
use App\Models\Project;
use App\Services\AnggaranReleaseService;
use Illuminate\Support\Str;

class UserAnggaranController extends Controller
{
    public function __construct(
        protected AnggaranReleaseService $releaseService
    ) {}

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

    public function proses(ProcessAnggaranRequest $request)
    {
        $response = null;
        if (in_array($request->button_type, ['create', 'create_submit'], true)) {
            $response = $this->store($request);
        } elseif (in_array($request->button_type, ['edit', 'edit_submit'], true)) {
            $response = $this->update($request);
        }

        if ($response) {
            if (in_array($request->button_type, ['create_submit', 'edit_submit'], true)) {
                if ($this->performApprovalSubmit($response->id)) {
                    return redirect()->route('user-payreqs.anggarans.index')->with('success', 'Anggaran berhasil diajukan');
                }

                return redirect()->route('user-payreqs.anggarans.index')->with('error', 'Anggaran gagal disubmit. Hubungi Administrator');
            }

            return redirect()->route('user-payreqs.anggarans.index')->with('success', 'Anggaran berhasil disimpan sebagai draft');
        }

        return redirect()->back()->with('error', 'There is an error in the form');
    }

    protected function performApprovalSubmit(int $id): bool
    {
        $response = app(ApprovalPlanController::class)->create_approval_plan('rab', $id);

        if (! $response) {
            return false;
        }

        $anggaran = Anggaran::find($id);
        $anggaran->update([
            'status' => 'submitted',
        ]);

        return true;
    }

    public function store(ProcessAnggaranRequest $data): Anggaran
    {
        $filename = $this->handleFileUpload($data);

        return Anggaran::create([
            'nomor' => $data->nomor,
            'rab_no' => $data->rab_no,
            'date' => $data->date ?: date('Y-m-d'),
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
    }

    public function edit($id)
    {
        $anggaran = Anggaran::findOrFail($id);
        $this->authorize('editThroughPayreq', $anggaran);

        $projects = Project::orderBy('code', 'asc')->get();

        $periode_anggarans = PeriodeAnggaran::orderBy('periode', 'asc')
            ->where('periode_type', 'anggaran')
            ->where('project', auth()->user()->project)
            ->where('is_active', 1)
            ->get();

        if ($anggaran->filename) {
            $origin_filename = preg_replace('/^rab_\d+_/', '', $anggaran->filename);
        } else {
            $origin_filename = null;
        }

        return view('user-payreqs.anggarans.edit', compact('anggaran', 'projects', 'periode_anggarans', 'origin_filename'));
    }

    public function update(ProcessAnggaranRequest $data): Anggaran
    {
        $anggaran = Anggaran::findOrFail($data->anggaran_id);
        $this->authorize('editThroughPayreq', $anggaran);

        $anggaran->update([
            'rab_no' => $data->rab_no,
            'date' => $data->date ?: date('Y-m-d'),
            'description' => $data->description,
            'rab_project' => $data->project,
            'type' => $data->rab_type,
            'amount' => $data->amount,
            'periode_anggaran' => $data->periode_anggaran,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
        ]);

        if ($data->file('file_upload')) {
            $filename = $this->handleFileUpload($data);
            $anggaran->update(['filename' => $filename]);
        }

        return $anggaran;
    }

    private function handleFileUpload(ProcessAnggaranRequest $data): ?string
    {
        if (! $data->file('file_upload')) {
            return null;
        }

        $file = $data->file('file_upload');
        $filename = 'rab_'.Str::uuid()->toString().'_'.$file->getClientOriginalName();
        $file->move(public_path('file_upload'), $filename);

        return $filename;
    }

    public function show($id)
    {
        $anggaran = Anggaran::findOrFail($id);
        $this->authorize('view', $anggaran);

        $summary = $this->releaseService->progressSummary($anggaran);
        $progres_persen = $summary['persen'];
        $total_release = $summary['amount'];
        $statusColor = $this->statusColor((float) $progres_persen);

        return view('user-payreqs.anggarans.show', compact('anggaran', 'progres_persen', 'statusColor', 'total_release'));
    }

    public function data()
    {
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $anggarans = Anggaran::orderBy('date', 'desc')
                ->limit(300)
                ->get();
        } elseif (in_array('cashier', $userRoles, true)) {
            $anggarans = Anggaran::where('project', auth()->user()->project)
                ->orderBy('date', 'desc')
                ->limit(300)
                ->get();
        } else {
            $anggarans = $this->getRabsData();
        }

        return datatables()->of($anggarans)
            ->editColumn('nomor', function ($anggaran) {
                $nomor = '<a href="'.route('user-payreqs.anggarans.show', $anggaran->id).'"><small>'.$anggaran->nomor.'</small></a>';
                $rab_no = $anggaran->rab_no ? '<a href="'.route('user-payreqs.anggarans.show', $anggaran->id).'"><small>'.$anggaran->rab_no.' <br> '.date('d-M-Y', strtotime((string) $anggaran->date)).'</small></a>' : '-';

                return $anggaran->rab_no ? $nomor.'<br>'.$rab_no : $nomor.'<br><small>'.date('d-M-Y', strtotime((string) $anggaran->date)).'</small>';
            })
            ->editColumn('description', function ($anggaran) {
                return '<small>'.$anggaran->description.'</small>';
            })
            ->editColumn('budget', function ($anggaran) {
                return number_format((float) $anggaran->amount, 2);
            })
            ->editColumn('realisasi', function ($anggaran) {
                return number_format((float) $anggaran->balance, 2);
            })
            ->addColumn('progres', function ($anggaran) {
                $progres = $anggaran->persen ? $anggaran->persen : 0;
                $statusColor = $this->statusColor((float) $progres);

                return '<div class="text-center"><small>'.$progres.'%</small>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar progress-bar-striped '.$statusColor.'" role="progressbar" style="width: '.$progres.'%" aria-valuenow="'.$progres.'" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>';
            })
            ->editColumn('rab_project', function ($anggaran) {
                return $anggaran->rab_project.'<br><small>'.ucfirst((string) $anggaran->usage).'</small>';
            })
            ->addIndexColumn()
            ->addColumn('action', 'user-payreqs.anggarans.action')
            ->rawColumns(['action', 'nomor', 'description', 'progres', 'rab_project'])
            ->toJson();
    }

    public function payreqs_data($anggaran_id)
    {
        $anggaran = Anggaran::findOrFail($anggaran_id);
        $this->authorize('view', $anggaran);

        $payreqs = $this->progress($anggaran_id)['payreqs'];

        return datatables()->of($payreqs)
            ->editColumn('approved_at', function ($payreq) {
                return $payreq->approved_at ? date('d-M-Y', strtotime((string) $payreq->approved_at)) : '-';
            })
            ->addColumn('employee', function ($payreq) {
                return $payreq->requestor->name;
            })
            ->editColumn('amount', function ($payreq) {
                return number_format((float) $payreq->amount, 2);
            })
            ->addIndexColumn()
            ->rawColumns(['nomor'])
            ->toJson();
    }

    public function progress(int|string $id): array
    {
        $anggaran = Anggaran::findOrFail($id);

        return $this->releaseService->progressSummary($anggaran);
    }

    public function statusColor(float $progress): string
    {
        if ($progress > 100) {
            return 'bg-danger';
        }
        if ($progress > 90) {
            return 'bg-warning';
        }

        return 'bg-success';
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

        return $project_rabs->merge($department_rabs)->merge($user_rabs);
    }

    public function getRabsData()
    {
        $project_rabs = Anggaran::where('usage', 'project')
            ->where('project', auth()->user()->project)
            ->whereIn('status', ['approved', 'close'])
            ->where('is_active', 1)
            ->orderBy('date', 'desc')
            ->get();

        $department_rabs = Anggaran::where('usage', 'department')
            ->where('department_id', auth()->user()->department_id)
            ->whereIn('status', ['approved', 'close'])
            ->where('is_active', 1)
            ->orderBy('date', 'desc')
            ->get();

        $user_rabs = Anggaran::where('usage', 'user')
            ->where('created_by', auth()->user()->id)
            ->orderBy('date', 'desc')
            ->limit(300)
            ->get();

        return $project_rabs->merge($department_rabs)->merge($user_rabs);
    }
}
