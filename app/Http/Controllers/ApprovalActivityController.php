<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Models\Account;
use App\Models\Activity;
use App\Models\Anggaran;
use App\Models\Department;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ApprovalActivityController extends Controller
{
    public function open(): JsonResponse
    {
        $project = request('project');

        $activities = Activity::query()
            ->open()
            ->with(['account:id,account_number,account_name'])
            ->when($project, function ($query, $project) {
                $query->where(function ($inner) use ($project) {
                    $inner->whereNull('project')->orWhere('project', $project);
                });
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'mode', 'project', 'account_id']);

        return response()->json($activities->map(fn (Activity $activity) => [
            'id' => $activity->id,
            'code' => $activity->code,
            'name' => $activity->name,
            'mode' => $activity->mode,
            'project' => $activity->project,
            'account' => $activity->account ? [
                'id' => $activity->account->id,
                'account_number' => $activity->account->account_number,
                'account_name' => $activity->account->account_name,
            ] : null,
        ]));
    }

    public function store(StoreActivityRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $code = filled($validated['code'] ?? null)
            ? $validated['code']
            : Activity::generateCode();

        $activity = Activity::create([
            'code' => $code,
            'name' => $validated['name'],
            'periode' => $validated['periode'],
            'project' => $validated['project'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'mode' => $validated['mode'],
            'account_id' => $validated['account_id'] ?? null,
            'status' => $validated['status'] ?? 'open',
            'created_by' => Auth::id(),
        ]);

        $activity->anggarans()->sync($validated['anggaran_ids'] ?? []);
        $activity->load('account:id,account_number,account_name');

        return response()->json([
            'success' => true,
            'message' => 'Kegiatan berhasil dibuat.',
            'activity' => [
                'id' => $activity->id,
                'code' => $activity->code,
                'name' => $activity->name,
                'mode' => $activity->mode,
                'project' => $activity->project,
                'account' => $activity->account ? [
                    'id' => $activity->account->id,
                    'account_number' => $activity->account->account_number,
                    'account_name' => $activity->account->account_name,
                ] : null,
            ],
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    public static function modalFormOptions(): array
    {
        return [
            'expenseAccounts' => Account::query()
                ->selectable()
                ->where('type', 'expense')
                ->orderBy('account_number')
                ->get(),
            'departments' => Department::query()->orderBy('akronim')->get(),
            'projects' => Project::query()->orderBy('code')->get(),
            'anggarans' => Anggaran::query()->orderByDesc('id')->limit(500)->get(),
        ];
    }
}
