<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Account;
use App\Models\Activity;
use App\Models\Anggaran;
use App\Models\Department;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        return view('activities.index');
    }

    public function data()
    {
        $activities = Activity::query()
            ->with(['account', 'creator', 'department'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('search'), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('periode', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->get();

        return datatables()->of($activities)
            ->addIndexColumn()
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
            ->addColumn('account_label', function (Activity $activity) {
                if (! $activity->account) {
                    return '-';
                }

                return $activity->account->account_number.' — '.$activity->account->account_name;
            })
            ->addColumn('creator_name', fn (Activity $activity) => $activity->creator?->name ?? '-')
            ->addColumn('action', fn (Activity $activity) => view('activities.action', ['model' => $activity])->render())
            ->rawColumns(['mode_label', 'status_label', 'action'])
            ->toJson();
    }

    public function create(): View
    {
        return view('activities.create', $this->formOptions());
    }

    public function store(StoreActivityRequest $request): RedirectResponse
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

        return redirect()
            ->route('activities.index')
            ->with('success', 'Kegiatan berhasil dibuat.');
    }

    public function edit(Activity $activity): View
    {
        $activity->load('anggarans');

        return view('activities.edit', array_merge(
            $this->formOptions(),
            ['activity' => $activity]
        ));
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $validated = $request->validated();

        $activity->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'periode' => $validated['periode'],
            'project' => $validated['project'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'mode' => $validated['mode'],
            'account_id' => $validated['account_id'] ?? null,
            'status' => $validated['status'],
        ]);

        $activity->anggarans()->sync($validated['anggaran_ids'] ?? []);

        return redirect()
            ->route('activities.index')
            ->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function close(Activity $activity): RedirectResponse
    {
        if ($activity->isClosed()) {
            return redirect()
                ->route('activities.index')
                ->with('error', 'Kegiatan sudah berstatus closed.');
        }

        $activity->update(['status' => 'closed']);

        return redirect()
            ->route('activities.index')
            ->with('success', 'Kegiatan berhasil ditutup.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
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
