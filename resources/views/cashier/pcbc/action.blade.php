@can('validate_pcbc_report')
    @if ($model->validation_status === \App\Models\Dokumen::VALIDATION_PENDING)
        <form action="{{ route('cashier.pcbc.dokumen.validate', $model) }}" method="POST" class="d-inline"
            onsubmit="return confirm('Are you sure you want to validate this PCBC? It will count as the official report for the document date and for compliance.');">
            @csrf
            <button type="submit" class="btn btn-xs btn-success" title="Validate"><i class="fas fa-check"></i></button>
        </form>
        <button type="button" class="btn btn-xs btn-outline-danger" data-toggle="modal"
            data-target="#rejectModal-{{ $model->id }}" title="Reject"><i class="fas fa-times"></i></button>
    @endif
@endcan

@if (auth()->user()->hasAnyRole(['superadmin', 'admin', 'cashier']) || auth()->user()->id == $model->created_by)
    <button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#editModal-{{ $model->id }}"
        title="edit"><i class="fas fa-edit"></i>
    </button>
@endif

<a href="{{ $model->filename1 }}" class="btn btn-xs btn-info" target="_blank" title="show pcbc"><i
        class="fas fa-file-pdf"></i></a>

@hasanyrole('superadmin|admin|cashier')
    <form action="{{ route('cashier.pcbc.destroy', $model->id) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-xs btn-danger"
            onclick="return confirm('Are you sure you want to delete this record?')" title="delete"><i
                class="fas fa-trash"></i></button>
    </form>
@else
    @if (auth()->user()->id == $model->created_by)
        <form action="{{ route('cashier.pcbc.destroy', $model->id) }}" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-xs btn-danger"
                onclick="return confirm('Are you sure you want to delete this record?')" title="delete"><i
                    class="fas fa-trash"></i></button>
        </form>
    @endif
@endhasanyrole

<!-- Modal -->
<div class="modal fade" id="editModal-{{ $model->id }}" tabindex="-1" role="dialog"
    aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Record</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('cashier.pcbc.update', $model->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    @if ($model->validation_status === \App\Models\Dokumen::VALIDATION_REJECTED && $model->rejection_reason)
                        <div class="alert alert-danger small mb-3">
                            <strong>Rejected — reason from reviewer</strong>
                            <div class="mt-1 mb-0 text-dark">{!! nl2br(e($model->rejection_reason)) !!}</div>
                        </div>
                    @endif
                    @hasanyrole('superadmin|cashier|admin')
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="project">Project</label>
                                    <select name="project" id="project" class="form-control select2bs4">
                                        @foreach (App\Models\Project::orderBy('code', 'asc')->get() as $project)
                                            <option value="{{ $project->code }}"
                                                {{ $project->code == old('project', $model->project) ? 'selected' : '' }}>
                                                {{ $project->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endhasanyrole
                    <!-- Periode field -->
                    <div class="form-group">
                        <label for="dokumen_date">PCBC Date</label>
                        <input type="date" class="form-control" id="dokumen_date" name="dokumen_date"
                            value="{{ old('dokumen_date', $model->getRawOriginal('dokumen_date')) }}">
                    </div>
                    <!-- Replace attachment -->
                    <div class="form-group">
                        <label for="attachment">Replace Attachment</label>

                        <input type="file" class="form-control" id="attachment" name="attachment">
                    </div>
                    @if ($model->filename1)
                        <div>
                            <a href="{{ $model->filename1 }}" target="_blank">View current attachment</a>
                        </div>
                    @endif
                    <!-- Add more fields as needed -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@can('validate_pcbc_report')
    @if ($model->validation_status === \App\Models\Dokumen::VALIDATION_PENDING)
        <div class="modal fade" id="rejectModal-{{ $model->id }}" tabindex="-1" role="dialog"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject PCBC</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('cashier.pcbc.dokumen.reject', $model) }}" method="POST"
                        onsubmit="var t=document.getElementById('rejection_reason-{{ $model->id }}'); if(!t||!t.value.trim()){alert('Please enter a reason for rejection.');if(t)t.focus();return false;} return confirm('Reject this PCBC? The uploader will need to address the reason and resubmit if needed.');">
                        @csrf
                        <div class="modal-body">
                            <p class="text-muted small mb-2">A reason is required so the uploader knows what to fix.</p>
                            <div class="form-group">
                                <label for="rejection_reason-{{ $model->id }}">Reason for rejection <span
                                        class="text-danger">*</span></label>
                                <textarea name="rejection_reason" id="rejection_reason-{{ $model->id }}"
                                    class="form-control" rows="4" required maxlength="2000"
                                    minlength="1"
                                    placeholder="Describe why this PCBC is being rejected (e.g. wrong period, unclear scan, does not match site cash)"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Confirm reject</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endcan
