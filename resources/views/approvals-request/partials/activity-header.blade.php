@can('manage_activities')
    @if ($showActivityColumn ?? false)
        <div class="card card-outline card-info mb-3" id="activity-header-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="card-title mb-0"><i class="fas fa-tags"></i> Kegiatan (opsional)</h3>
                @unless ($activityLocked ?? false)
                    <button type="button" class="vj-btn vj-btn-info btn-sm" id="btn-create-activity"
                        data-default-project="{{ $realization->project }}">
                        <i class="fas fa-plus"></i> Buat Kegiatan Baru
                    </button>
                @endunless
            </div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <label for="header_activity_id">Kegiatan default untuk seluruh baris</label>
                    <select id="header_activity_id" class="form-control activity-select2"
                        {{ ($activityLocked ?? false) ? 'disabled' : '' }}>
                        <option value="">— Tanpa kegiatan —</option>
                        @foreach ($openActivities as $activity)
                            <option value="{{ $activity->id }}"
                                {{ (string) $realization->activity_id === (string) $activity->id ? 'selected' : '' }}>
                                {{ $activity->code }} — {{ $activity->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($activityLocked ?? false)
                        <small class="text-muted">Kegiatan terkunci karena dokumen sudah disetujui.</small>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endcan
