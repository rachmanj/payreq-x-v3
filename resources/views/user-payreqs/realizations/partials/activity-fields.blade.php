@php
    $prefix = $prefix ?? '';
    $selectedActivityId = $selectedActivityId ?? null;
    $activityExcluded = $activityExcluded ?? false;
@endphp

<div class="form-group">
    <label>Kegiatan (opsional)</label>
    <div class="custom-control custom-checkbox mb-2">
        <input type="checkbox" class="custom-control-input {{ $prefix }}activity-excluded"
            id="{{ $prefix }}activity_excluded" name="activity_excluded" value="1"
            {{ $activityExcluded ? 'checked' : '' }}>
        <label class="custom-control-label" for="{{ $prefix }}activity_excluded">Tanpa kegiatan (override)</label>
    </div>
    <select name="activity_id" id="{{ $prefix }}activity_id" class="form-control {{ $prefix }}activity-select">
        <option value="">— Ikut header realisasi —</option>
        @foreach ($openActivities as $activity)
            <option value="{{ $activity->id }}"
                {{ (string) $selectedActivityId === (string) $activity->id ? 'selected' : '' }}>
                {{ $activity->code }} — {{ $activity->name }}
            </option>
        @endforeach
    </select>
    <small class="text-muted">Kosongkan dan tidak centang override = ikut kegiatan header.</small>
</div>
