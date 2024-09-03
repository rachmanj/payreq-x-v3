@extends('templates.main')

@section('title_page')
    RAB
@endsection

@section('breadcrumb_title')
    payreqs / rab / show
@endsection

@section('content')
<div class="row">
    <div class="col-12">
      <div class="card card-info">
        <div class="card-header">
          <h3 class="card-title">RAB Detail</h3>
          <a href="{{ route('reports.anggaran.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
          <dl class="row">
            <dt class="col-sm-4">RAB No</dt>
            <dd class="col-sm-8">: {{ $anggaran->nomor }} {{ $anggaran->rab_no != null ? '| ' . $anggaran->rab_no : '' }}</b> @if ($anggaran->filename) <a href="{{ asset('file_upload/') . '/'. $anggaran->filename }}" class='btn btn-xs btn-success' target=_blank>Show RAB</a> @endif</dd>
            <dt class="col-sm-4">Date</dt>
            <dd class="col-sm-8">: {{ date('d-M-Y', strtotime($anggaran->date)) }}</dd>
            <dt class="col-sm-4">Description</dt>
            <dd class="col-sm-8">: {{ $anggaran->description }}</dd>
            <dt class="col-sm-4">For Project | Usage</dt>
            <dd class="col-sm-8">: {{ $anggaran->rab_project }} | {{ ucfirst($anggaran->usage) }}</dd>
            <dt class="col-sm-4">Department</dt>
            <dd class="col-sm-8">: {{ $anggaran->createdBy->department->department_name }}</dd>
            <dt class="col-sm-4">Periode: Anggaran | OFR</dt>
            <dd class="col-sm-8">: {{ date('M Y', strtotime($anggaran->periode_anggaran)) }} | {{ date('M Y', strtotime($anggaran->periode_ofr)) }}</dd>
            <dt class="col-sm-4">Budget</dt>
            <dd class="col-sm-8">: Rp.{{ number_format($anggaran->amount, 2) }}</dd>
            <dt class="col-sm-4">Release to Date</dt>
            <dd class="col-sm-8">: Rp. {{ number_format($total_release, 2) }}
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8">: {{ ucfirst($anggaran->status) }}</dd>
            <dt class="col-sm-4">Created by</dt>
            <dd class="col-sm-8">: {{ $anggaran->createdBy->name }}</dd>
            <dt class="col-sm-4">Progress</dt>
            <dd class="col-sm-4">
              <div class="text-center"><small>{{ number_format($progres_persen, 2) }}%</small>
                <div class="progress">
                  <div class="progress-bar progress-bar-striped {{ $statusColor }} text-center" role="progressbar" style="width: {{ $progres_persen }}%" aria-valuenow="{{ $progres_persen }}" aria-valuemin="0" aria-valuemax="100">
                  </div>
                </div>
              </div>
            </dd>
          </dl>
        </div>
        <div class="card-body">
          <table id="payreq-buc" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Payreq No</th>
                <th>ApprovedDate</th>
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
      ajax: '{{ route('user-payreqs.anggarans.payreqs_data', $anggaran->id) }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'nomor'},
        {data: 'approved_at'},
        {data: 'employee'},
        {data: 'remarks'},
        {data: 'amount'},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [0, 5],
                "className": "text-right"
              }
            ]
    })
  });
</script>
@endsection