@extends('templates.main')

@section('title_page')
Migrasi Payreqs
@endsection

@section('breadcrumb_title')
migrasi / payreqs / create
@endsection

@section('content')
    <div class="row">
      <div class="col-12">

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">New Payment Request Migrasi - Advance</h3>
            <a href="{{ route('cashier.migrasi.payreqs.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
          </div>
          <div class="card-body">
            <form action="{{ route('cashier.migrasi.payreqs.store') }}" method="POST">
              @csrf

              <input type="hidden" name="form_type" value="old_payreq">

              <div class="row">
                <div class="col-4">
                  <div class="form-group">
                    <label for="old_payreq_no">Old Payreq No <small>(nomor payreq lama)</small></label>
                    <input type="text" name="old_payreq_no" value="{{ old('old_payreq_no') }}" class="form-control">
                  </div>
                </div>
                <div class="col-4">
                  <div class="form-group">
                    <label for="paid_date">Paid Date</label>
                    <input type="date" name="paid_date" value="{{ old('paid_date') }}" class="form-control">
                  </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="requestor_id">Requestor Name</label>
                        <select name="requestor_id" id="requestor_id" class="form-control select2bs4 @error('requestor_id') is-invalid @enderror">
                          <option value="">-- select requestor name --</option>
                          @foreach ($requestors as $requestor)
                              <option value="{{ $requestor->id }}" {{ old('requestor_id') === $requestor->id ? 'selected' : '' }}>{{ $requestor->name }} - {{ $requestor->project }} - {{ $requestor->department->department_name }}</option>
                          @endforeach
                        </select>
                        @error('requestor_id')
                          <div class="invalid-feedback">
                            {{ $message }}
                          </div>
                        @enderror
                    </div>
                </div>
                
              </div>

              <div class="form-group">
                <label for="remarks">Purpose</label>
                <textarea name="remarks" id="remarks" cols="30" rows="2" class="form-control @error('remarks') is-invalid @enderror" autofocus>{{ old('remarks') }}</textarea>
                @error('remarks')
                <div class="invalid-feedback">
                  {{ $message }}
                </div>
                @enderror
              </div>

              <div class="row">
                <div class="col-6">
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
                {{-- <div class="col-6">
                  <div class="form-group">
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="customSwitch1" name="will_reduce">
                      <label class="custom-control-label" for="customSwitch1">Payreq ini mengurangi Saldo PC Aplikasi</label>
                    </div>
                  </div>
                </div> --}}
                <div class="col-6">
                  <div class="form-group">
                    <label for="cashier_id">Cashier Name</label>
                    <select name="cashier_id" id="cashier_id" class="form-control select2bs4 @error('cashier_id') is-invalid @enderror">
                      <option value="">-- select cashier name --</option>
                      @foreach ($cashiers as $cashier)
                          <option value="{{ $cashier->id }}" {{ old('cashier_id') === $cashier->id ? 'selected' : '' }}>{{ $cashier->name }} - {{ $cashier->project }}</option>
                      @endforeach
                    </select>
                    @error('cashier_id')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                    @enderror
                </div>
                </div>
              </div>
              

              <div class="card-footer">
                <div class="row">
                  {{-- <div class="col-6">
                    <button type="submit" class="btn btn-primary btn-block" id="btn-draft"><i class="fas fa-save"></i> Save as Draft</button>
                  </div> --}}
                  <div class="col-6">
                    <button type="submit" class="btn btn-warning btn-block" id="btn-submit"><i class="fas fa-paper-plane"></i> Save and Submit</button>
                  </div>
                </div>
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