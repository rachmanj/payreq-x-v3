@extends('templates.main')

@section('title_page')
    My Payreqs
@endsection

@section('breadcrumb_title')
    payreqs
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-invoice-dollar"></i> Payment Request
                        </h3>
                        @if ($enable_payreq)
                            <button type="button" class="vj-btn vj-btn-primary" data-toggle="modal"
                                data-target="#new-payreq">
                                <i class="fas fa-plus"></i> New Payreq
                            </button>
                        @else
                            <button type="button" class="vj-btn vj-btn-primary" disabled>
                                <i class="fas fa-plus"></i> New Payreq
                            </button>
                        @endif
                    </div>

                    @if ($overdue_payreqs > 0 || $overdue_realizations > 0)
                        <div class="card-body border-bottom py-2">
                            @if ($overdue_payreqs > 0)
                                <div class="vj-alert vj-alert-danger mb-2">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    Terdapat <strong>{{ $overdue_payreqs }}</strong> Payreq Advance yang Overdue.
                                    Silahkan buat Realization terlebih dahulu.
                                    <a href="{{ route('user-payreqs.overdue-documents.index') }}"
                                        class="font-weight-bold ml-1"><u>Lihat dokumen overdue</u></a>
                                </div>
                            @endif
                            @if ($overdue_realizations > 0)
                                <div class="vj-alert vj-alert-danger mb-0">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    Terdapat <strong>{{ $overdue_realizations }}</strong> dokumen Realization yang belum
                                    diserahkan ke Accounting. Silahkan segera diselesaikan.
                                    <a href="{{ route('user-payreqs.overdue-documents.index') }}"
                                        class="font-weight-bold ml-1"><u>Lihat dokumen overdue</u></a>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="card-body">
                        <table id="mypayreqs" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Payreq No</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>IDR</th>
                                    <th>Remarks</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="new-payreq">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Payment Request Type</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="vj-modal-type-options">
                        <a href="{{ route('user-payreqs.advance.create') }}" class="vj-btn vj-btn-success">
                            <i class="fas fa-hand-holding-usd"></i> Advance
                        </a>
                        <a href="{{ route('user-payreqs.reimburse.create') }}" class="vj-btn vj-btn-primary">
                            <i class="fas fa-receipt"></i> Reimburse
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            $("#mypayreqs").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('user-payreqs.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor'
                    },
                    {
                        data: 'type'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'submit_at'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'remarks'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [5],
                    "className": "text-right"
                }, ]
            })
        });
    </script>
    <script>
        $(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
        })
    </script>
@endsection
