@extends('templates.main')

@section('title_page')
    Invoices
@endsection

@section('breadcrumb_title')
    invoices
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#multi-payment">Multi Payment</button>
        <a href="{{ route('invoices.paid.index') }}" class="float-right mx-2">Paid</a>
        <a href="#" class="float-right"><b>Wait Payment | </b></a>
        {{-- <a href="{{ route('invoices.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Invoice</a> --}}
      </div>
      <!-- /.card-header -->
      <div class="card-body">
        <table id="invoices" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>No.</th>
            <th>Vendor</th>
            <th>ARD | Days</th> {{-- Accounting Received Date  --}}
            <th>CRD | Days</th> {{-- Cashier Received Date  --}}
            <th>Amount</th>
            <th>Origin</th>
            {{-- <th>Days</th> --}}
            <th>Sender</th>
            <th></th>
          </tr>
          </thead>
        </table>
      </div>
      <!-- /.card-body -->
    </div>
    <!-- /.card -->
  </div>
  <!-- /.col -->
</div>
<!-- /.row -->

<div class="modal fade" id="multi-payment">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Multi Payment Invoices</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('invoices.multi_paid') }}" method="POST">
        @csrf @method('POST')
        <div class="modal-body">
          <div class="form-group">
            <label for="payment_date">Payment Date</label>
            <input type="date" name="payment_date" id="payment_date" class="form-control" value="{{ date('Y-m-d') }}">
          </div>
          <div class="form-group">
            <label for="account_id">Account No.</label>
            <select name="account_id" class="form-control">
                <option value="">-- not PC transaction --</option>
              @foreach (\App\Models\Account::orderBy('account_no', 'asc')->get() as $account)
                <option value="{{ $account->id }}">{{ $account->account_no . ' - ' . $account->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Pilih Invoices yang akan di Payment</label>
            <div class="select2-purple">
              <select name="invoices[]" class="select2 form-control" multiple="multiple" data-dropdown-css-class="select2-purple" data-placeholder="Select Invoices" style="width: 100%;">
                @foreach ($invoices as $item)
                  <option value="{{ $item->id }}">{{ $item->vendor_name . ' | ' . $item->nomor_invoice }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

@endsection

@section('styles')
    <!-- DataTables -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}"/>
  <!-- Select2 -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
<script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>
<!-- Select2 -->
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

<script>
  $(function () {
    $("#invoices").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('invoices.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'nomor_invoice'},
        {data: 'vendor_name'},
        {data: 'received_date'},
        {data: 'created_at'},
        {data: 'amount'},
        {data: 'origin'},
        // {data: 'days'},
        {data: 'sender_name'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [5],
                "className": "text-right"
              },
              {
                "targets": [3,4],
                "className": "text-center"
              },
            ]
    })
  });
</script>
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  }) 
</script>
@endsection