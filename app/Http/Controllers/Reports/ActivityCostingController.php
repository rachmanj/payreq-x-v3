<?php

namespace App\Http\Controllers\Reports;

use App\Exports\ActivityCostingExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Activity;
use App\Models\Department;
use App\Models\Project;
use App\Services\ActivityCostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityCostingController extends Controller
{
    public function __construct(
        protected ActivityCostingService $costingService,
    ) {}

    public function index(Request $request): View
    {
        return view('reports.activity-costing.index', [
            'filters' => $this->costingService->filtersFromRequest($request->all()),
            'departments' => Department::query()->orderBy('akronim')->get(),
            'projects' => Project::query()->orderBy('code')->get(),
            'activities' => Activity::query()->orderByDesc('id')->limit(500)->get(['id', 'code', 'name']),
        ]);
    }

    public function data(Request $request)
    {
        $filters = $this->costingService->filtersFromRequest($request->all());
        $activities = $this->costingService->indexQuery($filters)->get();

        return datatables()->of($activities)
            ->addIndexColumn()
            ->addColumn('account_label', function (Activity $activity) {
                if (! $activity->account) {
                    return '-';
                }

                return $activity->account->account_number.' — '.$activity->account->account_name;
            })
            ->addColumn('mode_label', function (Activity $activity) {
                return $activity->mode === 'reklasifikasi'
                    ? '<span class="badge badge-info">Reklasifikasi</span>'
                    : '<span class="badge badge-secondary">Tanpa Reklasifikasi</span>';
            })
            ->addColumn('status_label', function (Activity $activity) {
                return $activity->status === 'open'
                    ? '<span class="badge badge-success">Open</span>'
                    : '<span class="badge badge-dark">Closed</span>';
            })
            ->editColumn('total_cost', fn (Activity $activity) => number_format((int) $activity->total_cost, 0, ',', '.'))
            ->editColumn('realization_count', fn (Activity $activity) => number_format((int) $activity->realization_count, 0, ',', '.'))
            ->editColumn('note_count', fn (Activity $activity) => number_format((int) $activity->note_count, 0, ',', '.'))
            ->addColumn('action', function (Activity $activity) use ($filters) {
                return view('reports.activity-costing.action', [
                    'activity' => $activity,
                    'filters' => $filters,
                ])->render();
            })
            ->rawColumns(['mode_label', 'status_label', 'action'])
            ->toJson();
    }

    public function show(Request $request, int $id): View
    {
        $activity = Activity::query()->with(['account', 'department'])->findOrFail($id);
        $filters = $this->costingService->filtersFromRequest($request->all());

        return view('reports.activity-costing.show', compact('activity', 'filters'));
    }

    public function showData(Request $request, int $id)
    {
        $activity = Activity::query()->findOrFail($id);
        $filters = $this->costingService->filtersFromRequest($request->all());
        $type = $request->get('type', 'notas');

        if ($type === 'journals') {
            return $this->journalData($activity, $filters);
        }

        return $this->notaData($activity, $filters);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = $this->costingService->filtersFromRequest($request->all());
        $activities = $this->costingService->indexQuery($filters)->get();
        $filename = 'biaya-per-kegiatan-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new ActivityCostingExport($activities, $filters, $this->costingService), $filename);
    }

    public function close(int $id): RedirectResponse
    {
        $activity = Activity::query()->findOrFail($id);

        if ($activity->isClosed()) {
            return redirect()
                ->route('reports.activity-costing.show', $activity->id)
                ->with('error', 'Kegiatan sudah berstatus closed.');
        }

        $activity->update(['status' => 'closed']);

        return redirect()
            ->route('reports.activity-costing.show', $activity->id)
            ->with('success', 'Kegiatan berhasil ditutup.');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function notaData(Activity $activity, array $filters)
    {
        $details = $this->costingService->notaQuery($activity->id, $filters)->get();

        return datatables()->of($details)
            ->addIndexColumn()
            ->editColumn('expense_date', fn ($row) => $row->expense_date ? $row->expense_date->format('d-M-Y') : '-')
            ->addColumn('realization_link', function ($row) {
                $nomor = $row->realization?->nomor ?? '-';
                if (! $row->realization) {
                    return e($nomor);
                }

                $url = route('user-payreqs.realizations.show', $row->realization_id);

                return '<a href="'.$url.'" target="_blank">'.e($nomor).'</a>';
            })
            ->addColumn('account_label', function ($row) {
                if (! $row->account) {
                    return '-';
                }

                return $row->account->account_number.' — '.$row->account->account_name;
            })
            ->addColumn('cost_center', fn ($row) => $row->department?->sap_code ?? '-')
            ->editColumn('amount', fn ($row) => number_format((int) $row->amount, 0, ',', '.'))
            ->addColumn('reklasifikasi', fn ($row) => $this->costingService->isReclassifiedForActivity($activity, $row) ? 'Ya' : 'Tidak')
            ->addColumn('vj_status', function ($row) {
                $vj = $row->realization?->verificationJournal;
                if (! $vj) {
                    return '<span class="text-muted">Belum VJ</span>';
                }

                $status = $vj->sap_journal_no
                    ? 'Posted SAP ('.e($vj->sap_journal_no).')'
                    : 'VJ '.e($vj->nomor);

                return $status;
            })
            ->rawColumns(['realization_link', 'vj_status'])
            ->toJson();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function journalData(Activity $activity, array $filters)
    {
        $lines = $this->costingService->journalLinesQuery($activity->id, $filters)->get();

        return datatables()->of($lines)
            ->addIndexColumn()
            ->addColumn('account_label', function ($row) {
                $account = Account::query()->where('account_number', $row->account_code)->first();
                if (! $account) {
                    return e($row->account_code);
                }

                return $account->account_number.' — '.$account->account_name;
            })
            ->addColumn('debit_amount', function ($row) {
                return $row->debit_credit === 'debit'
                    ? number_format((int) $row->amount, 0, ',', '.')
                    : '-';
            })
            ->addColumn('credit_amount', function ($row) {
                return $row->debit_credit === 'credit'
                    ? number_format((int) $row->amount, 0, ',', '.')
                    : '-';
            })
            ->addColumn('vj_nomor', fn ($row) => $row->verificationJournal?->nomor ?? '-')
            ->addColumn('sap_status', function ($row) {
                $vj = $row->verificationJournal;
                if (! $vj) {
                    return '-';
                }

                if ($row->activity_id === null) {
                    return 'VJ tidak termapping';
                }

                if ($vj->sap_journal_no) {
                    return 'Posted ('.e($vj->sap_journal_no).')';
                }

                return 'Belum posting SAP';
            })
            ->rawColumns(['sap_status'])
            ->toJson();
    }
}
