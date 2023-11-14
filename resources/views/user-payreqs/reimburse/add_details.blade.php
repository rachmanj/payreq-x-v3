@extends('templates.main')

@section('title_page')
    My Payreqs
@endsection

@section('breadcrumb_title')
    payreqs
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq No</h5>
                <span class="description-text">{{ $payreq->nomor }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq Type</h5>
                <span class="description-text">Reimbursement</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Project</h5>
                <span class="description-text">{{ auth()->user()->project }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Department</h5>
                <span class="description-text">{{ auth()->user()->department->department_name }}</span>
            </div>
        </div>
    </div>

    {{-- DETAILS SECTION --}}
    @include('user-payreqs.reimburse.add_details_form')
    
    {{-- SUMMARY SECTION --}}
    @include('user-payreqs.reimburse.add_details_table')

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