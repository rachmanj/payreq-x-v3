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
          <a href="{{ route('user-payreqs.anggarans.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
          @if ($spendingExceeded)
            <div class="alert alert-danger">Utilization exceeds 100% of this budget.</div>
          @elseif ($spendingWarning)
            <div class="alert alert-warning">Utilization is at or above the alert threshold ({{ (int) ($anggaran->warning_threshold ?? 80) }}%).</div>
          @endif
          <dl class="row">
            <dt class="col-sm-4">RAB No</dt>
            <dd class="col-sm-8">: {{ $anggaran->nomor }} {{ $anggaran->rab_no != null ? '| ' . $anggaran->rab_no : '' }} @if ($anggaran->filename) <a href="{{ asset('file_upload/') . '/'. $anggaran->filename }}" class='btn btn-xs btn-success' target=_blank>Show RAB</a> @endif</dd>
            <dt class="col-sm-4">Date | Periode</dt>
            <dd class="col-sm-8">: {{ date('d-M-Y', strtotime($anggaran->date)) }} | {{ $anggaran->periode_anggaran !== null ? date('d-M-Y', strtotime($anggaran->periode_anggaran)) : '-' }}</dd>
            <dt class="col-sm-4">Description</dt>
            <dd class="col-sm-8">: {{ $anggaran->description }}</dd>
            <dt class="col-sm-4">For Project</dt>
            <dd class="col-sm-8">: {{ $anggaran->rab_project }}</dd>
            <dt class="col-sm-4">Department</dt>
            <dd class="col-sm-8">: {{ $anggaran->createdBy->department->department_name }}</dd>
            <dt class="col-sm-4">Budget</dt>
            <dd class="col-sm-8">: Rp.{{ number_format($anggaran->amount, 2) }}</dd>
            <dt class="col-sm-4">Release to Date</dt>
            <dd class="col-sm-8">: Rp. {{ number_format($total_release, 2) }}</dd>
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8">: {{ ucfirst($anggaran->status) }}</dd>
            <dt class="col-sm-4">Fund status</dt>
            <dd class="col-sm-8">: {{ $anggaran->fund_status ?? 'pending' }}</dd>
            <dt class="col-sm-4">Alert threshold</dt>
            <dd class="col-sm-8">: {{ (int) ($anggaran->warning_threshold ?? 80) }}%</dd>
            <dt class="col-sm-4">Progress</dt>
            <dd class="col-sm-8">
              <div class="text-center"><small>{{ $progres_persen }}%</small>
                <div class="progress">
                  <div class="progress-bar progress-bar-striped {{ $statusColor }} text-center" role="progressbar" style="width: {{ $progres_persen }}%" aria-valuenow="{{ $progres_persen }}" aria-valuemin="0" aria-valuemax="100">
                  </div>
                </div>
              </div>
            </dd>
          </dl>
          @if ($anggaran->details->isNotEmpty())
            <h5 class="mt-3">Budget lines</h5>
            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead><tr><th>Account</th><th>Description</th><th class="text-right">Qty</th><th>Unit</th><th class="text-right">Unit price</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                  @foreach ($anggaran->details as $line)
                    <tr>
                      <td>{{ $line->account ? $line->account->account_number . ' — ' . $line->account->account_name : '—' }}</td>
                      <td>{{ $line->description }}</td>
                      <td class="text-right">{{ number_format((float) $line->qty, 4) }}</td>
                      <td>{{ $line->unit }}</td>
                      <td class="text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                      <td class="text-right">{{ number_format((float) $line->amount, 2) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
        <div class="card-header">
          <h3 class="card-title">Approval Status</h3>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Approver</th>
                <th>Status</th>
                <th>Comment</th>
                <th>Your reply</th>
                <th>Response at</th>
              </tr>
            </thead>
            <tbody>
              @if ($approval_plans->count() > 0)
                @foreach ($approval_plans as $key => $item)
                  <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $item->approver->name }}</td>
                    @foreach ($approval_plan_status as $statusKey => $value)
                      @if ($statusKey == $item->status)
                        <td>{{ $value }}</td>
                      @endif
                    @endforeach
                    <td>{{ $item->remarks }}</td>
                    @include('user-payreqs.partials.approval-plan-your-reply-cell', ['item' => $item])
                    <td>{{ $item->status === 0 ? ' - ' : $item->updated_at->format('d-M-Y H:i:s') . ' wita' }}</td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="6" class="text-center">No Approval Plans Found</td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>
        <div class="card-body">
          <table id="payreq-buc" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Payreq/Realz No</th>
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
@include('user-payreqs.partials.save-requestor-remark-script')
@endsection