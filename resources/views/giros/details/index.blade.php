@extends('templates.main')

@section('title_page')
  GIRO Detail  
@endsection

@section('breadcrumb_title')
    giro
@endsection

@section('content')
    <div class="row">
      <div class="col-12">
        <div class="card card-info">
          <div class="card-header">
            <h3 class="card-title">GIRO Detail</h3>
            <a href="{{ route('giros.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-undo"></i> Back</a>
          </div>
          <div class="card-body">
            <dl class="row">
              <dt class="col-sm-4">Nomor</dt>
              <dd class="col-sm-8">: {{ $giro->nomor }}</b> @if ($giro->filename) <a href="{{ asset('document_upload/') . '/'. $giro->filename }}" class='btn btn-xs btn-success' target=_blank>Show Giro</a> @endif</dd>
              <dt class="col-sm-4">Date</dt>
              <dd class="col-sm-8">: {{ date('d-M-Y', strtotime($giro->tanggal)) }}</dd>
              <dt class="col-sm-4">Bank | Account</dt>
              <dd class="col-sm-8">: {{ $giro->bank . ' | ' . $giro->account }}</dd>
              <dt class="col-sm-4">Remarks</dt>
              <dd class="col-sm-8">: {{ $giro->remarks }}</dd>
              <dt class="col-sm-4">Amount</dt>
              <dd class="col-sm-8">: <b>Rp.{{ number_format($amount, 2) }}</b></dd>
            </dl>
          </div>
          <div class="card-body">
            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-add-detail"><i class="fas fa-plus"></i> Add Detail
            </button>
          </div>
          <div class="card-body">
            <table id="giro-details" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Description</th>
                  <th>Amount</th>
                  <th>is Cash in?</th>
                  <th></th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal create --}}
    <div class="modal fade" id="modal-add-detail">
        <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
            <h4 class="modal-title"> New Detail</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>
            <form action="{{ route('giros.detail.store', $giro->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
    
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <input type="text" name="remarks" id="remarks" class="form-control @error('remarks') is-invalid @enderror">
                @error('remarks')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="form-group">
              <label for="account_id">Account No.<small>(optional)</small></label>
              <select name="account_id" class="form-control">
                <option value="">-- select account --</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name . ' - ' . $account->account_no }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="text" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror">
                @error('amount')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            {{-- <div class="form-group">
                <label for="is_cashin">Is Cash-In</label>
                <select name="is_cashin" id="is_cashin" class="form-control select2bs4">
                      <option value="0">No</option>
                      <option value="1">Yes</option>
                </select>
              </div> --}}
    
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
    $("#giro-details").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('giros.detail.data', $giro->id) }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'remarks'},
        {data: 'amount'},
        {data: 'is_cashin'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [2],
                "className": "text-right"
              }
            ]
    })
  });
</script>
@endsection