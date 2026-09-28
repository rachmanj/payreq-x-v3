<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Http\Requests\StoreBapsbRequest;
use App\Http\Requests\UpdateBapsbRequest;
use App\Models\Bapsb;
use App\Models\BapsbLine;
use App\Models\Bilyet;
use App\Models\Dokumen;
use App\Models\Project;
use App\Services\BapsbComplianceService;
use App\Services\BapsbService;
use App\Services\PcbcService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class BapsbController extends Controller
{
    protected array $allowedRoles = ['admin', 'superadmin', 'cashier'];

    public function __construct(
        protected BapsbService $bapsbService,
        protected BapsbComplianceService $complianceService,
        protected PcbcService $pcbcService
    ) {
        $this->middleware('permission:akses_bapsb')->except(['outstanding', 'validateReport']);
        $this->middleware('permission:validate_bapsb_report')->only(['outstanding', 'validateReport']);
    }

    public function index(Request $request)
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $projects = $this->getProjects($userRoles);
        $periods = $this->recentPeriodOptions();

        return view('cashier.bapsb.index', compact('projects', 'periods'));
    }

    public function data(Request $request)
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $query = Bapsb::query()->with(['preparedBy']);

        if (! array_intersect($userRoles, ['superadmin', 'admin', 'cashier'])) {
            $query->where('project', auth()->user()->project);
        }

        if ($request->filled('project')) {
            $query->where('project', $request->project);
        }

        if ($request->filled('period')) {
            $query->where('period', $request->period);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('bapsb_date', fn (Bapsb $b) => $b->bapsb_date?->format('d M Y') ?? '-')
            ->editColumn('validation_status', function (Bapsb $b) {
                $class = $b->validation_status === Bapsb::VALIDATION_VALIDATED ? 'success' : 'warning';

                return '<span class="badge badge-'.$class.'">'.e(ucfirst($b->validation_status)).'</span>';
            })
            ->addColumn('bilyet_count', fn (Bapsb $b) => $b->lineCount())
            ->addColumn('submission', function (Bapsb $b) {
                if ($b->submitted_at) {
                    return '<span class="badge badge-info">Submitted</span>';
                }

                return '<span class="badge badge-secondary">Draft</span>';
            })
            ->addColumn('action', 'cashier.bapsb.action')
            ->rawColumns(['validation_status', 'submission', 'action'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $projects = $this->getProjects($userRoles)->filter(
            fn ($code) => $this->bapsbService->projectHasNeedsBilyetGiro($code)
        );
        $period = $request->query('period', now()->format('Y-m'));
        $project = $request->query('project', auth()->user()->project);

        if (! $projects->contains($project)) {
            $project = $projects->first();
        }

        $grouped = [];
        if ($project) {
            $bilyets = $this->bapsbService->bilyetsForPeriod($project, $period);
            $grouped = $this->groupBilyetsByAccount($bilyets);
            $mutations = $this->bapsbService->mutationCountsForPeriod($project, $period);
            $summary = $this->bapsbService->summarizeBilyets($bilyets);
        } else {
            $mutations = ['count_cair' => 0, 'count_void' => 0];
            $summary = [
                'total_bg' => 0, 'total_cek' => 0, 'total_loa' => 0,
                'count_bg' => 0, 'count_cek' => 0, 'count_loa' => 0,
            ];
        }

        $locations = BapsbService::LOCATIONS;
        $periods = $this->recentPeriodOptions();

        return view('cashier.bapsb.create', compact(
            'projects',
            'period',
            'project',
            'grouped',
            'mutations',
            'summary',
            'locations',
            'periods'
        ));
    }

    public function store(StoreBapsbRequest $request)
    {
        $project = $request->string('project')->toString();
        $period = $request->string('period')->toString();

        if (! $this->bapsbService->projectHasNeedsBilyetGiro($project)) {
            return redirect()->back()->withInput()->with('error', 'This project does not require BAPSB.');
        }

        if (Bapsb::query()->where('project', $project)->where('period', $period)->exists()) {
            return redirect()->back()->withInput()->with('error', 'BAPSB for this project and period already exists.');
        }

        $expected = $this->bapsbService->bilyetsForPeriod($project, $period)->keyBy('id');
        $lineIds = collect($request->input('lines', []))->pluck('bilyet_id')->map(fn ($id) => (int) $id)->sort()->values();
        $expectedIds = $expected->keys()->sort()->values();

        if ($lineIds->toArray() !== $expectedIds->toArray()) {
            return redirect()->back()->withInput()->with('error', 'Bilyet list does not match the selected period. Refresh and try again.');
        }

        $bapsb = DB::transaction(function () use ($request, $project, $period, $expected) {
            $bilyets = $expected->values();
            $summary = $this->bapsbService->summarizeBilyets($bilyets);
            $mutations = $this->bapsbService->mutationCountsForPeriod($project, $period);

            $bapsb = Bapsb::query()->create([
                'nomor' => $this->bapsbService->generateNomor($project, $period),
                'period' => $period,
                'project' => $project,
                'bapsb_date' => $request->date('bapsb_date'),
                'prepared_by' => $request->user()->id,
                'checker1' => $request->string('checker1'),
                'checker2' => $request->string('checker2'),
                'approved_by' => $request->input('approved_by'),
                ...$summary,
                ...$mutations,
                'validation_status' => Bapsb::VALIDATION_PENDING,
            ]);

            foreach ($request->input('lines', []) as $lineInput) {
                $bilyet = $expected->get((int) $lineInput['bilyet_id']);
                if (! $bilyet) {
                    continue;
                }
                $this->createLineFromBilyet($bapsb, $bilyet, $lineInput);
            }

            return $bapsb;
        });

        return redirect()->route('cashier.bapsb.show', $bapsb)->with('success', 'BAPSB draft saved.');
    }

    public function show(Bapsb $bapsb)
    {
        $this->authorizeProjectAccess($bapsb);
        $bapsb->load(['lines.bilyet.giro.bank', 'dokumen', 'preparedBy', 'validatedBy']);

        return view('cashier.bapsb.show', compact('bapsb'));
    }

    public function edit(Bapsb $bapsb)
    {
        $this->authorizeProjectAccess($bapsb);

        if (! $bapsb->isEditable()) {
            return redirect()->route('cashier.bapsb.show', $bapsb)->with('error', 'Submitted BAPSB cannot be edited.');
        }

        $bapsb->load(['lines.bilyet.giro.bank']);
        $mutations = [
            'count_cair' => $bapsb->count_cair,
            'count_void' => $bapsb->count_void,
        ];
        $summary = [
            'total_bg' => $bapsb->total_bg,
            'total_cek' => $bapsb->total_cek,
            'total_loa' => $bapsb->total_loa,
            'count_bg' => $bapsb->count_bg,
            'count_cek' => $bapsb->count_cek,
            'count_loa' => $bapsb->count_loa,
        ];
        $locations = BapsbService::LOCATIONS;
        $grouped = $this->groupLinesByAccount($bapsb->lines);

        return view('cashier.bapsb.edit', compact('bapsb', 'mutations', 'summary', 'locations', 'grouped'));
    }

    public function update(UpdateBapsbRequest $request, Bapsb $bapsb)
    {
        $this->authorizeProjectAccess($bapsb);

        if (! $bapsb->isEditable()) {
            return redirect()->route('cashier.bapsb.show', $bapsb)->with('error', 'Submitted BAPSB cannot be edited.');
        }

        DB::transaction(function () use ($request, $bapsb) {
            $bilyets = $this->bapsbService->bilyetsForPeriod($bapsb->project, $bapsb->period)->keyBy('id');
            $summary = $this->bapsbService->summarizeBilyets($bilyets->values());
            $mutations = $this->bapsbService->mutationCountsForPeriod($bapsb->project, $bapsb->period);

            $bapsb->update([
                'bapsb_date' => $request->date('bapsb_date'),
                'checker1' => $request->string('checker1'),
                'checker2' => $request->string('checker2'),
                'approved_by' => $request->input('approved_by'),
                ...$summary,
                ...$mutations,
            ]);

            foreach ($request->input('lines', []) as $lineInput) {
                $bilyet = $bilyets->get((int) $lineInput['bilyet_id']);
                if (! $bilyet) {
                    continue;
                }

                $line = $bapsb->lines()->where('bilyet_id', $bilyet->id)->first();
                if ($line) {
                    $line->update([
                        'physical_present' => (bool) $lineInput['physical_present'],
                        'location' => $lineInput['location'],
                        'location_note' => $lineInput['location_note'] ?? null,
                        'remarks' => $lineInput['remarks'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('cashier.bapsb.show', $bapsb)->with('success', 'BAPSB draft updated.');
    }

    public function print(Bapsb $bapsb)
    {
        $this->authorizeProjectAccess($bapsb);
        $bapsb->load(['lines', 'preparedBy']);

        return view('cashier.bapsb.print', compact('bapsb'));
    }

    public function upload(Request $request, Bapsb $bapsb)
    {
        $this->authorizeProjectAccess($bapsb);

        if (! $bapsb->isEditable()) {
            return redirect()->back()->with('error', 'Cannot replace PDF after submission.');
        }

        $request->validate([
            'attachment' => 'required|mimes:pdf|max:5120',
        ]);

        try {
            $filename = $this->uploadBapsbFile($request->file('attachment'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($bapsb->dokumen_id) {
            $old = Dokumen::query()->find($bapsb->dokumen_id);
            if ($old) {
                $raw = $old->getOriginal('filename1');
                if ($raw) {
                    $this->pcbcService->deleteFile($raw);
                }
                $old->delete();
            }
        }

        $dokumen = Dokumen::query()->create([
            'filename1' => $filename,
            'type' => 'bapsb',
            'project' => $bapsb->project,
            'dokumen_date' => $bapsb->bapsb_date,
            'remarks' => 'BAPSB '.$bapsb->nomor,
            'created_by' => $request->user()->id,
            'validation_status' => Dokumen::VALIDATION_PENDING,
        ]);

        $bapsb->update(['dokumen_id' => $dokumen->id]);

        return redirect()->back()->with('success', 'Signed PDF uploaded.');
    }

    public function submit(Request $request, Bapsb $bapsb)
    {
        $this->authorizeProjectAccess($bapsb);

        if ($bapsb->submitted_at) {
            return redirect()->back()->with('error', 'BAPSB was already submitted.');
        }

        if (! $bapsb->dokumen_id) {
            return redirect()->back()->with('error', 'Upload the signed PDF before submitting.');
        }

        $bapsb->update([
            'submitted_at' => now(),
        ]);

        return redirect()->back()->with('success', 'BAPSB submitted for HO validation.');
    }

    public function validateReport(Request $request, Bapsb $bapsb)
    {
        if (! $request->user()->can('validate_bapsb_report')) {
            abort(403);
        }

        if (! $bapsb->submitted_at) {
            return redirect()->back()->with('error', 'Only submitted BAPSB can be validated.');
        }

        if ($bapsb->validation_status === Bapsb::VALIDATION_VALIDATED) {
            return redirect()->back()->with('error', 'BAPSB is already validated.');
        }

        $request->validate([
            'validation_note' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($request, $bapsb) {
            $bapsb->update([
                'validation_status' => Bapsb::VALIDATION_VALIDATED,
                'validated_at' => now(),
                'validated_by' => $request->user()->id,
            ]);

            if ($bapsb->dokumen_id) {
                Dokumen::query()->whereKey($bapsb->dokumen_id)->update([
                    'validation_status' => Dokumen::VALIDATION_VALIDATED,
                    'validated_at' => now(),
                    'validated_by' => $request->user()->id,
                    'rejection_reason' => null,
                ]);
            }
        });

        return redirect()->back()->with('success', 'BAPSB marked as validated.');
    }

    public function outstanding(Request $request)
    {
        $outstanding = $this->complianceService->outstandingSubmissions();
        $pending = Bapsb::query()
            ->whereNotNull('submitted_at')
            ->where('validation_status', Bapsb::VALIDATION_PENDING)
            ->with(['preparedBy', 'dokumen'])
            ->orderByDesc('submitted_at')
            ->get();

        return view('cashier.bapsb.outstanding', compact('outstanding', 'pending'));
    }

    protected function getProjects(array $userRoles): \Illuminate\Support\Collection
    {
        if (array_intersect($this->allowedRoles, $userRoles)) {
            return Project::orderBy('code')->pluck('code');
        }

        return collect(explode(',', auth()->user()->project));
    }

    /**
     * @return array<int, string>
     */
    protected function recentPeriodOptions(): array
    {
        $options = [];
        $cursor = Carbon::now()->startOfMonth();
        for ($i = 0; $i < 18; $i++) {
            $options[] = $cursor->format('Y-m');
            $cursor->subMonth();
        }

        return $options;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Bilyet>  $bilyets
     * @return array<string, \Illuminate\Support\Collection<int, Bilyet>>
     */
    protected function groupBilyetsByAccount($bilyets): array
    {
        $grouped = [];
        foreach ($bilyets as $bilyet) {
            $key = $this->bapsbService->bankAccountLabel($bilyet);
            $grouped[$key] = ($grouped[$key] ?? collect())->push($bilyet);
        }

        return $grouped;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BapsbLine>  $lines
     * @return array<string, \Illuminate\Support\Collection<int, BapsbLine>>
     */
    protected function groupLinesByAccount($lines): array
    {
        $grouped = [];
        foreach ($lines as $line) {
            $key = $line->bank_account;
            $grouped[$key] = ($grouped[$key] ?? collect())->push($line);
        }

        return $grouped;
    }

    protected function createLineFromBilyet(Bapsb $bapsb, Bilyet $bilyet, array $lineInput): void
    {
        BapsbLine::query()->create([
            'bapsb_id' => $bapsb->id,
            'bilyet_id' => $bilyet->id,
            'type' => $bilyet->type,
            'nomor' => $bilyet->prefix.$bilyet->nomor,
            'bank_account' => $this->bapsbService->bankAccountLabel($bilyet),
            'bilyet_date' => $bilyet->bilyet_date,
            'cair_date' => $bilyet->cair_date,
            'amount' => (int) round((float) $bilyet->amount),
            'status' => $bilyet->status,
            'physical_present' => (bool) $lineInput['physical_present'],
            'location' => $lineInput['location'],
            'location_note' => $lineInput['location_note'] ?? null,
            'remarks' => $lineInput['remarks'] ?? null,
        ]);
    }

    protected function authorizeProjectAccess(Bapsb $bapsb): void
    {
        $userRoles = app(UserController::class)->getUserRoles();
        if (array_intersect($this->allowedRoles, $userRoles)) {
            return;
        }

        $userProject = auth()->user()->project;
        if ($bapsb->project !== $userProject) {
            abort(403);
        }
    }

    protected function uploadBapsbFile($file): string
    {
        $fileSize = $file->getSize();
        if ($fileSize > 5 * 1024 * 1024) {
            throw new \Exception('File size exceeds 5MB limit.');
        }

        if ($file->getMimeType() !== 'application/pdf') {
            throw new \Exception('Only PDF files are allowed.');
        }

        $filename = 'bapsb_'.uniqid().'_'.time().'.pdf';
        $file->move(public_path('dokumens'), $filename);

        Log::info('BAPSB file uploaded', [
            'filename' => $filename,
            'size' => $fileSize,
            'user_id' => auth()->id(),
        ]);

        return $filename;
    }
}
