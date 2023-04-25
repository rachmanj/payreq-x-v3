@extends('templates.main')

@section('title_page')
    Permissions
@endsection

@section('breadcrumb_title')
    permissions
@endsection

@section('content')
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Permissions</div>
            <button type="button" class="btn btn-sm btn-primary float-right" data-toggle="modal" data-target="#modal-input"><i class="fas fa-plus"></i> Permission</button>
          </div>
          <div class="card-body">
            <table class="table table-bordered table-striped" id="permissions_table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Permission Name</th>
                  <th>Guard Name</th>
                  {{-- <th>Action</th> --}}
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modal-input">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> New Permission</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form action="{{ route('permissions.store') }}" method="POST">
            @csrf
          <div class="modal-body">
              <div class="form-group">
                <label for="name">Permission Name</label>
                <input type="text" name='name' class="form-control" autofocus>
              </div>          
              <div class="form-group">
                <label for="guard_name">Guard Name</label>
                <input type="text" name='guard_name' class="form-control">
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
    $("#permissions_table").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('permissions.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'name'},
        {data: 'guard_name'},
      ],
      fixedHeader: true,
    })
  });
</script>
@endsection