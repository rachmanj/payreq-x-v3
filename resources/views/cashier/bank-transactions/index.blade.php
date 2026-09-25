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
    @include('partials.vj-soft-ui-styles')
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-university"></i> Bank Transactions
                        </h3>
                        <a href="{{ route('cashier.bank-transactions.create') }}" class="vj-btn vj-btn-primary">
                            <i class="fas fa-plus"></i> Create New
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="bank-transactions-table"
                                class="table table-bordered table-striped table-hover">
                                <thead>
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
                                <tbody>
                                </tbody>
                            </table>
                        </div>
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
                order: [],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'date',
                        name: 'date',
                        orderable: false,
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
                        orderable: false,
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
                        name: 'status',
                        orderable: false,
                    },
                    {
                        data: 'created_by',
                        name: 'created_by',
                        orderable: false,
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

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
