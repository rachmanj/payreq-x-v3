@extends('templates.main')

@section('title_page')
    Verification Journals
@endsection

@section('breadcrumb_title')
    verifications
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    @if ($select_all_button)
                        {{-- <a href="{{ route('verifications.journal.move_all_tocart') }}" class="btn btn-sm btn-warning">Move All
                            to Cart</a> --}}
                        <button id="move_selected_to_cart" class="btn btn-primary btn-sm">Move selected to cart</button>
                    @endif

                    <a href="{{ route('verifications.journal.index') }}" class="btn btn-sm btn-primary float-right"><i
                            class="fa fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <table id="to_cart" class="table table-borderd-table-striped">
                        <thead>
                            <tr>
                                <th class="text-right">#</th>
                                <th><input type="checkbox" id="select_all"></th>
                                <th>Realization No</th>
                                <th>Employee</th>
                                <th>Project</th>
                                <th>Amount</th>
                                <th>rows</th>
                                {{-- <th>action</th> --}}
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
                    <h3 class="card-title">CART</h3><br>
                    @if ($rows_count_text)
                        <small class="text-red">Jumlah baris Realization Details Tidak boleh lebih dari {{ $max_rows }}
                            baris (in cart: {{ $realization_detail_rows }} baris) </small>
                    @else
                        <small class="text-green">Realization Details rows: {{ $realization_detail_rows }} </small>
                    @endif
                    @if ($submit_button)
                        <a href="#" class="btn btn-sm btn-primary float-right" role="button" data-toggle="modal"
                            data-target="#create-journal">Submit Journal</a>
                    @else
                        <a href="#" class="btn btn-sm btn-primary float-right disabled" role="button"
                            data-toggle="modal" data-target="#create-journal">Submit Journal</a>
                    @endif
                    @if ($remove_all_button)
                        {{-- <a href="{{ route('verifications.journal.remove_all_fromcart') }}"
                            class="btn btn-sm btn-warning float-right mr-2">Remove All From Cart</a> --}}
                        <button id="remove_selected_from_cart" class="btn btn-warning btn-sm float-right mr-2">Remove
                            selected
                            from
                            cart</button>
                    @endif
                </div>
                <div class="card-body">
                    <table id="in_cart" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th class="text-right">#</th>
                                <th><input type="checkbox" id="select_all_in_cart"></th>
                                <th>Realization No</th>
                                <th>Employee</th>
                                <th>Project</th>
                                <th>Amount</th>
                                <th>rows</th>
                                {{-- <th>action</th> --}}
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
                <form action="{{ route('verifications.journal.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="date">Posting Date <span style="color:red;">*</span></label>
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
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            // AVAILABLE DOCUMENTS
            var toCartTable = $("#to_cart").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('verifications.journal.tocart_data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    }, {
                        data: 'id',
                        render: function(data, type, row) {
                            return '<input type="checkbox" class="select-row" value="' + data +
                                '">';
                        },
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'realization_no'
                    },
                    {
                        data: 'employee'
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'r_detail_rows'
                    },
                    // {
                    //     data: 'action',
                    //     orderable: false,
                    //     searchable: false
                    // },
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [0, 5, 6],
                    "className": "text-right"
                }]
            })

            // DOCUMENTS IN CART
            var inCartTable = $("#in_cart").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('verifications.journal.incart_data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    }, {
                        data: 'id',
                        render: function(data, type, row) {
                            return '<input type="checkbox" class="select-row" value="' + data +
                                '">';
                        },
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'realization_no'
                    },
                    {
                        data: 'employee'
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'r_detail_rows'
                    },
                    // {
                    //     data: 'action',
                    //     orderable: false,
                    //     searchable: false
                    // },
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [0, 5, 6],
                    "className": "text-right"
                }]
            })

            // Select all checkboxes in to_cart table
            $('#select_all').on('click', function() {
                var rows = toCartTable.rows({
                    'search': 'applied'
                }).nodes();
                $('input[type="checkbox"]', rows).prop('checked', this.checked);
            });

            // Select all checkboxes in in_cart table
            $('#select_all_in_cart').on('click', function() {
                var rows = inCartTable.rows({
                    'search': 'applied'
                }).nodes();
                $('input[type="checkbox"]', rows).prop('checked', this.checked);
            });

            // Uncheck select all if one of the checkboxes in to_cart table is unchecked
            $('#to_cart tbody').on('change', 'input[type="checkbox"]', function() {
                if (!this.checked) {
                    var el = $('#select_all').get(0);
                    if (el && el.checked && ('indeterminate' in el)) {
                        el.indeterminate = true;
                    }
                }
            });

            // Uncheck select all if one of the checkboxes in in_cart table is unchecked
            $('#in_cart tbody').on('change', 'input[type="checkbox"]', function() {
                if (!this.checked) {
                    var el = $('#select_all_in_cart').get(0);
                    if (el && el.checked && ('indeterminate' in el)) {
                        el.indeterminate = true;
                    }
                }
            });

            // Move selected to cart
            $('#move_selected_to_cart').on('click', function() {
                var selectedIds = [];
                toCartTable.$('input[type="checkbox"]:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length > 0) {
                    $.ajax({
                        url: '{{ route('verifications.journal.move_selected_to_cart') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            ids: selectedIds
                        },
                        success: function(response) {
                            window.location.reload(); // Refresh the page
                        },
                        error: function(xhr) {
                            alert('An error occurred while moving selected items to cart.');
                        }
                    });
                } else {
                    alert('Please select at least one item.');
                }
            });

            // Remove selected from cart
            $('#remove_selected_from_cart').on('click', function() {
                var selectedIds = [];
                inCartTable.$('input[type="checkbox"]:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length > 0) {
                    $.ajax({
                        url: '{{ route('verifications.journal.remove_selected_from_cart') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            ids: selectedIds
                        },
                        success: function(response) {
                            window.location.reload(); // Refresh the page
                        },
                        error: function(xhr) {
                            alert('An error occurred while removing selected items from cart.');
                        }
                    });
                } else {
                    alert('Please select at least one item.');
                }
            });
        });
    </script>
@endsection
