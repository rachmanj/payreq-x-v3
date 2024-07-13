@extends('templates.main')

@section('title_page')
    RAB
@endsection

@section('breadcrumb_title')
    payreqs / rab
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        
        <a href="{{ route('user-payreqs.anggarans.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> RAB</a>
       
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
      $("#payreq-anggarans").DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('user-payreqs.anggarans.data') }}',
        columns: [
          {data: 'DT_RowIndex', orderable: false, searchable: false},
          {data: 'nomor'},
          {data: 'type'},
          {data: 'realization_no'},
          {data: 'status'},
          {data: 'duration'},
          {data: 'amount'},
          {data: 'action', orderable: false, searchable: false},
        ],
        fixedHeader: true,
        columnDefs: [
                {
                  "targets": [5, 6],
                  "className": "text-right"
                },
              ]
      })
    });
</script>
@endsection