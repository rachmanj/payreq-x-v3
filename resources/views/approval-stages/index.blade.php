@extends('templates.main')

@section('title_page')
    Approvals
@endsection

@section('breadcrumb_title')
    approvals
@endsection

@section('content')
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Approval Stages</h3>
            <button type="button" class="btn btn-sm btn-warning float-right mx-2" data-toggle="modal" data-target="#modal-auto-create"> Auto Generate</button>
            <button type="button" class="btn btn-sm btn-primary float-right" data-toggle="modal" data-target="#modal-create"><i class="fas fa-plus"></i> Stage</button>
          </div>
          <div class="card-body">
            <table class="table table-bordered" id="approval-stages-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Approver</th>
                  <th>Stages</th>
                  {{-- <th>Department</th>
                  <th>Approver</th> --}}
                  {{-- <th>Action</th> --}}
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- MODAL CREATE --}}
    <div class="modal fade" id="modal-create">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> New Stage</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form action="{{ route('approval-stages.store') }}" method="POST">
            @csrf
          <div class="modal-body">
            <div class="form-group">
              <label for="approver_id">Approver Name</label>
              <select name="approver_id" class="form-control select2bs4 @error('approver_id') is-invalid @enderror">
                  <option value="">-- select Approver --</option>
                  @foreach ($approvers as $approver)
                  <option value="{{ $approver->id }}" {{ $approver->id == old('approver_id') ? "selected" : "" }}>{{ $approver->name }}</option>
                  @endforeach
              </select>
              @error('approver_id')
              <div class="invalid-feedback">
                  {{ $message }}
              </div>
              @enderror
            </div> 
            <div class="form-group">
              <label for="project">Project</label>
              <select name="project" class="form-control select2bs4 @error('project') is-invalid @enderror">
                  <option value="">-- select Project --</option>
                  @foreach ($projects as $project)
                  <option value="{{ $project }}" {{ $project == old('project') ? "selected" : "" }}>{{ $project }}</option>
                  @endforeach
              </select>
              @error('project')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
              @enderror
            </div>

            <div class="form-group">
              <label>Select Departments</label>
              <div class="select2-purple">
                <select name="departments[]" class="select2 form-control" multiple="multiple" data-dropdown-css-class="select2-purple" data-placeholder="Select departments" style="width: 100%;">
                  @foreach ($departments as $item)
                    <option value="{{ $item->id }}">{{ $item->department_name }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Select Documents</label>
              <div class="select2-purple">
                <select name="documents[]" class="select2 form-control" multiple="multiple" data-dropdown-css-class="select2-purple" data-placeholder="Select documents" style="width: 100%;">
                  <option value="payreq">Payment Request</option>
                  <option value="realization">Realization</option>
                  <option value="rab">RAB</option>
                </select>
              </div>
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

    {{-- MODAL AUTO-CREATE --}}
    <div class="modal fade" id="modal-auto-create">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> Approval Stage Auto Generation</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form action="{{ route('approval-stages.auto_generate') }}" method="POST">
            @csrf
          <div class="modal-body">
            <div class="form-group">
              <label for="approver_id">Approver Name</label>
              <select name="approver_id" class="form-control select2bs4 @error('approver_id') is-invalid @enderror">
                  <option value="">-- select Approver --</option>
                  @foreach ($approvers as $approver)
                  <option value="{{ $approver->id }}" {{ $approver->id == old('approver_id') ? "selected" : "" }}>{{ $approver->name }}</option>
                  @endforeach
              </select>
              @error('approver_id')
              <div class="invalid-feedback">
                  {{ $message }}
              </div>
              @enderror
            </div> 
            <div class="form-group">
              <label for="project">Project</label>
              <select name="project" class="form-control select2bs4 @error('project') is-invalid @enderror">
                  <option value="">-- select Project --</option>
                  @foreach ($projects as $project)
                  <option value="{{ $project }}" {{ $project == old('project') ? "selected" : "" }}>{{ $project }}</option>
                  @endforeach
              </select>
              @error('project')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
              @enderror
            </div>

          </div>
          <div class="modal-footer float-left">
            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Generate</button>
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
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  }) 
</script>
<script>
  $(function () {
    $("#approval-stages-table").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('approval-stages.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'approver'},
        {data: 'stages'},
        // {data: 'department'},
        // {data: 'action'},
      ],
      fixedHeader: true,
    })
  });

  function deleteApprovalStage(id) {
  $.ajax({
          url: "{{ route('approval-stages.destroy', '') }}/" + id,
          type: 'DELETE',
          headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}' // Add CSRF token if needed
          },
          success: function(response) {
            // remove row from table
            $('#approval-stages-table').DataTable().row("#approval-stage-"+id).remove().draw();
          },
          error: function(xhr) {
              console.error(xhr.responseText);
          }
      });
}
</script>
@endsection