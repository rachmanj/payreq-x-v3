@extends('templates.main')

@section('title_page')
    MODAL  
@endsection

@section('breadcrumb_title')
    modal
@endsection

@section('content')
<div class="row">
    <div class="col-12">
  
      <div class="card">
        <div class="card-header">
        <h3 class="card-title">Serah Terima Modal Cashier</h3>
        @hasanyrole('superadmin|admin|head_cashier')
        <button href="#" class="btn btn-sm btn-primary float-right" data-toggle="modal" data-target="#modal-head_cashier"><i class="fas fa-plus"></i> Awal Hari</button>
        @endhasanyrole
        @hasanyrole('cashier')
        @if ($cashier_button)
          <button href="#" class="btn btn-sm btn-primary float-right" data-toggle="modal" data-target="#modal-cashier"><i class="fas fa-plus"></i> Akhir Hari</button>
        @endif
        @endhasanyrole
        </div>  <!-- /.card-header -->
       
        <div class="card-body">
          <table id="cashier-modal" class="table table-bordered table-striped">
            <thead>
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>Type</th>
              <th>Diserahkan by</th>
              <th>Diterima by</th>
              <th>status</th>
              <th></th>
            </tr>
            </thead>
          </table>
        </div> <!-- /.card-body -->
      </div> <!-- /.card -->
    </div> <!-- /.col -->
  </div>  <!-- /.row -->
  
  {{-- Modal head cashier --}}
  <div class="modal fade" id="modal-head_cashier">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"> Penyerahan Modal Cashier</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('cashier.modal.store') }}" method="POST">
          @csrf
        <div class="modal-body">
  
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" name="date" id="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="type">Type</label>
                        <input type="text" class="form-control" value="Begining of Day" disabled>
                        <input type="hidden" name="type" value="bod">
                    </div>
                </div>
            </div>

            <div class="row">
              <div class="col-12">
                  <div class="form-group">
                    <label for="submit_amount">Jumlah diserahkan <small>(max Rp. {{ number_format($max_modal, 2) }})</small></label>
                    <input name="submit_amount" id="submit_amount" class="form-control @error('submit_amount') is-invalid @enderror" value="{{ old('submit_amount') }}">
                    @error('submit_amount')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                  </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                  <div class="form-group">
                    <label for="receiver">Cashier</label>
                    <select name="receiver" id="receiver" class="form-control">
                        <option value="">-- Pilih Cashier --</option>
                        @foreach ($cashiers as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                  </div>
              </div>
            </div>
  
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <input type="text" name="remarks" id="remarks" class="form-control @error('remarks') is-invalid @enderror">
                @error('remarks')
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

  {{-- Modal cashier --}}
  <div class="modal fade" id="modal-cashier">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"> Penyerahan Modal Cashier</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('cashier.modal.store') }}" method="POST">
          @csrf
        <div class="modal-body">
  
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" name="date" id="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="type">Type</label>
                        <input type="text" class="form-control" value="End of Day" disabled>
                        <input type="hidden" name="type" value="eod">
                    </div>
                </div>
            </div>

            <div class="row">
              <div class="col-12">
                  <div class="form-group">
                    <label for="submit_amount">Jumlah diserahkan <small>(closing balance = Rp. {{ $closing_balance }})</small></label>
                    <input name="submit_amount" id="submit_amount" class="form-control @error('submit_amount') is-invalid @enderror" value="{{ old('submit_amount') }}">
                    @error('submit_amount')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                  </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                  <div class="form-group">
                    <label for="receiver">Head Cashier</label>
                    <select name="receiver" id="receiver" class="form-control">
                        <option value="">-- Pilih Head Cashier --</option>
                        @foreach ($head_cashiers as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                  </div>
              </div>
            </div>
  
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <input type="text" name="remarks" id="remarks" class="form-control @error('remarks') is-invalid @enderror">
                @error('remarks')
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
    $("#cashier-modal").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('cashier.modal.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'date'},
        {data: 'type'},
        {data: 'submitter'},
        {data: 'receiver'},
        {data: 'status'},
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