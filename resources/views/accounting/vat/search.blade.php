@extends('templates.main')

@section('title_page')
    VAT
@endsection

@section('breadcrumb_title')
    accounting / vat / search
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-vat-links page="search" status="complete" />

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Search Fakturs</h3>
                </div>
                <div class="card-body">
                    <form id="searchForm">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="faktur_no">Faktur Number</label>
                                    <input type="text" class="form-control" id="faktur_no" name="faktur_no">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="type">Type</label>
                                    <select class="form-control" id="type" name="type">
                                        <option value="">All</option>
                                        <option value="sales">Sales</option>
                                        <option value="purchase">Purchase</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="invoice_no">Invoice Number</label>
                                    <input type="text" class="form-control" id="invoice_no" name="invoice_no">
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="customer_name">Customer/Supplier Name</label>
                                    <select class="form-control select2bs4" id="customer_name" name="customer_name">
                                        <option value="">Select Customer/Supplier</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="doc_num">Document Number</label>
                                    <input type="text" class="form-control" id="doc_num" name="doc_num">
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-sm btn-primary">Search</button>
                                <button type="button" class="btn btn-sm btn-secondary" id="resetButton">Reset</button>
                            </div>
                        </div>
                    </form>
                </div> {{-- card-body --}}

                <div class="card-body">
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered table-striped" id="faktursTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Type</th>
                                    <th>Faktur</th>
                                    <th>Invoice</th>
                                    <th>Customer/Supplier</th>
                                    <th>Doc Number</th>
                                    <th>Amount</th>
                                    <th>Create Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div> {{-- card-body --}}

            </div> {{-- card --}}
        </div> {{-- col-12 --}}
    </div> {{-- row --}}
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />

    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .card-header .active {
            color: black;
            text-transform: uppercase;
        }
    </style>
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
        $(document).ready(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

            let table = $('#faktursTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('accounting.vat.search.data') }}',
                    data: function(d) {
                        d.faktur_no = $('#faktur_no').val();
                        d.type = $('#type').val();
                        d.invoice_no = $('#invoice_no').val();
                        d.customer_name = $('#customer_name').val();
                        d.doc_num = $('#doc_num').val();
                        d.search_clicked = window.searchClicked ? 1 : 0;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'faktur',
                        name: 'faktur_no'
                    },
                    {
                        data: 'invoice',
                        name: 'invoice_no'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'doc_num',
                        name: 'doc_num'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'create_date',
                        name: 'create_date'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [7, 'desc']
                ],
                drawCallback: function() {
                    $('[data-toggle="tooltip"]').tooltip();
                }
            });

            window.searchClicked = false;

            $('#searchForm').on('submit', function(e) {
                e.preventDefault();
                window.searchClicked = true;
                table.draw();
            });

            $('#resetButton').on('click', function() {
                $('#searchForm')[0].reset();
                $('.select2bs4').val('').trigger('change');
                window.searchClicked = false;
                table.draw();
            });
        });
    </script>
@endsection
