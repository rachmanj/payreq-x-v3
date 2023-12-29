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
        <button href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-create"><i class="fas fa-plus"></i> Document No</button>
        <button href="#" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#modal-auto-generate"> Auto Generate</button>
      </div>  <!-- /.card-header -->
     
      <div class="card-body">
        <table id="document" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Year</th>
            <th>Project</th>
            <th>Doc Type</th>
            <th>Last Number</th>
            <th></th>
          </tr>
          </thead>
        </table>
      </div> <!-- /.card-body -->
    </div> <!-- /.card -->
  </div> <!-- /.col -->
</div>  <!-- /.row -->

{{-- Modal manual creation --}}
<div class="modal fade" id="modal-create">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> New Document Parameter</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('document-number.store') }}" method="POST">
        @csrf
      <div class="modal-body">

        <div class="form-group">
          <label for="document_type">Document Type</label>
          <select name="document_type" id="document_type" class="form-control select2bs4">
            <option value="">-- Select Document Type --</option>
            @foreach ($document_types as $type)
                <option value="{{ $type }}">{{ $type }}</option>
            @endforeach
          </select>
          @error('document_type')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
          <label for="project">Project</label>
          <select name="project" id="project" class="form-control select2bs4">
            @foreach ($projects as $project)
                <option value="{{ $project->code }}">{{ $project->code }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label for="year">For Year</label>
          <input type="text" name="year" class="form-control @error('year') is-invalid @enderror">
          @error('year')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
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

{{-- Modal auto creation --}}
<div class="modal fade" id="modal-auto-generate">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> Document Number Auto Generate</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('document-number.auto_generate') }}" method="POST">
        @csrf
      <div class="modal-body">

        <div class="form-group">
          <label for="year">For Year</label>
          <input type="text" name="year" class="form-control">
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
    $("#document").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('document-number.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'year'},
        {data: 'project'},
        {data: 'document_type'},
        {data: 'last_number'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [4],
                "className": "text-center"
              }
            ]
    })
  });
</script>
@endsection