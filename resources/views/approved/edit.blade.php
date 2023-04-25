@extends('templates.main')

@section('title_page')
    Approved Payment Request
@endsection

@section('breadcrumb_title')
    approved
@endsection

@section('content')
    <div class="row">
      <div class="col-12">

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Edit Data</h3>
            <a href="{{ route('approved.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-undo"></i> Back</a>
          </div>
          <div class="card-body">
            <form action="{{ route('approved.update', $payreq->id) }}" method="POST" id="form-edit-payreq">
              @csrf @method('PUT')

              <div class="row">
                <div class="col-4">
                  <div class="form-group">
                    <label for="employee_id">Employee Name</label>
                    <select name="employee_id" id="employee_id" class="form-control select2bs4 @error('employee_id') is-invalid @enderror">
                      <option value="">-- select employee name --</option>
                      @foreach ($employees as $employee)
                          <option value="{{ $employee->id }}" {{ $payreq->user_id === $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                      @endforeach
                    </select>
                    @error('employee_id')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>
                <div class="col-4">
                  <div class="form-group">
                    <label for="payreq_num">Payreq No</label>
                    <input type="text" name="payreq_num" value="{{ old('payreq_num', $payreq->payreq_num) }}" class="form-control @error('payreq_num') is-invalid @enderror">
                    @error('payreq_num')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                      @enderror
                  </div>
                </div>
                <div class="col-4">
                  <div class="form-group">
                    <label for="budgeted">is Budgeted?</label>
                    <select name="budgeted" id="budgeted" class="form-control">
                      <option value="1" {{ $payreq->budgeted == 1 ? 'selected' : '' }}>Yes</option>
                      <option value="0" {{ $payreq->budgeted == 0 || $payreq->budgeted == null ? 'selected' : '' }}>Not yet</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                
                <div class="col-4">
                  <div class="form-group">
                    <label for="approve_date">Approved Date</label>
                    <input type="date" name="approve_date" value="{{ old('approve_date', $payreq->approve_date) }}" class="form-control @error('approve_date') is-invalid @enderror">
                    @error('approve_date')
                    <div class="invalid-feedback">
                      {{ $message }}
                    </div>
                    @enderror
                  </div>
                </div>
                <div class="col-4">
                  <div class="form-group">
                    <label for="payreq_type">Type</label>
                    <select name="payreq_type" id="payreq_type" class="form-control">
                      <option value="Advance" {{ $payreq->payreq_type === 'Advance' ? 'selected' : '' }}>Advance</option>
                      <option value="Other" {{ $payreq->payreq_type === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                  </div>
                </div>
                <div class="col-4">
                  <div class="form-group">
                    <label for="que_group">Priority</label>
                    <select name="que_group" id="que_group" class="form-control">
                      <option value="1" {{ $payreq->que_group === '1' ? 'selected' : '' }}>1</option>
                      <option value="2" {{ $payreq->que_group === '2' ? 'selected' : '' }}>2</option>
                      <option value="3" {{ $payreq->que_group === '3' ? 'selected' : '' }}>3</option>
                    </select>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-4">
                  <div class="form-group">
                    <label for="payreq_idr">Amount</label>
                    <input type="text" name="payreq_idr" id="payreq_idr" value="{{ old('payreq_idr', $payreq->payreq_idr) }}" class="form-control @error('payreq_idr') is-invalid @enderror">
                    @error('payreq_idr')
                    <div class="invalid-feedback">
                      {{ $message }}
                    </div>
                    @enderror
                  </div>
                </div>
                <div class="col-8">
                  <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" cols="30" class="form-control">{{ old('remarks', $payreq->remarks) }}</textarea>
                  </div>
                </div>
                </div>
              
                <div class="row">
                  <div class="col-6">
                    <div class="form-group">
                      <label for="advance_category_id">Adv Category</label>
                      <select name="advance_category_id" id="advance_category_id" class="form-control select2bs4">
                        <option value="">-- select adv category code --</option>
                        @foreach ($adv_categories as $category)
                          <option value="{{ $category->id }}" {{ $payreq->advance_category_id === $category->id ? 'selected' : '' }}>{{ $category->code . ' - ' . $category->description }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group">
                      <label for="rab_id">RAB No</label><small> Optional</small>
                      <select name="rab_id" id="rab_id" class="form-control select2bs4 @error('rab_id') is-invalid @enderror">
                        <option value="">-- select RAB No --</option>
                        @foreach ($rabs as $rab)
                            <option value="{{ $rab->id }}" {{ $payreq->rab_id == $rab->id ? 'selected' : '' }}>{{ $rab->rab_no }}</option>
                        @endforeach
                      </select>
                      @error('buc_id')
                        <div class="invalid-feedback">
                          {{ $message }}
                        </div>
                      @enderror
                    </div>
                  </div>
                </div>
              
            
            </form>
            </div> {{--card body --}}
            <div class="card-footer">
              <button type="submit" class="btn btn-primary btn-sm" form="form-edit-payreq"><i class="fas fa-save"></i> Save</button>
            </div>
          </div>

        </div>
      </div>
    </div>
@endsection

@section('styles')
  <!-- Select2 -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
<!-- Select2 -->
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  }) 
</script>
@endsection