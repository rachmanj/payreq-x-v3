@extends('templates.main')

@section('title_page')
  RAB  
@endsection

@section('breadcrumb_title')
    rab
@endsection

@section('content')
    <div class="row">
      <div class="col-12">
        <div class="card card-info">
          <div class="card-header">
            <h3 class="card-title">RAB Detail</h3>
            <a href="{{ route('rabs.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-undo"></i> Back</a>
          </div>
          <div class="card-body">
            <dl class="row">
              <dt class="col-sm-4">RAB No</dt>
              <dd class="col-sm-8">: {{ $rab->rab_no }}</b> @if ($rab->filename) <a href="{{ asset('document_upload/') . '/'. $rab->filename }}" class='btn btn-xs btn-success' target=_blank>Show RAB</a> @endif</dd>
              <dt class="col-sm-4">Date</dt>
              <dd class="col-sm-8">: {{ date('d-M-Y', strtotime($rab->date)) }}</dd>
              <dt class="col-sm-4">Description</dt>
              <dd class="col-sm-8">: {{ $rab->description }}</dd>
              <dt class="col-sm-4">Project</dt>
              <dd class="col-sm-8">: {{ $rab->project_code }}</dd>
              <dt class="col-sm-4">Department</dt>
              <dd class="col-sm-8">: {{ $rab->department->department_name }}</dd>
              <dt class="col-sm-4">Budget</dt>
              <dd class="col-sm-8">: Rp.{{ number_format($rab->budget, 2) }}</dd>
              <dt class="col-sm-4">Release to Date</dt>
              <dd class="col-sm-8">: Rp.{{ number_format($total_release, 2) }} ({{ number_format($total_release / $rab->budget * 100, 2) }}%)</dd>
            </dl>
          </div>
          <div class="card-body">
            <table id="payreq-buc" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Payreq No</th>
                  <th>Date</th>
                  <th>Requestor</th>
                  <th>Remarks</th>
                  <th>Amount</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}"/>
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
<script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

<script>
  $(function () {
    $("#payreq-buc").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('rabs.payreq_data', $rab->id) }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'payreq_num'},
        {data: 'approve_date'},
        {data: 'employee'},
        {data: 'remarks'},
        {data: 'amount'},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [5],
                "className": "text-right"
              }
            ]
    })
  });
</script>
@endsection