@extends('templates.main')

@section('title_page')
    Verifications
@endsection

@section('breadcrumb_title')
    verifications
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Verifications</h3>
        <button type="button" class="btn btn-sm btn-primary float-right" data-toggle="modal" data-target="#modal-create-verification">
          <i class="fas fa-plus"></i> New Verification
        </button>
      </div>
      
      <div class="card-body">
        <table id="verifications" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Realization No</th>
            <th>Realization Date</th>
            <th>Payreq No</th>
            <th>Employee</th>
            <th>Project</th>
            <th>Status</th>
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
<div class="modal fade" id="modal-create-verification">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">New Verification</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form action="{{ route('verifications.store') }}" method="POST">
        @csrf

        <div class="modal-body">
          <div class="row">
            <div class="col-12">
              <div class="form-group">
                  <label for="realization_id">Realization No</label>
                  <select name="realization_id" class="form-control select2bs4 @error('realization_id') is-invalid @enderror">
                    <option value="">-- select realization to verify --</option>
                    @foreach ($realizations as $realization)
                      <option value="{{ $realization->id }}">{{ $realization->requestor->name }} |Realization No.{{ $realization->nomor }} | IDR {{ $realization->realizationDetails->sum('amount') }} </option>
                    @endforeach
                  </select>
                  @error('realization_id')
                    <div class="invalid-feedback">
                      {{ $message }}
                    </div>
                  @enderror
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                  <label for="date">Verification Date</label>
                  <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date') ?? date('Y-m-d') }}">
                  @error('date')
                    <div class="invalid-feedback">
                      {{ $message }}
                    </div>
                  @enderror
              </div>
            </div>
          </div>

        </div>

        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary btn-sm">Create</button>
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
    $("#verifications").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('verifications.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'realization_no'},
        {data: 'date'},
        {data: 'payreq_no'},
        {data: 'requestor'},
        {data: 'project'},
        {data: 'status'},
        // {data: 'amount'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
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