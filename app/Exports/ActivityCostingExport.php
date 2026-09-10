<?php

namespace App\Exports;

use App\Services\ActivityCostingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ActivityCostingExport implements FromView, ShouldAutoSize
{
    /**
     * @param  Collection<int, \App\Models\Activity>  $activities
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected Collection $activities,
        protected array $filters,
        protected ActivityCostingService $costingService,
    ) {}

    public function view(): View
    {
        $notaDetails = collect();

        foreach ($this->activities as $activity) {
            $rows = $this->costingService->notaQuery($activity->id, $this->filters)
                ->with(['account', 'department', 'realization'])
                ->get()
                ->map(function ($detail) use ($activity) {
                    $detail->activity_code = $activity->code;
                    $detail->activity_name = $activity->name;
                    $detail->reklasifikasi = $this->costingService->isReclassifiedForActivity($activity, $detail) ? 'Ya' : 'Tidak';

                    return $detail;
                });

            $notaDetails = $notaDetails->merge($rows);
        }

        return view('reports.activity-costing.export', [
            'activities' => $this->activities,
            'notaDetails' => $notaDetails,
            'filters' => $this->filters,
        ]);
    }
}
