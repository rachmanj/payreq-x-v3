<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Anggaran;
use App\Models\Project;
use App\Services\AnggaranReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggaranDashboardController extends Controller
{
    public function __construct(
        protected AnggaranReleaseService $releaseService
    ) {}

    public function index(Request $request): View
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $filters = [
            'project' => $request->query('project'),
            'type' => $request->query('type'),
            'fund_status' => $request->query('fund_status'),
        ];

        $stats = $this->releaseService->aggregateDashboardStats($request->user(), $userRoles, array_filter($filters));

        $projects = Project::query()->orderBy('code')->get();

        return view('reports.anggaran.dashboard', compact('stats', 'projects', 'filters'));
    }

    public function byDepartment(Request $request): JsonResponse
    {
        $project = (string) $request->query('project', '');
        if ($project === '') {
            return response()->json(['data' => []]);
        }

        $userRoles = app(UserController::class)->getUserRoles();
        if (! array_intersect(['superadmin', 'admin'], $userRoles)) {
            if ($project !== $request->user()->project) {
                abort(403);
            }
        }

        $data = $this->releaseService->aggregateByDepartment($project);

        return response()->json(['data' => $data]);
    }

    public function releaseData(Request $request): mixed
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $project = auth()->user()->project;

        $query = Anggaran::query()
            ->with(['createdBy:id,name', 'department:id,department_name'])
            ->withCount('payreqs')
            ->where('status', 'approved')
            ->where('is_active', 1);

        if (! array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->where('project', $project);
        }

        if ($request->filled('filter_project')) {
            $query->where('rab_project', $request->input('filter_project'));
        }
        if ($request->filled('filter_type')) {
            $query->where('type', $request->input('filter_type'));
        }
        if ($request->filled('filter_fund_status')) {
            $query->where('fund_status', $request->input('filter_fund_status'));
        }
        if ($request->filled('filter_date_from')) {
            $query->whereDate('date', '>=', $request->input('filter_date_from'));
        }
        if ($request->filled('filter_date_to')) {
            $query->whereDate('date', '<=', $request->input('filter_date_to'));
        }
        if ($request->filled('filter_department_id')) {
            $query->where('department_id', (int) $request->input('filter_department_id'));
        }

        $query->orderByDesc('anggarans.id');

        $dataTable = datatables()->of($query);

        return $dataTable
            ->addIndexColumn()
            ->editColumn('nomor', fn (Anggaran $a) => '<a href="'.route('reports.anggaran.show', $a->id).'">'.e((string) $a->nomor).'</a>')
            ->addColumn('budget_amount', fn (Anggaran $a) => number_format((float) $a->amount, 2))
            ->addColumn('released', fn (Anggaran $a) => number_format((float) $a->balance, 2))
            ->addColumn('remaining', function (Anggaran $a) {
                $rem = (float) $a->amount - (float) $a->balance;

                return number_format($rem, 2);
            })
            ->addColumn('utilization', function (Anggaran $a) {
                $p = $this->releaseService->parsePersenToFloat($a);
                $color = $this->statusColor($p);

                return '<div class="text-center"><small>'.$p.'%</small><div class="progress" style="height:18px">'
                    .'<div class="progress-bar progress-bar-striped '.$color.'" style="width:'.min($p, 100).'%"></div></div></div>';
            })
            ->addColumn('fund_status_label', fn (Anggaran $a) => '<span class="badge badge-secondary">'.e((string) ($a->fund_status ?? Anggaran::FUND_STATUS_PENDING)).'</span>')
            ->addColumn('department', fn (Anggaran $a) => e(optional($a->department)->department_name ?? '—'))
            ->addColumn('actions', fn (Anggaran $a) => '<a class="btn btn-xs btn-info" href="'.route('reports.anggaran.show', $a->id).'">View</a>')
            ->rawColumns(['nomor', 'utilization', 'fund_status_label', 'actions'])
            ->toJson();
    }

    protected function statusColor(float $progress): string
    {
        if ($progress > 100) {
            return 'bg-danger';
        }
        if ($progress > 90) {
            return 'bg-warning';
        }

        return 'bg-success';
    }
}
