@php
    $selectedType = old('rab_type', $typeDefault ?? 'periode');
    $selectedUsage = old('usage', $usageDefault ?? 'user');
@endphp

<div class="row">
    <div class="col-md-6">
        <label for="rab_type">Type</label>
        <div class="form-group">
            <div class="form-check d-inline mr-4">
                <input class="form-check-input" type="radio" value="periode" name="rab_type" id="rab_type_periode"
                    {{ $selectedType === 'periode' ? 'checked' : '' }}>
                <label class="form-check-label" for="rab_type_periode">Periode</label>
            </div>
            <div class="form-check d-inline mr-4">
                <input class="form-check-input" type="radio" name="rab_type" value="event" id="rab_type_event"
                    {{ $selectedType === 'event' ? 'checked' : '' }}>
                <label class="form-check-label" for="rab_type_event">Event</label>
            </div>
            <div class="form-check d-inline">
                <input class="form-check-input" type="radio" name="rab_type" value="buc" id="rab_type_buc"
                    {{ $selectedType === 'buc' ? 'checked' : '' }}>
                <label class="form-check-label" for="rab_type_buc">BUC <small>(DNC only)</small></label>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <label for="usage_user">Usage</label>
        <div class="form-group">
            <div class="form-check d-inline mr-4">
                <input class="form-check-input" type="radio" value="user" name="usage" id="usage_user"
                    {{ $selectedUsage === 'user' ? 'checked' : '' }}>
                <label class="form-check-label" for="usage_user">User</label>
            </div>
            <div class="form-check d-inline mr-4">
                <input class="form-check-input" type="radio" name="usage" value="department" id="usage_department"
                    {{ $selectedUsage === 'department' ? 'checked' : '' }}>
                <label class="form-check-label" for="usage_department">Department</label>
            </div>
            <div class="form-check d-inline">
                <input class="form-check-input" type="radio" name="usage" value="project" id="usage_project"
                    {{ $selectedUsage === 'project' ? 'checked' : '' }}>
                <label class="form-check-label" for="usage_project">Project</label>
            </div>
        </div>
    </div>
</div>
