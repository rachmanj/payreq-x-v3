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
        <h3 class="card-title">Giro Account</h3>

        @hasanyrole('superadmin|admin|cashier')
        <button href="#" class="btn btn-sm btn-primary float-right" data-toggle="modal" data-target="#modal-create"><i class="fas fa-plus"></i> Giro Account</button>
        @endhasanyrole
      </div>  <!-- /.card-header -->
     
      <div class="card-body">
        <table id="giros" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Account No</th>
            <th>Name</th>
            <th>Bank</th>
            <th>Type</th>
            <th>Curr</th>
            <th>Project</th>
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
        <h4 class="modal-title"> New Giro Account</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('accounting.giros.store') }}" method="POST">
        @csrf
      <div class="modal-body">

        <div class="form-group">
          <label for="acc_no">Account No</label>
          <input name="acc_no" id="acc_no" class="form-control @error('acc_no') is-invalid @enderror">
          @error('acc_no')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
          <label for="acc_name">Account Name</label>
          <input name="acc_name" id="acc_name" class="form-control">
        </div>
        
        <div class="form-group">
          <label for="bank_id">Bank</label>
          <select name="bank_id" id="bank" class="form-control select2bs4">
            <option value="">-- Select Bank --</option>
            @foreach ($banks as $bank)
                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
            @endforeach
          </select>
        </div>
        
        <div class="form-group">
          <label for="type">Giro Type</label>
          <select name="type" id="type" class="form-control select2bs4">
                <option value="giro">Giro</option>
                <option value="tabungan">Tabungan</option>
          </select>
        </div>

        <div class="form-group">
          <label for="curr">Currency</label>
          <select name="curr" id="curr" class="form-control select2bs4">
                <option value="idr">IDR</option>
                <option value="usd">USD</option>
          </select>
        </div>

        <div class="form-group">
          <label for="project">Project</label>
          <select name="project" id="project" class="form-control select2bs4">
            @foreach ($projects as $project)
                <option value="{{ $project->code }}">{{ $project->code }}</option>
            @endforeach
          </select>
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
      ajax: '{{ route('accounting.giros.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'acc_no'},
        {data: 'acc_name'},
        {data: 'bank'},
        {data: 'type'},
        {data: 'curr'},
        {data: 'project'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [6],
                "className": "text-center"
              }
            ]
    })

     //Initialize Select2 Elements
     $('.select2bs4').select2({
      theme: 'bootstrap4'
    });

  });

</script>
@endsection