

<button href="#" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#modal-edit-{{ $model->id }}"><i class="fas fa-edit"></i></button>

{{-- Modal create --}}
<div class="modal fade" id="modal-edit-{{ $model->id }}">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"> New Giro Account</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('accounting.giros.update', $model->id) }}" method="POST">
          @csrf @method('PUT')
        <div class="modal-body">
  
          <div class="form-group">
            <label for="acc_no">Account No</label>
            <input name="acc_no" id="acc_no" class="form-control @error('acc_no') is-invalid @enderror" value="{{ old('acc_no', $model->acc_no) }}"> 
            @error('acc_no')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
          </div>
  
          <div class="form-group">
            <label for="acc_name">Account Name</label>
            <input name="acc_name" id="acc_name" class="form-control" value="{{ old('acc_name', $model->acc_name) }}">
          </div>
          
          <div class="form-group">
            <label for="bank_id">Bank</label>
            <select name="bank_id" id="bank" class="form-control select2bs4">
              @foreach (App\Models\Bank::orderBy('name')->get() as $bank)
                  <option value="{{ $bank->id }}" {{ $bank->id == $model->bank_id ? 'selected' : '' }}>{{ $bank->name }}</option>
              @endforeach
            </select>
          </div>
          
          <div class="form-group">
            <label for="type">Giro Type</label>
            <select name="type" id="type" class="form-control select2bs4">
                  <option value="giro">Giro</option>
                  <option value="tabungan">Tabungan</option>
            </select>
          </div>
  
          <div class="form-group">
            <label for="curr">Currency</label>
            <select name="curr" id="curr" class="form-control select2bs4">
                  <option value="idr" {{ $model->curr == 'idr' ? 'selected' : '' }}>IDR</option>
                  <option value="usd" {{ $model->curr == 'usd' ? 'selected' : '' }}>USD</option>
            </select>
          </div>
  
          <div class="form-group">
            <label for="project">Project</label>
            <select name="project" id="project" class="form-control select2bs4">
              @foreach (App\Models\Project::orderBy('code')->get() as $project)
                  <option value="{{ $project->code }}">{{ $project->code }}</option>
              @endforeach
            </select>
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