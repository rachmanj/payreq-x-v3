@extends('templates.main')

@section('title_page')
  Transaksi List  
@endsection

@section('breadcrumb_title')
    transaksi
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <button href="#" class="btn btn-sm btn-success float-right" data-toggle="modal" data-target="#transaksi-create"><i class="fas fa-plus"></i> Transaksi</button>
      </div>  <!-- /.card-header -->
     
      <div class="card-body">
        <table id="transaksi-table" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>PostingD</th>
            <th>Account</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Desc</th>
            <th>User</th>
            <th>Created</th>
            <th></th>
          </tr>
          </thead>
        </table>
      </div> <!-- /.card-body -->
    </div> <!-- /.card -->
  </div> <!-- /.col -->
</div>  <!-- /.row -->

{{-- Modal transaksi create --}}
<div class="modal fade" id="transaksi-create">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> New Transaksi</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
      <form action="{{ route('transaksi.store') }}" method="POST" id="transaksi-create">
        @csrf
      <div class="modal-body">
        
        <div class="form-group">
          <label for="account_id">Account</label>
          <select name="account_id" id="account_id" class="form-control @error('account_id') is-invalid @enderror">
            <option value="">-- select account --</option>
            @foreach ($accounts as $account)
              <option value="{{ $account->id }}">{{ $account->account_no . ' - ' . $account->name }}</option>
            @endforeach
          </select>
          @error('account_id')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
          <label for="posting_date">Posting Date <small>(blank = today)</small></label>
          <input type="date" name="posting_date" id="posting_date" class="form-control" autocomplete="off">
        </div>

        <div class="form-group">
          <label for="type">Type</label>
          <select name="type" id="type" class="form-control">  
            <option value="plus">PLUS</option>
            <option value="minus">MINUS</option>
          </select>
        </div>

        <div class="form-group">
          <label for="amount">Amount</label>
          <input name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" autocomplete="off">
          @error('amount')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <input name="description" id="description" class="form-control @error('description') is-invalid @enderror" autocomplete="off">
        </div>
      
      </div> <!-- /.modal-body -->
      <div class="modal-footer float-left">
        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
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
    $("#transaksi-table").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('transaksi.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'posting_date'},
        {data: 'account'},
        {data: 'type'},
        {data: 'amount'},
        {data: 'description'},
        {data: 'created_by'},
        {data: 'created_at'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [4],
                "className": "text-right"
              },
              {
                "targets": [1, 3],
                "className": "text-center"
              }
            ]
    })
  });
</script>
@endsection