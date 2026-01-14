@extends('templates.main')

@section('title_page')
    VAT
@endsection

@section('breadcrumb_title')
    accounting / vat
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-vat-links page="sales" status="complete" />

            <div class="card">
                <div class="card-header">
                    <a
                        href="{{ route('accounting.vat.index', ['page' => 'sales', 'status' => 'incomplete']) }}">Incomplete</a>
                    | COMPLETE
                </div>
                <div class="card-body">
                    <table id="sales-complete" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Invoice</th>
                                <th>Faktur</th>
                                <th>AR Invoice DocNum</th>
                                <th>JE Num</th>
                                <th>IDR</th>
                                <td></td>
                            </tr>
                        </thead>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div> <!-- /.col -->
    </div> <!-- /.row -->
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
            /* font-weight: bold; */
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
        $(function() {
            const table = $("#sales-complete").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('accounting.vat.data') }}",
                    data: {
                        page: 'sales',
                        status: 'complete'
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'customer'
                    },
                    {
                        data: 'invoice'
                    },
                    {
                        data: 'faktur'
                    },
                    {
                        data: 'sap_ar_doc_num'
                    },
                    {
                        data: 'sap_je_num'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                fixedHeader: true,
            });

            // Handle Submit to SAP button click
            $(document).on('click', '.submit-to-sap-btn', function() {
                const fakturId = $(this).data('faktur-id');
                const fakturNo = $(this).data('faktur-no');
                const invoiceNo = $(this).data('invoice-no');
                const dpp = $(this).data('dpp');
                const btn = $(this);

                Swal.fire({
                    title: 'Submit to SAP B1?',
                    html: `
                        <div class="text-left">
                            <p><strong>Invoice No:</strong> ${invoiceNo || fakturNo}</p>
                            <p><strong>Faktur No:</strong> ${fakturNo || '-'}</p>
                            <p><strong>DPP Amount:</strong> ${dpp ? new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR'}).format(dpp) : '-'}</p>
                            <p class="mt-3">This will create:</p>
                            <ul>
                                <li>AR Invoice in SAP B1</li>
                                <li>Journal Entry (Revenue + AR)</li>
                            </ul>
                            <p class="text-warning mt-3"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Submit',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        const url = `{{ route('accounting.vat.submit-to-sap', 0) }}`.replace(/\/fakturs\/0\//, `/fakturs/${fakturId}/`);
                        return fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({})
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(data => {
                                    throw new Error(data.message || 'Submission failed');
                                });
                            }
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error.message}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value.success) {
                            Swal.fire({
                                title: 'Success!',
                                html: `
                                    <p>AR Invoice and Journal Entry created successfully.</p>
                                    <p><strong>AR Doc:</strong> ${result.value.ar_doc_num || '-'}</p>
                                    <p><strong>JE Num:</strong> ${result.value.je_num || '-'}</p>
                                `,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                table.ajax.reload();
                            });
                        } else if (result.value.partial) {
                            Swal.fire({
                                title: 'Partial Success',
                                html: `
                                    <p>AR Invoice created successfully, but Journal Entry failed.</p>
                                    <p><strong>AR Doc:</strong> ${result.value.ar_doc_num || '-'}</p>
                                    <p class="text-danger mt-2"><strong>Error:</strong> ${result.value.message}</p>
                                `,
                                icon: 'warning',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                table.ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: result.value.message || 'Submission failed',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    }
                });
            });
        });
    </script>
@endsection
