@extends('templates.main')

@section('title_page')
  GIRO  
@endsection

@section('breadcrumb_title')
    giro
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">

        @hasanyrole('superadmin|admin|acc_cashier')
        <button href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-create"><i class="fas fa-plus"></i> Giro</button>
        @endhasanyrole
      </div>  <!-- /.card-header -->
     
      <div class="card-body">
        <table id="giros" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Nomor</th>
            <th>Bank | Account</th>
            <th>Type</th>
            <th>Date</th>
            <th>Amount</th>
            <th></th>
          </tr>
          </thead>
        </table>
      </div> <!-- /.card-body -->
    </div> <!-- /.card -->
  </div> <!-- /.col -->
</div>  <!-- /.row -->

{{-- Modal create --}}
<div class="modal fade" id="modal-create">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> New Giro</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('giros.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-body">

        <div class="form-group">
          <label for="nomor">Document No</label>
          <input name="nomor" id="nomor" class="form-control @error('nomor') is-invalid @enderror">
          @error('nomor')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label for="bank">Bank</label>
              <select name="bank" id="bank" class="form-control select2bs4">
                @foreach ($banks as $bank)
                    <option value="{{ $bank }}">{{ $bank }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label for="account">Account</label>
              <select name="account" id="account" class="form-control select2bs4">
                @foreach ($accounts as $account)
                    <option value="{{ $account }}">{{ $account }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="giro_type">Giro Type</label>
          <select name="giro_type" id="giro_type" class="form-control select2bs4">
                <option value="cek">Cek</option>
                <option value="Bilyet">Bilyet Giro</option>
          </select>
        </div>

        <div class="form-group">
          <label for="tanggal">Date</label>
          <input type="date" name="tanggal" class="form-control @error('tanggal') is-invalid @enderror">
          @error('tanggal')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
          <label for="remarks">Remarks</label>
          <input type="text" name="remarks" id="remarks" class="form-control @error('remarks') is-invalid @enderror">
          @error('remarks')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
          <label for="file_upload">Upload giro</label>
          <input type="file" name="file_upload" id="file_upload" class="form-control">
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
    $("#giros").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('giros.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'nomor'},
        {data: 'bank'},
        {data: 'giro_type'},
        {data: 'tanggal'},
        {data: 'amount'},
        {data: 'action', orderable: false, searchable: false},
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