@hasanyrole('superadmin')
<form action="{{ route('accounts.destroy', $model->id) }}" method="POST">
  @csrf @method('DELETE')
  <button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#account-edit-{{ $model->id }}">edit</button>
  <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are You sure You want to delete this records?')">delete</button>
</form>
@endhasanyrole

{{-- Modal edit --}}
<div class="modal fade" id="account-edit-{{ $model->id }}">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title"> Edit Account</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
      <form action="{{ route('accounts.update', $model->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="modal-body">

          <div class="row">
            <div class="col-4">
              <div class="form-group">
                <label for="account_number">Account No</label>
                <input name="account_number" id="account_number" value="{{ old('account_number', $model->account_number) }}" class="form-control @error('account_number') is-invalid @enderror" autocomplete="off" autofocus>
                @error('account_number')
                  <div class="invalid-feedback">
                    {{ $message }}
                  </div>
                @enderror
              </div>
            </div>
            <div class="col-4">
              <div class="form-group">
                <label for="type">Type</label>
                <select name="type" class="form-control @error('type') is-invalid @enderror">
                  @foreach (\App\Models\AccountType::all() as $type)
                    <option value="{{ $type->type_name }}" {{ old('type', $model->type) == $type->type_name  ? 'selected' : '' }} >{{ ucfirst($type->type_name) }}</option>
                  @endforeach
                </select>
                @error('type')
                  <div class="invalid-feedback">
                    {{ $message }}
                  </div>
                @enderror
              </div>
            </div>
            <div class="col-4">
              <div class="form-group">
                <label for="project">Project</label>
                <select name="project" id="project" class="form-control @error('project') is-invalid @enderror">
                  @foreach (\App\Models\Project::all() as $project)
                      <option value="{{ $project->code }}" {{ old('project', $model->project) == $project->code ? 'selected' : '' }} >{{ $project->code }}</option>
                  @endforeach
                    <option value="all-site" {{ old('project', $model->project) == 'all-site' ? 'selected' : '' }} >All Site</option>
                </select>
                @error('project')
                  <div class="invalid-feedback">
                    {{ $message }}
                  </div>
                @enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="account_name">Account Name</label>
            <input name="account_name" id="account_name" value="{{ old('account_name', $model->account_name) }}" class="form-control @error('account_name') is-invalid @enderror" autocomplete="off">
            @error('account_name')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
          </div>
          <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $model->description) }}</textarea>
            @error('description')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
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