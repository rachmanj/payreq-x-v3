@extends('templates.main')

@section('title_page')
    SAP Master Data Sync
@endsection

@section('breadcrumb_title')
    sap-master-data-sync
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">SAP Master Data Synchronization</h3>
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
                        Synchronize master data from SAP B1 Service Layer. You can sync individual data types or sync all at once.
                        <br><small>Note: Large datasets may take several minutes to complete.</small>
                    </div>

                    <!-- Sync All Button -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <button class="btn btn-lg btn-primary btn-block" id="sync-all">
                                <i class="fas fa-sync"></i> Sync All Master Data
                            </button>
                        </div>
                    </div>

                    <!-- Individual Sync Cards -->
                    <div class="row">
                        <!-- Projects -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-primary card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-project-diagram"></i> Projects
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">SAP Projects master data</p>
                                    <div id="projects-status" class="mb-2"></div>
                                    <button class="btn btn-sm btn-primary btn-block" data-type="projects">
                                        <i class="fas fa-sync"></i> Sync Projects
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Cost Centers -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-success card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-building"></i> Cost Centers
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">SAP Profit Centers master data</p>
                                    <div id="cost-centers-status" class="mb-2"></div>
                                    <button class="btn btn-sm btn-success btn-block" data-type="cost-centers">
                                        <i class="fas fa-sync"></i> Sync Cost Centers
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- GL Accounts -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-info card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-book"></i> GL Accounts
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">SAP General Ledger Accounts</p>
                                    <div id="accounts-status" class="mb-2"></div>
                                    <button class="btn btn-sm btn-info btn-block" data-type="accounts">
                                        <i class="fas fa-sync"></i> Sync Accounts
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Business Partners -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-warning card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-users"></i> Business Partners
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Customers, Suppliers, Leads</p>
                                    <div id="business-partners-status" class="mb-2"></div>
                                    <button class="btn btn-sm btn-warning btn-block" data-type="business-partners">
                                        <i class="fas fa-sync"></i> Sync Business Partners
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sync Progress -->
                    <div class="row mt-4" id="sync-progress" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Sync Progress</h3>
                                </div>
                                <div class="card-body">
                                    <div id="progress-details"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            const syncButtons = $('[data-type]');
            const syncAllBtn = $('#sync-all');
            const progressDiv = $('#sync-progress');
            const progressDetails = $('#progress-details');

            function updateStatus(type, message, isSuccess = true) {
                const statusDiv = $(`#${type}-status`);
                const className = isSuccess ? 'text-success' : 'text-danger';
                statusDiv.html(`<small class="${className}"><i class="fas fa-${isSuccess ? 'check' : 'exclamation-triangle'}"></i> ${message}</small>`);
            }

            function resetStatus(type) {
                const statusDiv = $(`#${type}-status`);
                statusDiv.html(`<small class="text-muted"><i class="fas fa-spinner fa-spin"></i> Syncing...</small>`);
            }

            function syncSingle(type) {
                const btn = $(`[data-type="${type}"]`);
                const originalHtml = btn.html();
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
                resetStatus(type.replace('-', '-'));

                $.ajax({
                    url: `/admin/sap-master-data-sync/sync`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        type: type
                    },
                    success: function(response) {
                        if (response.success) {
                            updateStatus(type.replace('-', '-'), `Synced: ${response.synced} record(s)`, true);
                            toastr.success(response.message || `${type} synced successfully`);
                        } else {
                            updateStatus(type.replace('-', '-'), 'Sync failed', false);
                            toastr.error(response.message || 'Sync failed');
                        }
                    },
                    error: function(xhr) {
                        updateStatus(type.replace('-', '-'), 'Sync failed', false);
                        const errorMsg = xhr.responseJSON?.message || 'Unknown error occurred';
                        toastr.error(`Sync failed: ${errorMsg}`);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            }

            syncButtons.on('click', function() {
                const type = $(this).data('type');
                syncSingle(type);
            });

            syncAllBtn.on('click', function() {
                const btn = $(this);
                const originalHtml = btn.html();
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing All...');
                progressDiv.show();
                progressDetails.html('<p class="text-muted">Starting synchronization...</p>');

                // Reset all statuses
                syncButtons.each(function() {
                    const type = $(this).data('type');
                    resetStatus(type.replace('-', '-'));
                });

                $.ajax({
                    url: `/admin/sap-master-data-sync/sync-all`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            let progressHtml = '<div class="alert alert-success"><h5><i class="fas fa-check-circle"></i> All Sync Completed</h5><ul class="mb-0">';
                            
                            if (response.results) {
                                Object.keys(response.results).forEach(function(key) {
                                    const result = response.results[key];
                                    const label = key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                                    progressHtml += `<li>${label}: ${result.synced} record(s) synced`;
                                    if (result.errors && result.errors.length > 0) {
                                        progressHtml += ` <span class="text-danger">(${result.errors.length} error(s))</span>`;
                                    }
                                    progressHtml += `</li>`;
                                    
                                    // Update individual status
                                    const typeKey = key.replace('_', '-');
                                    updateStatus(typeKey, `${result.synced} synced`, result.errors.length === 0);
                                });
                            }
                            
                            progressHtml += '</ul></div>';
                            progressDetails.html(progressHtml);
                            toastr.success('All master data synced successfully');
                        } else {
                            progressDetails.html(`<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${response.message || 'Sync failed'}</div>`);
                            toastr.error(response.message || 'Sync failed');
                        }
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'Unknown error occurred';
                        progressDetails.html(`<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${errorMsg}</div>`);
                        toastr.error(`Sync failed: ${errorMsg}`);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });
    </script>
@endsection
