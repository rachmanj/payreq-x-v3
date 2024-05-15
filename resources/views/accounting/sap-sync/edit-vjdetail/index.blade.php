@extends('templates.main')

@section('title_page')
Edit Detail VJ
@endsection

@section('breadcrumb_title')
    accounting / sap-sync
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">VJ No: {{ $vj->nomor }} | {{ $vj->project }} | Creator: {{ $vj->createdBy->name }}</h3>
        <a href="{{ route('accounting.sap-sync.show', $vj->id) }}" class="btn btn-sm btn-success float-right"><i class="fas fa-arrow-left"></i> Back</a> 
      </div>
      <div class="card-body">
        <table id="vj_details" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Account / Acc Name</th>
            <th>Desc</th>
            <th>Project</th>
            <th>CC</th>
            <th></th>
          </tr>
          </thead>
        </table>
      </div>
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
    $("#vj_details").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('accounting.sap-sync.edit_vjdetail_data', ['vj_id' => $vj->id]) }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'akun'},
        {data: 'description'},
        {data: 'project'},
        {data: 'cost_center'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [4],
                "className": "text-right"
              }
            ]
    })
  });
</script>
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
@endsection