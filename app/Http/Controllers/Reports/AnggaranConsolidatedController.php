<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Anggaran;
use App\Models\Project;
use App\Services\AnggaranReleaseService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggaranConsolidatedController extends Controller
{
    public function __construct(
        protected AnggaranReleaseService $releaseService
    ) {}

    public function index(Request $request): View
    {
        $userRoles = app(UserController::class)->getUserRoles();
        $projects = Project::query()->orderBy('code')->get();

        $filters = [
            'project' => $request->query('project'),
            'type' => $request->query('type'),
            'fund_status' => $request->query('fund_status'),
        ];

        $base = Anggaran::query()
            ->where('status', 'approved')
            ->where('is_active', 1);

        if (! array_intersect(['superadmin', 'admin'], $userRoles)) {
            $base->where('project', $request->user()->project);
        }

        if (! empty($filters['project'])) {
            $base->where('rab_project', $filters['project']);
        }
        if (! empty($filters['type'])) {
            $base->where('type', $filters['type']);
        }
        if (! empty($filters['fund_status'])) {
            $base->where('fund_status', $filters['fund_status']);
        }

        $totals = [
            'budget' => (float) (clone $base)->sum('amount'),
            'released' => (float) (clone $base)->sum('balance'),
        ];
        $totals['remaining'] = $totals['budget'] - $totals['released'];

        $anggarans = (clone $base)
            ->with(['createdBy:id,name', 'department:id,department_name'])
            ->orderByDesc('date')
            ->paginate(100)
            ->withQueryString();

        $byDepartment = [];
        if (! empty($filters['project'])) {
            $byDepartment = $this->releaseService->aggregateByDepartment((string) $filters['project']);
        }

        return view('reports.anggaran.consolidated', compact('anggarans', 'projects', 'filters', 'byDepartment', 'totals'));
    }
}
