@extends('templates.main')

@section('title_page')
  BILYET  
@endsection

@section('breadcrumb_title')
    bilyet
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <a href="{{ route('cashier.bilyets.index') }}">On hand</a> |
        <a href="{{ route('cashier.bilyets.release_index') }}"> Released</a> |
        <a href="{{ route('cashier.bilyets.cair_index') }}"> Cair</a> |
        <a href="{{ route('cashier.bilyets.void_index') }}"> Void</a> |
        <a href="#" class="text-dark"> UPLOAD</a>
        <a href="{{ asset('file_upload/') . '/bilyet_template.xlsx' }}" class="btn btn-xs btn-success float-right" target=_blank>download template</a>
        <a href="{{ route('cashier.bilyets.import') }}" class="btn btn-xs btn-warning float-right mx-2 {{ $import_button }}" data-toggle="modal" data-target="#modal-import"> Import</a>
        <a href="{{ route('cashier.bilyet-temps.truncate') }}" class="btn btn-xs btn-danger float-right {{ $empty_button }}" onclick="return confirm('Are you sure you want to delete all data in table?')"> Empty Table</a>
        <button href="#" class="btn btn-xs btn-primary float-right mr-2" data-toggle="modal" data-target="#modal-upload"> Upload</button>
      </div>  <!-- /.card-header -->
     
      <div class="card-body">
        <table id="giros" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Nomor</th>
              <th>status</th>
              {{-- <th>Giro ID</th> --}}
              <th>Giro Acc</th>
              <th>Type</th>
              <th>BilyetD</th>
              <th>CairD</th>
              <th>Amount</th>
              <th></th>
            </tr>
          </thead>
        </table>
      </div> <!-- /.card-body -->
    </div> <!-- /.card -->
  </div> <!-- /.col -->
</div>  <!-- /.row -->

<div class="modal fade" id="modal-upload">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> Upload Bilyets</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('cashier.bilyet-temps.upload') }}" enctype="multipart/form-data" method="POST">
        @csrf
      <div class="modal-body">
          <label>Pilih file excel</label>
          <div class="form-group">
            <input type="file" name='file_upload' required class="form-control">
          </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-sm btn-primary"> Upload</button>
      </div>
    </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade" id="modal-import">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> Import Bilyets to DB</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('cashier.bilyets.import') }}" method="POST">
        @csrf
      <div class="modal-body">
          <label>Receive Date</label>
          <div class="form-group">
            <input type="date" name='receive_date' required class="form-control">
          </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-sm btn-primary"> Import</button>
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
    $("#giros").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('cashier.bilyet-temps.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'nomor'},
        {data: 'status_duplikasi'},
        // {data: 'giro_id'},
        {data: 'acc_no'},
        {data: 'type'},
        {data: 'bilyet_date'},
        {data: 'cair_date'},
        {data: 'amount'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [7],
                "className": "text-right"
              }
            ]
    })

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  });
</script>
@endsection