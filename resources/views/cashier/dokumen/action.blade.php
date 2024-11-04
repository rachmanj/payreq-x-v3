<!-- Button to trigger modal -->
{{-- <button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#editModal-{{ $model->id }}">
    edit
</button> --}}

<!-- Modal -->
<div class="modal fade" id="editModal-{{ $model->id }}" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Record</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm" action="{{ route('cashier.dokumen.update', $model->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <!-- Filename field -->
                    <div class="form-group">
                        <label for="filename1">Filename</label>
                        <input type="hidden" name="type" value="koran">
                        <input type="text" class="form-control" id="filename1" name="filename1"
                            value="{{ $model->filename1 }}">
                    </div>
                    <!-- Periode field -->
                    <div class="form-group">
                        <label for="periode">Periode</label>
                        <input type="text" class="form-control" id="periode" name="periode"
                            value="{{ old('periode', $model->periode) }}">
                    </div>
                    <!-- Giros selection -->
                    <div class="form-group">
                        <label for="giros">Giros</label>
                        <select class="form-control" id="giros" name="giros">
                            @foreach (App\Models\Giro::all() as $giro)
                                <option value="{{ $giro->id }}"
                                    {{ $model->giro_id == $giro->id ? 'selected' : '' }}>
                                    {{ $giro->acc_no . ' - ' . $giro->acc_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Replace attachment -->
                    <div class="form-group">
                        <label for="attachment">Replace Attachment</label>
                        <input type="file" class="form-control" id="attachment" name="attachment">
                    </div>
                    <!-- Add more fields as needed -->
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" form="editForm">Save changes</button>
            </div>
        </div>
    </div>
</div>


<a href="{{ $model->filename1 }}" class="btn btn-xs btn-info" target="_blank">show</a>


@hasanyrole(['superadmin', 'admin', 'cashier'])
    <form action="{{ route('cashier.dokumen.destroy', $model->id) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-xs btn-danger"
            onclick="return confirm('Are you sure you want to delete this record?')">delete</button>
    </form>
@endhasanyrole
