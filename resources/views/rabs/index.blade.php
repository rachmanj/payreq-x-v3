@extends('templates.main')

@section('title_page')
  RAB  
@endsection

@section('breadcrumb_title')
    rab
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">

        @hasanyrole('superadmin|admin|acc_cashier')
        <button href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-create"><i class="fas fa-plus"></i> RAB</button>
        @endhasanyrole
      </div>  <!-- /.card-header -->
     
      <div class="card-body">
        <table id="bucs" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>RAB No</th>
            <th>Date</th>
            <th>Project</th>
            <th>Budget</th>
            <th>Advance</th>
            <th>Realization</th>
            <th>Progress</th>
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
        <h4 class="modal-title"> New RAB</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('rabs.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-body">

        <div class="form-group">
          <label for="rab_no">RAB No</label>
          <input name="rab_no" id="rab_no" class="form-control @error('rab_no') is-invalid @enderror">
          @error('rab_no')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
          <label for="date">Date</label>
          <input type="date" name="date" class="form-control @error('date') is-invalid @enderror">
          @error('date')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <input type="text" name="description" id="description" class="form-control @error('description') is-invalid @enderror">
          @error('description')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
          <label for="project_code">Project</label>
          <select name="project_code" id="project_code" class="form-control select2bs4 @error('project_code') is-invalid @enderror">
            <option value="">-- select project --</option>
            @foreach ($projects as $project)
                <option value="{{ $project }}">{{ $project }}</option>
            @endforeach
          </select>
          @error('project_code')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
            <label for="department_id">Department</label>
            <select name="department_id" id="department_id" class="form-control select2bs4 @error('department_id') is-invalid @enderror">
              <option value="">-- select department --</option>
              @foreach ($departments as $department)
                  <option value="{{ $department->id }}">{{ $department->department_name }}</option>
              @endforeach
            </select>
            @error('department_id')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
        </div>

        <div class="form-group">
          <label for="budget">Budget</label>
          <input type="text" name="budget" id="budget" class="form-control @error('budget') is-invalid @enderror">
          @error('budget')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>

        <div class="form-group">
          <label for="file_upload">Upload RAB</label>
          <input type="file" name="file_upload" id="file_upload" class="form-control @error('file_upload') is-invalid @enderror">
          @error('file_upload')
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
    $("#bucs").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('rabs.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'rab_no'},
        {data: 'date'},
        {data: 'project_code'},
        {data: 'budget'},
        {data: 'advance'},
        {data: 'realization'},
        {data: 'progress'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [4, 5, 6, 7],
                "className": "text-right"
              }
            ]
    })
  });
</script>
@endsection