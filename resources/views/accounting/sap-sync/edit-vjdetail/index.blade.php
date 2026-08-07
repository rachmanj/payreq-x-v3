@extends('templates.main')

@section('title_page')
    Edit Detail VJ
@endsection

@section('breadcrumb_title')
    accounting / sap-sync
@endsection

@section('content')
    <div class="vj-show">
        <div class="vj-stat-grid mb-3">
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-hashtag"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">VJ Number</span>
                    <span class="vj-stat-value">{{ $vj->nomor }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-success">
                <div class="vj-stat-icon"><i class="fas fa-project-diagram"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Project</span>
                    <span class="vj-stat-value">{{ $vj->project }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-neutral">
                <div class="vj-stat-icon"><i class="fas fa-user"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Creator</span>
                    <span class="vj-stat-value">{{ $vj->createdBy->name }}</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-invoice"></i> VJ Details
                        </h3>
                        <a href="{{ route('accounting.sap-sync.show', $vj->id) }}" class="vj-action-item vj-action-print">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back</span>
                        </a>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="vj_details" class="table table-bordered table-striped table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="8%">Type</th>
                                        <th width="18%">Account / Acc Name</th>
                                        <th width="22%">Description</th>
                                        <th width="8%">Project</th>
                                        <th width="10%">Cost Center</th>
                                        <th width="12%">Amount</th>
                                        <th width="10%">Actions</th>
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

    <div id="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;"></div>

    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @include('partials.vj-soft-ui-styles')

    <style>
        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
        }

        .select2-container {
            z-index: 9999 !important;
        }

        #alert-container .vj-alert {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 0.5rem;
        }

        #alert-container .vj-alert-success {
            background: #e8f5e9;
            border-color: #c8e6c9;
            color: #198754;
        }

        .amount-debit {
            color: #198754;
            font-weight: 600;
        }

        .amount-credit {
            color: #dc3545;
            font-weight: 600;
        }

        .additional-info {
            display: block;
            color: #6c757d;
            font-style: italic;
            border-top: 1px dashed #dee2e6;
            padding-top: 3px;
            margin-top: 3px;
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            function formatNumber(number) {
                return new Intl.NumberFormat('id-ID').format(number);
            }

            let table = $("#vj_details").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('accounting.sap-sync.edit_vjdetail_data', ['vj_id' => $vj->id]) }}',
                    error: function(xhr, error, thrown) {
                        window.showAlert('Error loading data. Please refresh the page.', 'danger');
                        console.error("DataTable error:", error, thrown);
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'debit_credit_badge',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'akun'
                    },
                    {
                        data: 'description',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                if (data && data.includes('\n')) {
                                    let parts = data.split('\n');
                                    let mainDesc = parts[0];
                                    let additionalInfo = parts[1].replace(/\[|\]/g, '');

                                    return mainDesc + '<small class="additional-info">' +
                                        additionalInfo + '</small>';
                                }
                                return data;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'cost_center',
                        render: function(data) {
                            return data;
                        }
                    },
                    {
                        data: 'amount',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                let formattedAmount = formatNumber(Math.abs(parseFloat(data) || 0));
                                let cssClass = row.debit_credit === 'debit' ? 'amount-debit' :
                                    'amount-credit';
                                let prefix = row.debit_credit === 'debit' ? '' : '(';
                                let suffix = row.debit_credit === 'debit' ? '' : ')';
                                return `<span class="${cssClass}">${prefix}${formattedAmount}${suffix}</span>`;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                responsive: true,
                autoWidth: false,
                columnDefs: [{
                        "targets": [0, 1, 4, 5, 6, 7],
                        "className": "text-center"
                    },
                    {
                        "targets": [3],
                        "className": "text-wrap"
                    }
                ],
                language: {
                    processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
                }
            });

            window.showAlert = function(message, type) {
                const alertClass = type === 'success'
                    ? 'vj-alert-success'
                    : (type === 'danger' ? 'vj-alert-danger' : 'vj-alert-warning');

                const alertDiv = $(`<div class="vj-alert ${alertClass} alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    ${message}
                </div>`);

                $("#alert-container").append(alertDiv);

                setTimeout(function() {
                    alertDiv.alert('close');
                }, 5000);
            }

            $(document).ajaxError(function(event, jqXHR, settings, thrownError) {
                if (settings.error === undefined && jqXHR.status !== 0) {
                    console.error("Global AJAX error:", thrownError);
                    console.error("Response text:", jqXHR.responseText);

                    let errorMessage = 'A server error occurred';

                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                    }

                    if (!settings.url.includes(
                            '{{ route('accounting.sap-sync.edit_vjdetail_data', ['vj_id' => $vj->id]) }}'
                        )) {
                        window.showAlert(errorMessage, 'danger');
                    }
                }
            });
        });
    </script>
@endsection
