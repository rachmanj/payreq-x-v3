@extends('templates.main')

@section('title_page')
    Loans
@endsection

@section('breadcrumb_title')
    accounting / loans / installments
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Loan</h3>
            {{-- create payreq --}}
            <div class="card-tools">
                <a href="{{ route('accounting.loans.installments.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> New Installment
                </a>
                <a href="{{ route('accounting.loans.index') }}" class="btn btn-sm btn-primary float-right ml-2"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
          </div>

          <div class="card-body">
            <div class="row">
              <dt class="col-sm-4">Loan Code</dt>
              <dd class="col-sm-8">: {{ $realization->nomor }}</dd>
              <dt class="col-sm-4">Amount</dt>
              <dd class="col-sm-8">: IDR {{ number_format($realization->realizationDetails->sum('amount'), 2) }}</dd>
              <dt class="col-sm-4">Payreq No</dt>
              <dd class="col-sm-8">: {{ $realization->payreq->nomor }}</dd>
              <dt class="col-sm-4">Payreq Remark</dt>
              <dd class="col-sm-8">: {{ $realization->payreq->remarks }}</dd>
              <dt class="col-sm-4">Status</dt>
              <dd class="col-sm-8">: {{ $realization->status == 'submitted' ? 'Wait approve' : ucfirst($realization->status) }}</dd>
              <dt class="col-sm-4">Submitted at</dt>
              <dd class="col-sm-8">: {{ $submit_at->addHours(8)->format('d-M-Y H:i:s') . ' wita'  }}</dd>
              <dt class="col-sm-4">Created at</dt>
              <dd class="col-sm-8">: {{ $realization->created_at->addHours(8)->format('d-M-Y H:i:s') . ' wita' }}</dd>
            </div>
          </div>

          <div class="card-body">
              <table id="loans" class="table table-bordered table-striped" >
                  <thead>
                      <tr>
                          <th>#</th>
                          <th>InsCode</th>
                          <th>Creditor</th>
                          <th>Desc</th>
                          <th>Capital</th>
                          <th>StartD</th>
                          <th>CreatedBy</th>
                          <th></th>
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
      $("#loans").DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('accounting.loans.data') }}',
        columns: [
          {data: 'DT_RowIndex', orderable: false, searchable: false},
          {data: 'installment_code'},
          {data: 'creditor_name'},
          {data: 'description'},
          {data: 'capital'},
          {data: 'start_date'},
          {data: 'created_by'},
          {data: 'action', orderable: false, searchable: false},
        ],
        fixedHeader: true,
        columnDefs: [
                {
                  "targets": [4],
                  "className": "text-right"
                },
              ]
      })
    });
</script>
@endsection