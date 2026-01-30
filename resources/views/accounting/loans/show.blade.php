@extends('templates.main')

@section('title_page')
    Loans
@endsection

@section('breadcrumb_title')
    accounting / loans / installments
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <x-loan-links page="index" />

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Loan</h3>
                    {{-- create payreq --}}
                    <div class="card-tools">
                        {{-- <button href="#" data-toggle="modal" data-target="#installment-upload" class="btn btn-success btn-sm"> Upload</button> --}}
                        <a href="{{ route('accounting.loans.installments.generate', $loan->id) }}"
                            class="btn btn-success btn-sm"> Generate Installment</a>
                        {{-- <a href="{{ route('accounting.loans.installments.create') }}" class="btn btn-success btn-sm"> New</a> --}}
                        <a href="{{ route('accounting.loans.index') }}" class="btn btn-sm btn-primary float-right ml-2"><i
                                class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <dt class="col-sm-4">Loan Code</dt>
                        <dd class="col-sm-8">: {{ $loan->loan_code }}</dd>
                        <dt class="col-sm-4">Creditor</dt>
                        <dd class="col-sm-8">: {{ $loan->creditor->name }}</dd>
                        <dt class="col-sm-4">Principal</dt>
                        <dd class="col-sm-8">: IDR {{ number_format($loan->principal, 2) }}</dd>
                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8">: {{ $loan->description }}</dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">: {{ $loan->status ? ucfirst($loan->status) : '-' }}</dd>
                    </div>
                </div>

                <div class="card-body">
                    <table id="loans" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                {{-- <th>#</th> --}}
                                <th>Installment #</th>
                                <th>Due Date</th>
                                <th>Paid D</th>
                                <th>Bilyet No</th>
                                <th>Account</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>SAP Status</th>
                                <th>SAP Documents</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
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
        $(function() {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })

            $("#loans").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('accounting.loans.installments.data', $loan->id) }}',
                columns: [
                    // {data: 'DT_RowIndex', orderable: false, searchable: false},
                    {
                        data: 'angsuran_ke'
                    },
                    {
                        data: 'due_date'
                    },
                    {
                        data: 'paid_date'
                    },
                    {
                        data: 'bilyet_no'
                    },
                    {
                        data: 'account'
                    },
                    {
                        data: 'bilyet_amount'
                    },
                    {
                        data: 'payment_method'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'sap_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'sap_documents',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                        "targets": [1, 2],
                        "className": "text-center"
                    },
                    {
                        "targets": [0, 5],
                        "className": "text-right"
                    },
                    {
                        "targets": [6, 7, 8],
                        "className": "text-center"
                    },
                ]
            })
        });
    </script>
@endsection
