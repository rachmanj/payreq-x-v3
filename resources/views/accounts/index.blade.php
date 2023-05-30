@extends('templates.main')

@section('title_page')
  Account List  
@endsection

@section('breadcrumb_title')
    acount
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <button href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-create"><i class="fas fa-plus"></i> Account</button>
        <button href="#" class="btn btn-sm btn-success" data-toggle="modal" data-target="#accounts-upload"> Upload</button>
      </div>  <!-- /.card-header -->
     
      <div class="card-body">
        <table id="accounts-table" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Account No</th>
            <th>Account Name</th>
            <th>Type</th>
            <th>Project</th>
            <th>Description</th>
            <th></th>
          </tr>
          </thead>
        </table>
      </div> <!-- /.card-body -->
    </div> <!-- /.card -->
  </div> <!-- /.col -->
</div>  <!-- /.row -->

{{-- Modal Account create --}}

<div class="modal fade" id="modal-create">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title"> New Account</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form action="{{ route('accounts.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="row">
            <div class="col-4">
              <div class="form-group">
                <label for="account_number">Account No</label>
                <input name="account_number" id="account_number" value="{{ old('account_number') }}" class="form-control @error('account_number') is-invalid @enderror" autocomplete="off" autofocus>
                @error('account_number')
                  <div class="invalid-feedback">
                    {{ $message }}
                  </div>
                @enderror
              </div>
            </div>
            <div class="col-4">
              <div class="form-group">
                <label for="type">Type</label>
                <select name="type" id="type" class="form-control @error('type') is-invalid @enderror">
                  <option value="">-- Select Type --</option>
                  @foreach ($types as $type)
                    <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                  @endforeach
                </select>
                @error('type')
                  <div class="invalid-feedback">
                    {{ $message }}
                  </div>
                @enderror
              </div>
            </div>
            <div class="col-4">
              <div class="form-group">
                <label for="project">Project</label>
                <select name="type" id="type" class="form-control @error('type') is-invalid @enderror">
                  <option value="" >-- select project --</option>
                  @foreach ($projects as $project)
                      <option value="{{ $project }}">{{ $project }}</option>
                  @endforeach
                </select>
                @error('project')
                  <div class="invalid-feedback">
                    {{ $message }}
                  </div>
                @enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="account_name">Account Name</label>
            <input name="account_name" id="account_name" value="{{ old('account_name') }}" class="form-control @error('account_name') is-invalid @enderror" autocomplete="off">
            @error('account_name')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">
            {{ old('description') }}</textarea>
            @error('description')
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

{{-- MODAL UPLOAD --}}
<div class="modal fade" id="accounts-upload">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> Upload Accounts</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('accounts.upload') }}" enctype="multipart/form-data" method="POST">
        @csrf
      <div class="modal-body">
          <label>Pilih file excel</label>
          <div class="form-group">
            <input type="file" name='file_upload' required class="form-control">
          </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary btn-sm"> Upload</button>
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
      $("#accounts-table").DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('accounts.data') }}',
        columns: [
          {data: 'DT_RowIndex', orderable: false, searchable: false},
          {data: 'account_number'},
          {data: 'account_name'},
          {data: 'type'},
          {data: 'project'},
          {data: 'description'},
          {data: 'action', orderable: false, searchable: false},
        ],
        fixedHeader: true,
      })
    });
  </script>
@endsection