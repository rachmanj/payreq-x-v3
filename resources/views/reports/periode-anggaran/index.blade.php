@extends('templates.main')

@section('title_page')
    Periode Anggaran
@endsection

@section('breadcrumb_title')
    periode-anggaran
@endsection

@section('content')
<div class="row">
    <div class="col-12">
  
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Setup Periode Anggaran</h3>
          <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary float-right"> <i class="fas fa-arrow-left"></i> Back</a>
          <button type="button" class="btn btn-sm btn-warning float-right mx-2" data-toggle="modal" data-target="#modal-bulk-generate">
              <i class="fas fa-magic"></i> Generate Monthly Periods
          </button>
          <button href="#" class="btn btn-sm btn-success float-right mx-2" data-toggle="modal" data-target="#modal-create"><i class="fas fa-plus"></i> Periode Anggaran</button>
        </div>  <!-- /.card-header -->
       
        <div class="card-body">
          <table id="periode-anggaran" class="table table-bordered table-striped">
            <thead>
            <tr>
              <th>#</th>
              <th>Periode</th>
              <th>Type</th>
              <th>Project</th>
              <th>is-active</th>
              <th>Desc</th>
              <th></th>
            </tr>
            </thead>
          </table>
        </div> <!-- /.card-body -->
      </div> <!-- /.card -->
    </div> <!-- /.col -->
  </div>  <!-- /.row -->
  
  {{-- modal-create --}}
  <div class="modal fade" id="modal-create">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"> Create Periode Anggaran</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('reports.periode-anggaran.store') }}" method="POST">
          @csrf
        <div class="modal-body">
  
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="periode">Periode</label>
                        <input type="month" name="periode" id="periode" class="form-control">
                    </div>
                </div>
                <div class="col-6">
                  <div class="form-group">
                    <label for="project">Project</label>
                    <select name="project" class="form-control">
                        @foreach ($projects as $project)
                            <option value="{{ $project->code }}">{{ $project->code }}</option>
                        @endforeach
                    </select>
                  </div>
                </div>
            </div>

            <div class="row">
              <div class="col-6">
                  <div class="form-group">
                      <label for="is_active">Is Active</label>
                      <div class="form-check">
                          <input class="form-check-input" type="radio" value="yes" name="is_active" >
                          <label class="form-check-label">Yes</label>
                      </div>
                      <div class="form-check">
                          <input class="form-check-input" type="radio" name="is_active" value="no" checked>
                          <label class="form-check-label">No</label>
                      </div>
                  </div>
              </div>
              <div class="col-6">
                <label for="periode_type">Type</label>
                <div class="form-group">
                    <div class="form-check d-inline mr-4">
                        <input class="form-check-input" type="radio" name="periode_type" value="anggaran" checked>
                        <label class="form-check-label">Anggaran</label>
                    </div>
                    <div class="form-check mr-4">
                        <input class="form-check-input" type="radio" name="periode_type" value="ofr">
                        <label class="form-check-label">OFR</label>
                    </div>
                </div>
              </div>
          </div>

            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" name="description" id="description" class="form-control">
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
  {{-- modal-bulk-generate --}}
  <div class="modal fade" id="modal-bulk-generate">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Generate Monthly Periods</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('reports.periode-anggaran.bulk-generate') }}" method="POST">
          @csrf
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="bulk_periode">Periode</label>
                  <input type="month" name="periode" id="bulk_periode" class="form-control"
                    value="{{ old('periode', date('Y-m')) }}" required>
                </div>
              </div>
              <div class="col-md-6">
                <label>Type</label>
                <div class="form-group">
                  <div class="form-check d-inline mr-4">
                    <input class="form-check-input" type="checkbox" name="types[]" value="anggaran" id="bulk_type_anggaran"
                      {{ in_array('anggaran', old('types', ['anggaran', 'ofr']), true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="bulk_type_anggaran">Anggaran</label>
                  </div>
                  <div class="form-check d-inline">
                    <input class="form-check-input" type="checkbox" name="types[]" value="ofr" id="bulk_type_ofr"
                      {{ in_array('ofr', old('types', ['anggaran', 'ofr']), true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="bulk_type_ofr">OFR</label>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="bulk_projects">Projects</label>
              <div class="select2-purple">
                <select name="projects[]" id="bulk_projects" class="select2 form-control" multiple="multiple"
                  data-dropdown-css-class="select2-purple" data-placeholder="Select projects" style="width: 100%;" required>
                  <option value="all">All active projects</option>
                  @foreach ($projects as $project)
                    <option value="{{ $project->code }}"
                      {{ $activeSelectableProjects->contains('code', $project->code) ? 'selected' : '' }}>
                      {{ $project->code }}
                    </option>
                  @endforeach
                </select>
              </div>
              <small class="text-muted">Choose "All active projects" or select specific project codes.</small>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Is Active</label>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" value="yes" name="is_active" id="bulk_is_active_yes"
                      {{ old('is_active', 'yes') === 'yes' ? 'checked' : '' }}>
                    <label class="form-check-label" for="bulk_is_active_yes">Yes</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" value="no" name="is_active" id="bulk_is_active_no"
                      {{ old('is_active') === 'no' ? 'checked' : '' }}>
                    <label class="form-check-label" for="bulk_is_active_no">No</label>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="deactivate_previous" value="1"
                      id="bulk_deactivate_previous"
                      {{ old('deactivate_previous', '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="bulk_deactivate_previous">
                      Deactivate previous active period (same project + type)
                    </label>
                  </div>
                  <small class="text-muted">Prevents multiple active periods appearing in the RAB creation dropdown.</small>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="bulk_description">Description</label>
              <input type="text" name="description" id="bulk_description" class="form-control"
                value="{{ old('description') }}" placeholder="Optional, applied to all generated rows">
            </div>
          </div>
          <div class="modal-footer float-left">
            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
            <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-magic"></i> Generate</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@section('styles')
    <!-- DataTables -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}"/>
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
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

<script>
  $(function () {
    $('.select2').select2({
      theme: 'bootstrap4'
    });
  });
</script>
<script>
  $(function () {
    $("#periode-anggaran").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('reports.periode-anggaran.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'periode'},
        {data: 'periode_type'},
        {data: 'project'},
        {data: 'is_active'},
        {data: 'description'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      // columnDefs: [
      //         {
      //           "targets": [5, 6],
      //           "className": "text-right"
      //         }
      //       ]
    })
  });
</script>
@endsection