@can('manage_activities')
    @if (($showActivityColumn ?? false) && ! ($activityLocked ?? false) && ! empty($activityModalOptions))
        <div class="modal fade" id="approval-create-activity-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title"><i class="fas fa-plus"></i> Buat Kegiatan Baru</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="approval-create-activity-form">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="modal_activity_name">Nama Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" id="modal_activity_name" name="name" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="modal_activity_periode">Periode <span class="text-danger">*</span></label>
                                        <select id="modal_activity_periode" name="periode" class="form-control" required>
                                            @include('partials.activity-period-options')
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="modal_activity_project">Project</label>
                                        <select id="modal_activity_project" name="project" class="form-control">
                                            <option value="">Semua Project</option>
                                            @foreach ($activityModalOptions['projects'] as $project)
                                                <option value="{{ $project->code }}"
                                                    {{ $project->code === $realization->project ? 'selected' : '' }}>
                                                    {{ $project->code }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="modal_activity_mode">Mode <span class="text-danger">*</span></label>
                                        <select id="modal_activity_mode" name="mode" class="form-control" required>
                                            <option value="tanpa_reklasifikasi">Tanpa Reklasifikasi</option>
                                            <option value="reklasifikasi">Reklasifikasi</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="modal_activity_account_id">Akun Kegiatan (expense)</label>
                                <select id="modal_activity_account_id" name="account_id" class="form-control select2-modal">
                                    <option value="">— Pilih akun —</option>
                                    @foreach ($activityModalOptions['expenseAccounts'] as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->account_number }} — {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Wajib untuk mode reklasifikasi.</small>
                            </div>
                            <div class="form-group mb-0">
                                <label for="modal_activity_anggaran_ids">RAB Terkait (opsional)</label>
                                <select id="modal_activity_anggaran_ids" name="anggaran_ids[]"
                                    class="form-control select2-modal" multiple>
                                    @foreach ($activityModalOptions['anggarans'] as $anggaran)
                                        <option value="{{ $anggaran->id }}">
                                            {{ $anggaran->nomor ?? $anggaran->rab_no ?? ('RAB #' . $anggaran->id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="vj-action-item vj-action-print" data-dismiss="modal">
                                <i class="fas fa-times"></i>
                                <span>Batal</span>
                            </button>
                            <button type="submit" class="vj-btn vj-btn-primary" id="btn-submit-create-activity">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endcan
