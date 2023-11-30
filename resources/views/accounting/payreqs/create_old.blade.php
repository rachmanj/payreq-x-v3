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
            <h3 class="card-title">New Payment Request</h3>
            <a href="{{ route('accounting.payreqs.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-undo"></i> Back</a>
          </div>
          <div class="card-body">
            <form action="{{ route('accounting.payreqs.store') }}" method="POST">
              @csrf

              <div class="row">
                <div class="col-4">
                  <div class="form-group">
                    <label for="employee_id">Employee Name</label>
                    <select name="employee_id" id="employee_id" class="form-control select2bs4 @error('employee_id') is-invalid @enderror">
                      <option value="">-- select employee name --</option>
                      @foreach ($employees as $employee)
                          <option value="{{ $employee->id }}" {{ old('employee_id') === $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
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
                    <label for="nomor">Payreq No</label>
                    <input type="text" name="nomor" value="{{ old('nomor') }}" class="form-control @error('nomor') is-invalid @enderror" autocomplete="off">
                    @error('nomor')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                      @enderror
                  </div>
                </div>
              </div>
  
              <div class="row">
                <div class="col-4">
                  <div class="form-group">
                    <label for="approved_at">Approved Date</label>
                    <input type="date" name="approved_at" value="{{ old('approved_at') }}" class="form-control @error('approved_at') is-invalid @enderror">
                    @error('approved_at')
                    <div class="invalid-feedback">
                      {{ $message }}
                    </div>
                    @enderror
                  </div>
                </div>
                <div class="col-4">
                  <div class="form-group">
                    <label for="type">Type</label>
                    <select name="type" id="type" class="form-control">
                      <option value="advance" {{ old('type') === 'advance' ? 'selected' : '' }}>Advance</option>
                      <option value="reimburse" {{ old('type') === 'reimburse' ? 'selected' : '' }}>Other</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-4">
                  <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="text" name="amount" id="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror">
                    @error('amount')
                    <div class="invalid-feedback">
                      {{ $message }}
                    </div>
                    @enderror
                  </div>
                </div>
                <div class="col-8">
                  <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" cols="30" rows="2" class="form-control">{{ old('remarks') }}</textarea>
                  </div>
                </div>
              </div>

              <div class="row">
                
                <div class="col-6">
                  <div class="form-group">
                    <label for="rab_id">RAB No</label><small> (Pilih RAB No jika merupakan payreq utk RAB)</small>
                    <select name="rab_id" id="rab_id" class="form-control select2bs4 @error('rab_id') is-invalid @enderror">
                      <option value="">-- select RAB No --</option>
                      @foreach ($rabs as $rab)
                          <option value="{{ $rab->id }}">{{ $rab->rab_no }}</option>
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

              <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save</button>
              </div>
            </form>

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