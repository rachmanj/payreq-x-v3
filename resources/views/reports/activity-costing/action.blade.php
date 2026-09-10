@php
    $query = http_build_query(array_filter([
        'date_from' => $filters['date_from'] ?? null,
        'date_to' => $filters['date_to'] ?? null,
        'project' => $filters['project'] ?? null,
        'department_id' => $filters['department_id'] ?? null,
        'periode' => $filters['periode'] ?? null,
        'status' => $filters['status'] ?? null,
    ]));
@endphp

<a href="{{ route('reports.activity-costing.show', $activity->id) }}{{ $query ? '?' . $query : '' }}" class="btn btn-sm btn-info" title="Detail">
    <i class="fas fa-eye"></i> Detail
</a>
