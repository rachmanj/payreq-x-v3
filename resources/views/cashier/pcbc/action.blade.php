<!-- Button to trigger modal -->
<button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#editModal-{{ $model->id }}"
    title="edit"><i class="fas fa-edit"></i>
</button>

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
                            value="{{ old('dokumen_date', $model->dokumen_date) }}">
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
