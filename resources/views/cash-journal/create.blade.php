@extends('templates.main')

@section('title_page')
    Cash-Out Journals
@endsection

@section('breadcrumb_title')
    journals
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    @if ($select_all_button)
                    <a href="{{ route('cash-journals.move_all_tocart') }}" class="btn btn-sm btn-warning">Select All Documents to Cart</a>
                    @endif
                    <a href="{{ route('cash-journals.index') }}" class="btn btn-sm btn-primary float-right"><i class="fa fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <table id="to_cart" class="table table-borderd-table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>OutgoingID</th>
                                <th>OutgoingD</th>
                                <th>Project</th>
                                <th>PayReqNo</th>
                                <th>Amount</th>
                                <th>action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">CART</h3>
                    @if ($remove_all_button)
                    <a href="#" class="btn btn-sm btn-primary float-right" role="button" data-toggle="modal" data-target="#create-journal">Create Journal</a>
                    <a href="{{ route('cash-journals.remove_all_fromcart') }}" class="btn btn-sm btn-warning float-right mr-2">Remove All From Cart</a>
                    @endif
                </div>
                <div class="card-body">
                    <table id="in_cart" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>OutgoingID</th>
                                <th>OutgoingD</th>
                                <th>Project</th>
                                <th>PayReqNo</th>
                                <th>Amount</th>
                                <th>action</th>
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
                    <h5 class="modal-title">Create Journal</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('cash-journals.store') }}" method="POST">
                    @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
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
    // AVAILABLE DOCUMENTS
    $("#to_cart").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('cash-journals.to_cart.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'id'},
        {data: 'outgoing_date'},
        {data: 'project'},
        {data: 'payreq_no'},
        {data: 'amount'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [2, 3, 4],
                "className": "text-center"
              },
              {
                "targets": [5],
                "className": "text-right"
              }
            ]
    })

    // DOCUMENTS IN CART
    $("#in_cart").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('cash-journals.in_cart.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'id'},
        {data: 'outgoing_date'},
        {data: 'project'},
        {data: 'payreq_no'},
        {data: 'amount'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [2, 3, 4],
                "className": "text-center"
              },
              {
                "targets": [5],
                "className": "text-right"
              }
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
