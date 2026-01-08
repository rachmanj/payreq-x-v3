@extends('templates.main')

@section('title_page')
    Business Partners Management
@endsection

@section('breadcrumb_title')
    business-partners
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Business Partners Management</h3>
                    @can('akses_admin')
                        <button class="btn btn-sm btn-primary float-right" id="sync-from-sap">
                            <i class="fas fa-sync"></i> Sync from SAP
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Business Partners are synchronized from SAP B1. Use the "Sync from SAP" button to update the list. This includes Customers, Suppliers, and Leads.
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select id="filter-type" class="form-control form-control-sm">
                                <option value="">All Types</option>
                                <option value="cCustomer">Customers</option>
                                <option value="cSupplier">Suppliers</option>
                                <option value="cLead">Leads</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filter-active" class="form-control form-control-sm">
                                <option value="">All Status</option>
                                <option value="1">Active Only</option>
                                <option value="0">Inactive Only</option>
                            </select>
                        </div>
                    </div>

                    <table id="business-partners-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Active</th>
                                <th>VAT Liable</th>
                                <th>NPWP</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Credit Limit</th>
                                <th>Balance</th>
                                <th>Last Synced</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            const table = $('#business-partners-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.business-partners.index') }}',
                    data: function(d) {
                        d.type = $('#filter-type').val();
                        d.active = $('#filter-active').val();
                    }
                },
                columns: [{
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'type_badge',
                        name: 'type',
                        orderable: false
                    },
                    {
                        data: 'active',
                        name: 'active'
                    },
                    {
                        data: 'vat_liable',
                        name: 'vat_liable'
                    },
                    {
                        data: 'federal_tax_id',
                        name: 'federal_tax_id'
                    },
                    {
                        data: 'phone',
                        name: 'phone'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'credit_limit',
                        name: 'credit_limit'
                    },
                    {
                        data: 'balance',
                        name: 'balance'
                    },
                    {
                        data: 'last_synced_at',
                        name: 'last_synced_at'
                    }
                ],
                order: [
                    [0, 'asc']
                ],
                pageLength: 25
            });

            $('#filter-type, #filter-active').on('change', function() {
                table.ajax.reload();
            });

            @can('akses_admin')
                $('#sync-from-sap').on('click', function() {
                    const btn = $(this);
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');

                    $.ajax({
                        url: '{{ route('admin.business-partners.sync') }}',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                table.ajax.reload();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            toastr.error('Sync failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
                        },
                        complete: function() {
                            btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sync from SAP');
                        }
                    });
                });
            @endcan
        });
    </script>
@endsection
