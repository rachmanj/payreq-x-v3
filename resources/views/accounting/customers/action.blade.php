@hasanyrole('superadmin|admin')
    <button href="#" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#modal-edit-{{ $model->id }}"><i
            class="fas fa-edit"></i></button>
    {{-- delete customer --}}
    <form action="{{ route('accounting.customers.destroy', $model->id) }}" method="POST" class="d-inline">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-xs btn-danger"
            onclick="return confirm('Are you sure you want to delete this customer account?')"><i
                class="fas fa-trash"></i></button>
    </form>
@endhasanyrole

{{-- Modal create --}}
<div class="modal fade" id="modal-edit-{{ $model->id }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"> Update Customer Account</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('accounting.customers.update', $model->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="customer_name">Customer Name</label>
                                <input name="customer_name" id="customer_name"
                                    class="form-control @error('customer_name') is-invalid @enderror"
                                    value="{{ old('customer_name', $model->name) }}">
                                @error('customer_name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="project">Project</label>
                                <select name="project" id="project" class="form-control">
                                    @foreach (\App\Models\Project::all() as $project)
                                        <option value="{{ $project->code }}"
                                            {{ $model->code == $project->code ? 'selected' : '' }}>
                                            {{ $project->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                </div> <!-- /.modal-body -->

                <div class="modal-footer float-left">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
</div>
