@extends('templates.main')

@section('title_page')
    API Keys
@endsection

@section('breadcrumb_title')
    api-keys
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">API Key Management</h3>
                    <button class="btn btn-sm btn-primary float-right" id="btn-generate-key">
                        <i class="fas fa-plus"></i> Generate New API Key
                    </button>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        API keys provide external applications access to create payment requests. Keep keys secure and never
                        share them publicly.
                    </div>

                    <table id="api-keys-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Application</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Last Used</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate API Key Modal -->
    <div class="modal fade" id="generateKeyModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">
                        <i class="fas fa-key"></i> Generate New API Key
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="generate-key-form">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                placeholder="e.g., Mobile App Production">
                            <small class="form-text text-muted">A descriptive name for this API key</small>
                        </div>
                        <div class="form-group">
                            <label for="application">Application <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="application" name="application" required
                                placeholder="e.g., iOS App v2.0">
                            <small class="form-text text-muted">The application that will use this key</small>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                placeholder="Optional description or notes"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key"></i> Generate Key
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Show API Key Modal -->
    <div class="modal fade" id="showKeyModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle"></i> API Key Generated Successfully
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Important:</strong> This is the only time you will see this key. Copy it now and store it
                        securely.
                    </div>

                    <div class="form-group">
                        <label for="raw-key-display">API Key</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="raw-key-display" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" id="copy-key-btn">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Key Name</label>
                        <p class="form-control-plaintext" id="key-name-display"></p>
                    </div>

                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h5 class="card-title">Usage Instructions</h5>
                        </div>
                        <div class="card-body">
                            <p>Include this key in the header of all API requests:</p>
                            <pre class="bg-light p-3"><code>X-API-Key: <span id="key-for-code"></span></code></pre>
                            <p class="mb-0"><small class="text-muted">See API documentation for complete examples.</small>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">
                        <i class="fas fa-check"></i> I've Copied the Key
                    </button>
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
            // Initialize DataTable
            const table = $('#api-keys-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.api-keys.data') }}',
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'application',
                        name: 'application'
                    },
                    {
                        data: 'description',
                        name: 'description',
                        orderable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'is_active'
                    },
                    {
                        data: 'creator_name',
                        name: 'creator.name'
                    },
                    {
                        data: 'last_used',
                        name: 'last_used_at'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [6, 'desc']
                ]
            });

            // Show generate key modal
            $('#btn-generate-key').click(function() {
                $('#generate-key-form')[0].reset();
                $('#generateKeyModal').modal('show');
            });

            // Handle generate key form submission
            $('#generate-key-form').submit(function(e) {
                e.preventDefault();

                const formData = {
                    name: $('#name').val(),
                    application: $('#application').val(),
                    description: $('#description').val(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: '{{ route('admin.api-keys.store') }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#generateKeyModal').modal('hide');

                        // Show the generated key
                        $('#raw-key-display').val(response.data.raw_key);
                        $('#key-name-display').text(response.data.name);
                        $('#key-for-code').text(response.data.raw_key);
                        $('#showKeyModal').modal('show');

                        // Refresh table
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        alert('Error generating API key: ' + (xhr.responseJSON?.message ||
                            'Unknown error'));
                    }
                });
            });

            // Copy key to clipboard
            $('#copy-key-btn').click(function() {
                const keyInput = document.getElementById('raw-key-display');
                keyInput.select();
                keyInput.setSelectionRange(0, 99999); // For mobile devices

                try {
                    document.execCommand('copy');
                    $(this).html('<i class="fas fa-check"></i> Copied!');
                    setTimeout(() => {
                        $('#copy-key-btn').html('<i class="fas fa-copy"></i> Copy');
                    }, 2000);
                } catch (err) {
                    alert('Failed to copy. Please copy manually.');
                }
            });

            // Activate API key
            $(document).on('click', '.activate-btn', function() {
                const id = $(this).data('id');

                if (confirm('Are you sure you want to activate this API key?')) {
                    $.ajax({
                        url: `/admin/api-keys/${id}/activate`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            table.ajax.reload();
                            alert(response.message);
                        },
                        error: function(xhr) {
                            alert('Error activating API key: ' + (xhr.responseJSON?.message ||
                                'Unknown error'));
                        }
                    });
                }
            });

            // Deactivate API key
            $(document).on('click', '.deactivate-btn', function() {
                const id = $(this).data('id');

                if (confirm(
                        'Are you sure you want to deactivate this API key? External applications will lose access immediately.'
                        )) {
                    $.ajax({
                        url: `/admin/api-keys/${id}/deactivate`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            table.ajax.reload();
                            alert(response.message);
                        },
                        error: function(xhr) {
                            alert('Error deactivating API key: ' + (xhr.responseJSON?.message ||
                                'Unknown error'));
                        }
                    });
                }
            });

            // Delete API key
            $(document).on('click', '.delete-btn', function() {
                const id = $(this).data('id');

                if (confirm(
                        'Are you sure you want to permanently delete this API key? This action cannot be undone.'
                        )) {
                    $.ajax({
                        url: `/admin/api-keys/${id}`,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            table.ajax.reload();
                            alert(response.message);
                        },
                        error: function(xhr) {
                            alert('Error deleting API key: ' + (xhr.responseJSON?.message ||
                                'Unknown error'));
                        }
                    });
                }
            });
        });
    </script>
@endsection
