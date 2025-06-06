@extends('templates.main')

@section('title_page')
    Bank Transactions
@endsection

@section('breadcrumb_title')
    bank-transactions
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <style>
        .table th,
        .table td {
            padding: 0.4rem;
            font-size: 0.85rem;
        }

        .table thead th {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .dataTables_info,
        .dataTables_paginate {
            font-size: 0.85rem;
            margin-top: 0.5rem !important;
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header p-2">
                    <h3 class="card-title">Bank Transactions Data</h3>
                    <a href="{{ route('cashier.bank-transactions.create') }}" class="btn btn-sm btn-primary float-right">
                        <i class="fas fa-plus"></i> Create New
                    </a>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table id="bank-transactions-table" class="table table-sm table-bordered table-striped table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" width="3%">#</th>
                                    <th class="text-center" width="8%">Date</th>
                                    <th class="text-center" width="10%">Journal #</th>
                                    <th class="text-center" width="10%">SAP Journal #</th>
                                    <th class="text-center" width="7%">Project</th>
                                    <th class="text-center" width="25%">Description</th>
                                    <th class="text-center" width="10%">Amount</th>
                                    <th class="text-center" width="7%">Status</th>
                                    <th class="text-center" width="10%">Created By</th>
                                    <th class="text-center" width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <!-- DataTables will fill this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(function() {
            var table = $('#bank-transactions-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('cashier.bank-transactions.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'nomor',
                        name: 'nomor'
                    },
                    {
                        data: 'sap_journal_no',
                        name: 'sap_journal_no'
                    },
                    {
                        data: 'project',
                        name: 'project'
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        className: 'text-right',
                        render: function(data, type, row) {
                            return type === 'display' ?
                                new Intl.NumberFormat('id-ID', {
                                    minimumFractionDigits: 2
                                }).format(data) :
                                data;
                        }
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'created_by',
                        name: 'created_by'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            // Handle submit transaction button click
            $(document).on('click', '.submit-transaction', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');

                Swal.fire({
                    title: 'Submit Transaction?',
                    text: "Are you sure you want to submit this transaction? This will create an incoming record.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, submit it!',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#dc3545',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Handle delete transaction button click
            $(document).on('click', '.delete-transaction', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');

                Swal.fire({
                    title: 'Delete Transaction?',
                    text: "Are you sure you want to delete this transaction? This action cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, cancel',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
