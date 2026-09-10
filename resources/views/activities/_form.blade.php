@php
    $isEdit = isset($activity);
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="code">Kode</label>
            <input type="text" name="code" id="code" class="form-control"
                value="{{ old('code', $activity->code ?? '') }}"
                placeholder="Kosongkan untuk auto KEG-YYYY-NNN">
        </div>
    </div>
    <div class="col-md-8">
        <div class="form-group">
            <label for="name">Nama Kegiatan <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control" required
                value="{{ old('name', $activity->name ?? '') }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="periode">Periode <span class="text-danger">*</span></label>
            <input type="text" name="periode" id="periode" class="form-control" required
                placeholder="2026-09 atau 2026"
                value="{{ old('periode', $activity->periode ?? now()->format('Y-m')) }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="project">Project</label>
            <select name="project" id="project" class="form-control">
                <option value="">Semua Project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->code }}"
                        {{ old('project', $activity->project ?? '') === $project->code ? 'selected' : '' }}>
                        {{ $project->code }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="department_id">Departemen</label>
            <select name="department_id" id="department_id" class="form-control">
                <option value="">—</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}"
                        {{ (string) old('department_id', $activity->department_id ?? '') === (string) $department->id ? 'selected' : '' }}>
                        {{ $department->akronim }} — {{ $department->name ?? $department->sap_code }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="mode">Mode <span class="text-danger">*</span></label>
            <select name="mode" id="mode" class="form-control" required>
                <option value="tanpa_reklasifikasi"
                    {{ old('mode', $activity->mode ?? 'tanpa_reklasifikasi') === 'tanpa_reklasifikasi' ? 'selected' : '' }}>
                    Tanpa Reklasifikasi
                </option>
                <option value="reklasifikasi"
                    {{ old('mode', $activity->mode ?? '') === 'reklasifikasi' ? 'selected' : '' }}>
                    Reklasifikasi
                </option>
            </select>
        </div>
    </div>
    <div class="col-md-8">
        <div class="form-group">
            <label for="account_id">Akun Kegiatan (expense)</label>
            <select name="account_id" id="account_id" class="form-control select2">
                <option value="">— Pilih akun —</option>
                @foreach ($expenseAccounts as $account)
                    <option value="{{ $account->id }}"
                        {{ (string) old('account_id', $activity->account_id ?? '') === (string) $account->id ? 'selected' : '' }}>
                        {{ $account->account_number }} — {{ $account->account_name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Wajib untuk mode reklasifikasi.</small>
        </div>
    </div>
</div>

<div class="form-group">
    <label for="anggaran_ids">RAB Terkait</label>
    <select name="anggaran_ids[]" id="anggaran_ids" class="form-control select2" multiple>
        @php
            $selectedRabs = old('anggaran_ids', isset($activity) ? $activity->anggarans->pluck('id')->all() : []);
        @endphp
        @foreach ($anggarans as $anggaran)
            <option value="{{ $anggaran->id }}" {{ in_array($anggaran->id, $selectedRabs, false) ? 'selected' : '' }}>
                {{ $anggaran->nomor ?? $anggaran->rab_no ?? ('RAB #' . $anggaran->id) }}
            </option>
        @endforeach
    </select>
</div>

@if ($isEdit)
    <div class="form-group">
        <label for="status">Status</label>
        <select name="status" id="status" class="form-control">
            <option value="open" {{ old('status', $activity->status) === 'open' ? 'selected' : '' }}>Open</option>
            <option value="closed" {{ old('status', $activity->status) === 'closed' ? 'selected' : '' }}>Closed</option>
        </select>
    </div>
@endif
