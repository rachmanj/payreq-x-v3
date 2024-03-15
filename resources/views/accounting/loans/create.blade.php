@extends('templates.main')

@section('title_page')
    Loans
@endsection

@section('breadcrumb_title')
    accounting / loans  / create
@endsection

@section('content')
<div class="row">
    <div class="col-12">

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">New Loan</h3>
          <a href="{{ route('accounting.loans.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
          <form action="{{ route('accounting.loans.store') }}" method="POST">
            @csrf

            <div class="row">
              <div class="col-4">
                <div class="form-group">
                  <label for="loan_code">Code</label>
                  <input type="text" name="loan_code" class="form-control" value="{{ old('loan_code') }}" >
                </div>
              </div>
              <div class="col-4">
                <div class="form-group">
                  <label for="creditor_id">Creditor Name</label>
                  <select name="creditor_id" class="form-control select2bs4 @error('creditor_id') is-invalid @enderror">
                    <option value="">-- select creditor name --</option>
                    @foreach ($creditors as $creditor)
                    <option value="{{ $creditor->id }}" {{ $creditor->id == old('creditor_id') ? "selected" : "" }}>{{ $creditor->name }}</option>
                    @endforeach
                  </select>
                  @error('creditor_id')
                  <div class="invalid-feedback">
                      {{ $message }}
                  </div>
                  @enderror
                </div>
              </div>
              <div class="col-4">
                <div class="form-group">
                  <label for="start_date">Start Date</label>
                  <input type="date" name="start_date" value="{{ old('start_date')}}" class="form-control">
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="remarks">Description</label>
              <textarea name="description" id="description" cols="30" rows="2" class="form-control">{{ old('description') }}</textarea>
            </div>

            <div class="row">
              <div class="col-4">
                <div class="form-group">
                  <label for="tenor">Tenor<small> (bulan) </small></label>
                  <input type="text" name="tenor" class="form-control" value="{{ old('tenor') }}" >
                </div>
              </div>
              <div class="col-4">
                <div class="form-group">
                  <label for="principal">Principal</label>
                  <input type="text" name="principal" value="{{ old('principal') }}" class="form-control" >
                </div>
              </div>
              <div class="col-4">
                <div class="form-group">
                  <label for="status">Status</label>
                  <input type="text" name="status" value="{{ old('status')}}" class="form-control">
                </div>
              </div>
            </div>
            
            <div class="card-footer">
              <div class="row">
                <div class="col-6">
                  <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save"></i> Save</button>
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
 {{-- axios --}}
 <script src="{{ asset('adminlte/axios/axios.min.js') }}"></script>
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  }) 
</script>
@endsection
