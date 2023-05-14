@extends('templates.main')

@section('title_page')
    Parameters
@endsection

@section('breadcrumb_title')
    parameters
@endsection

@section('content')
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-create"><i class="fas fa-plus"></i> Parameter</button>
          </div>
          <div class="card-body">
            <table class="table table-bordered table-striped" id="parameters-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Param1</th>
                  <th>Param2</th>
                  <th>Value</th>
                  <th>Action</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modal-create">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> New Parameter</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form action="{{ route('parameters.store') }}" method="POST">
            @csrf
          <div class="modal-body">
            <div class="form-group">
              <label for="name1">Param1</label>
              <input type="text" name='name1' value="{{ old('name1') }}" class="form-control @error('name1') is-invalid @enderror" autofocus>
              @error('name1')
                <div class="invalid-feedback">
                  {{ $message }}
                </div>
              @enderror
            </div>    
            <div class="form-group">
              <label for="name2">Param2</label>
              <input type="text" name='name2' value="{{ old('name2') }}" class="form-control @error('name2') is-invalid @enderror">
              @error('name2')
                <div class="invalid-feedback">
                  {{ $message }}
                </div>
              @enderror
            </div>       
            <div class="form-group">
              <label for="param_value">Value</label>
              <input type="text" name='param_value' value="{{ old('param_value') }}" class="form-control @error('param_value') is-invalid @enderror">
              @error('param_value')
                <div class="invalid-feedback">
                  {{ $message }}
                </div>
              @enderror
            </div>
          </div>
          <div class="modal-footer float-left">
            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
          </div>
        </form>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
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
    $("#parameters-table").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('parameters.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'name1'},
        {data: 'name2'},
        {data: 'param_value'},
        {data: 'action'},
      ],
      fixedHeader: true,
    })
  });
</script>
@endsection