<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Models\Anggaran;
use App\Models\Project;
use App\Services\AnggaranReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnggaranFundPoolController extends Controller
{
    public function __construct(
        protected AnggaranReleaseService $releaseService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('recalculate_release');

        $userRoles = app(UserController::class)->getUserRoles();
        $projects = Project::query()->orderBy('code')->get();

        $projectFilter = $request->query('project');
        $statusFilter = $request->query('fund_status');

        $query = Anggaran::query()
            ->with(['department:id,department_name', 'fundPooledBy:id,name'])
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->orderBy('rab_project')
            ->orderBy('nomor');

        if (! array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->where('project', $request->user()->project);
        }

        if ($projectFilter) {
            $query->where('rab_project', $projectFilter);
        }

        if ($statusFilter) {
            $query->where('fund_status', $statusFilter);
        }

        $anggarans = $query->paginate(50)->withQueryString();

        return view('reports.anggaran.fund-pool', compact('anggarans', 'projects', 'projectFilter', 'statusFilter'));
    }

    public function markPooled(Request $request): RedirectResponse
    {
        $this->authorize('recalculate_release');

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:anggarans,id'],
        ]);

        $user = $request->user();
        $ids = array_map('intval', $request->input('ids', []));

        DB::transaction(function () use ($ids, $user): void {
            Anggaran::query()
                ->whereIn('id', $ids)
                ->where('status', 'approved')
                ->where('fund_status', Anggaran::FUND_STATUS_PENDING)
                ->update([
                    'fund_status' => Anggaran::FUND_STATUS_POOLED,
                    'fund_pooled_at' => now(),
                    'fund_pooled_by' => $user->id,
                ]);
        });

        foreach ($ids as $id) {
            $this->releaseService->forgetDetailCaches((int) $id);
        }
        $this->releaseService->flushListingCaches();

        return redirect()->back()->with('success', 'Selected budgets marked as pooled.');
    }

    public function markReleased(Request $request): RedirectResponse
    {
        $this->authorize('recalculate_release');

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:anggarans,id'],
        ]);

        $ids = array_map('intval', $request->input('ids', []));

        DB::transaction(function () use ($ids): void {
            Anggaran::query()
                ->whereIn('id', $ids)
                ->where('status', 'approved')
                ->where('fund_status', Anggaran::FUND_STATUS_POOLED)
                ->update([
                    'fund_status' => Anggaran::FUND_STATUS_RELEASED,
                ]);
        });

        foreach ($ids as $id) {
            $this->releaseService->forgetDetailCaches((int) $id);
        }
        $this->releaseService->flushListingCaches();

        return redirect()->back()->with('success', 'Selected pooled budgets marked as released.');
    }
}
