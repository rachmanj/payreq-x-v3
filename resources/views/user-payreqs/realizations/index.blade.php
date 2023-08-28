@extends('templates.main')

@section('title_page')
    My Payreqs
@endsection

@section('breadcrumb_title')
    payreqs
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Realizations</h3>
        <button type="button" class="btn btn-sm btn-primary float-right" data-toggle="modal" data-target="#modal-create">
          <i class="fas fa-plus"></i> New Realization
        </button>
      </div>
      
      <div class="card-body">
        <table id="realizations" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Realization No</th>
            <th>Date</th>
            <th>Payreq No</th>
            <th>Status</th>
            <th>IDR</th>
            <th>Days</th>
            <th></th>
          </tr>
          </thead>
        </table>
      </div>

      <!-- /.card-body -->
    </div>
    <!-- /.card -->
  </div>
  <!-- /.col -->
</div>
<!-- /.row -->

{{-- MODAL CREATE --}}
<div class="modal fade" id="modal-create">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">New Realization</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form action="{{ route('user-payreqs.realizations.store') }}" method="POST">
        @csrf

        <div class="modal-body">
          
          <div class="row">
            <div class="col-4">
              <div class="form-group">
                <label for="realization_no">Realization No <small>(auto generated)</small></label>
                <input type="text" name="realization_no" value="{{ $realization_no }}" class="form-control" readonly>
              </div>
            </div>
            <div class="col-4">
              <div class="form-group">
                <label for="project">Project</label>
                <input type="text" name="project" value="{{ auth()->user()->project }}" class="form-control" readonly>
              </div>
            </div>
            <div class="col-4">
              <div class="form-group">
                <label for="department">Department</label>
                <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                <input type="text" name="department" value="{{ auth()->user()->department->department_name}}" class="form-control" readonly>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-12">
              <div class="form-group">
                  <label for="payreq_id">Payment Request No</label>
                  <select name="payreq_id" class="form-control select2bs4 @error('payreq_id') is-invalid @enderror">
                    <option value="">-- select payreq to realization --</option>
                    @foreach ($user_payreqs as $payreq)
                      <option value="{{ $payreq->id }}">Payreq No.{{ $payreq->nomor }} | Amount: IDR {{ $payreq->amount }} </option>
                    @endforeach
                  </select>
                  @error('payreq_id')
                    <div class="invalid-feedback">
                      {{ $message }}
                    </div>
                  @enderror
              </div>
            </div>
          </div>

        </div>

        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Add Details</button>
        </div>
      </form>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div> <!-- /.modal -->

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
    $("#realizations").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('user-payreqs.realizations.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'nomor'},
        {data: 'created_at'},
        {data: 'payreq_no'},
        {data: 'status'},
        {data: 'amount'},
        {data: 'days'},
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
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  })
</script>
@endsection