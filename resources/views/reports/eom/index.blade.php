@extends('templates.main')

@section('title_page')
End Of Month Adjusment
@endsection

@section('breadcrumb_title')
    reports / eom
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back to Index</a>
        <a href="#" class="btn btn-sm btn-primary float-right mr-2" role="button" data-toggle="modal" data-target="#create-journal">Generate Journal</a>
      </div>
      <div class="card-body">
        <table id="eom_journals" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>EOM Journal No</th>
            <th>Date</th>
            <th>Projects</th>
            <th>Status</th> {{-- posted or not posted --}}
            <th>Amount</th>
            <th>SAPJ No</th>
            <th>SAPJ Date</th>
            <th></th>
          </tr>
          </thead>
        </table>
      </div>
    </div>

  </div>
</div>

{{-- MODAL CREATE JOURNAL --}}
<div class="modal fade" id="create-journal">
  <div class="modal-dialog modal-md">
      <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title">Generate Journal</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <form action="{{ route('reports.eom.store') }}" method="POST">
              @csrf
          <div class="modal-body">
              <div class="form-group">
                  <label for="date">Posting Date <span style="color:red;">*</span></label>
                  <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
              </div>
              
              <div class="form-group">
                <label>Select Projects <small>(multiple select)</small></label>
                <div class="select2-purple">
                  <select name="projects[]" class="select2 form-control" multiple="multiple" data-dropdown-css-class="select2-purple" data-placeholder="Select projects" style="width: 100%;">
                    @foreach ($projects as $project)
                      <option value="{{ $project }}">{{ $project }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="form-group">
                  <label for="description">Description</label>
                  <input type="text" name="description" class="form-control">
              </div>
          </div>
          {{-- button --}}
          <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
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
    $("#eom_journals").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('reports.eom.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'nomor'},
        {data: 'date'},
        {data: 'projects'},
        {data: 'status'},
        {data: 'amount'},
        {data: 'sap_journal_no'},
        {data: 'sap_posting_date'},
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