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
        <a href="#" class="text-dark">ON HAND</a> | 
        <a href="{{ route('cashier.bilyets.release_index') }}"> Released</a> |
        <a href="{{ route('cashier.bilyets.cair_index') }}"> Cair</a> |
        <a href="{{ route('cashier.bilyets.void_index') }}"> Void</a> |
        <a href="{{ route('cashier.bilyet-temps.index') }}"> Upload</a>
        @can('add_bilyet')
          <button href="#" class="btn btn-xs btn-success float-right" data-toggle="modal" data-target="#modal-create"><i class="fas fa-plus"></i> Bilyet</button>
          {{-- <a href="{{ route('cashier.bilyets.export') }}" class="btn btn-xs btn-primary float-right"> download template</a> --}}
        @endcan
      </div>  <!-- /.card-header -->
     
      <div class="card-body">
        <table id="giros" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Nomor</th>
              <th>Bank | Account</th>
              <th>Type</th>
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
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> New Bilyet</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('cashier.bilyets.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-body">

        <div class="row">
          <div class="col-2">
            <div class="form-group">
              <label for="prefix">Prefix</label>
              <input type="hidden" name="project" value="{{ auth()->user()->project }}">
              <input name="prefix" id="prefix" class="form-control @error('prefix') is-invalid @enderror">
              @error('prefix')
                <div class="invalid-feedback">
                  {{ $message }}
                </div>
              @enderror
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label for="nomor">Bilyet No</label>
              <input name="nomor" id="nomor" class="form-control @error('nomor') is-invalid @enderror">
              @error('nomor')
                <div class="invalid-feedback">
                  {{ $message }}
                </div>
              @enderror
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label for="type">Bilyet Type</label>
              <select name="type" id="giro_type" class="form-control select2bs4">
                    <option value="cek">Cek</option>
                    <option value="bilyet">BG</option>
                    <option value="loa">LOA</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label for="giro_id">Giro</label>
          <select name="giro_id" id="giro_id" class="form-control select2bs4">
            @foreach ($giros as $giro)
                <option value="{{ $giro->id }}">{{ $giro->acc_no . ' - ' . $giro->acc_name }}</option>
            @endforeach
          </select>
        </div>

        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label for="bilyet_date">Bilyet Date</label>
              <input type="date" name="bilyet_date" class="form-control">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label for="cair_date">Cair Date</label>
              <input type="date" name="cair_date" class="form-control">
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="remarks">Remarks</label>
          <textarea name="remarks" id="remarks" class="form-control"></textarea>
        </div>

        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label for="amount">Amount</label>
              <input type="number" name="amount" id="amount" class="form-control">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label for="file_upload">Upload bilyet <small>(optional)</small></label>
              <input type="file" name="file_upload" id="file_upload" class="form-control">
            </div>
          </div>
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
      ajax: '{{ route('cashier.bilyets.data') . '?status=onhand' }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'nomor'},
        {data: 'account'},
        {data: 'type'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [0],
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