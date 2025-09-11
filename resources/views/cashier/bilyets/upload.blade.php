@extends('templates.main')

@section('title_page')
    BILYET
@endsection

@section('breadcrumb_title')
    bilyet
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-bilyet-links page='upload' />

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload Bilyets</h3>
                    <a href="{{ asset('file_upload/') . '/bilyet_template.xlsx' }}" class="btn btn-xs btn-success float-right"
                        target=_blank>download template</a>
                    <a href="{{ route('cashier.bilyets.import') }}"
                        class="btn btn-xs btn-warning float-right mx-2 {{ $import_button }}" data-toggle="modal"
                        data-target="#modal-import"> Import</a>
                    <a href="{{ route('cashier.bilyet-temps.truncate') }}"
                        class="btn btn-xs btn-danger float-right {{ $empty_button }}"
                        onclick="return confirm('Are you sure you want to delete all data in table?')"> Empty Table</a>
                    <button href="#" class="btn btn-xs btn-primary float-right mr-2" data-toggle="modal"
                        data-target="#modal-upload"> Upload</button>
                </div> <!-- /.card-header -->

                <div class="card-body">
                    <table id="giros" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nomor</th>
                                <th>status</th>
                                <th>Giro Acc</th>
                                <th>Type</th>
                                <th>BilyetD</th>
                                <th>CairD</th>
                                <th>Amount</th>
                                <th>Loan</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div> <!-- /.col -->
    </div> <!-- /.row -->

    <div class="modal fade" id="modal-upload">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        <i class="fas fa-upload"></i> Upload Bilyets
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="upload-form" action="{{ route('cashier.bilyet-temps.upload') }}" enctype="multipart/form-data"
                    method="POST">
                    @csrf
                    <div class="modal-body">
                        <!-- File Upload Area -->
                        <div class="form-group">
                            <label for="file_upload">
                                <i class="fas fa-file-excel"></i> Select Excel File
                            </label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="file_upload" id="file_upload" required
                                        class="custom-file-input" accept=".xls,.xlsx">
                                    <label class="custom-file-label" for="file_upload">Choose file...</label>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Supported formats: .xls, .xlsx (Max size: 10MB)
                            </small>
                        </div>

                        <!-- File Info Display -->
                        <div id="file-info" class="alert alert-info" style="display: none;">
                            <h6><i class="fas fa-info-circle"></i> File Information</h6>
                            <div id="file-details"></div>
                        </div>

                        <!-- Progress Bar -->
                        <div id="upload-progress" class="progress" style="display: none;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                style="width: 0%"></div>
                        </div>

                        <!-- Status Messages -->
                        <div id="upload-status" class="alert" style="display: none;"></div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary" id="upload-btn">
                            <i class="fas fa-upload"></i> Upload File
                        </button>
                    </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

    <div class="modal fade" id="modal-import">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"> Import Bilyets to DB</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('cashier.bilyets.import') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <label>Receive Date</label>
                        <div class="form-group">
                            <input type="date" name='receive_date' required class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary"> Import</button>
                    </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->
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
        $(function() {
            $("#giros").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('cashier.bilyet-temps.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor'
                    },
                    {
                        data: 'status_duplikasi'
                    },
                    // {data: 'giro_id'},
                    {
                        data: 'acc_no'
                    },
                    {
                        data: 'type'
                    },
                    {
                        data: 'bilyet_date'
                    },
                    {
                        data: 'cair_date'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'loan'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [7],
                    "className": "text-right"
                }]
            })

            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })

            // Enhanced file upload functionality
            $('#file_upload').on('change', function() {
                const file = this.files[0];
                if (file) {
                    // Show file info
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    const fileDetails = `
                        <strong>File:</strong> ${file.name}<br>
                        <strong>Size:</strong> ${fileSize} MB<br>
                        <strong>Type:</strong> ${file.type || 'Excel file'}
                    `;
                    $('#file-details').html(fileDetails);
                    $('#file-info').show();

                    // Validate file
                    if (file.size > 10 * 1024 * 1024) { // 10MB limit
                        $('#upload-status').removeClass('alert-success alert-warning')
                            .addClass('alert-danger')
                            .html(
                                '<i class="fas fa-exclamation-triangle"></i> File size exceeds 10MB limit')
                            .show();
                        $('#upload-btn').prop('disabled', true);
                    } else if (!file.name.match(/\.(xls|xlsx)$/i)) {
                        $('#upload-status').removeClass('alert-success alert-danger')
                            .addClass('alert-warning')
                            .html(
                                '<i class="fas fa-exclamation-triangle"></i> Please select an Excel file (.xls or .xlsx)'
                                )
                            .show();
                        $('#upload-btn').prop('disabled', true);
                    } else {
                        $('#upload-status').removeClass('alert-warning alert-danger')
                            .addClass('alert-success')
                            .html('<i class="fas fa-check-circle"></i> File ready for upload')
                            .show();
                        $('#upload-btn').prop('disabled', false);
                    }
                } else {
                    $('#file-info').hide();
                    $('#upload-status').hide();
                    $('#upload-btn').prop('disabled', false);
                }
            });

            // Enhanced form submission with progress
            $('#upload-form').on('submit', function(e) {
                const fileInput = $('#file_upload')[0];
                if (!fileInput.files[0]) {
                    e.preventDefault();
                    alert('Please select a file to upload');
                    return false;
                }

                // Show progress bar
                $('#upload-progress').show();
                $('#upload-btn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Uploading...');

                // Simulate progress (since we can't track real progress easily)
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90;
                    $('.progress-bar').css('width', progress + '%');
                }, 200);

                // Clear interval when form submits
                setTimeout(() => {
                    clearInterval(progressInterval);
                    $('.progress-bar').css('width', '100%');
                }, 3000);
            });

            // Display error details if available
            @if (session('error_details'))
                const errorDetails = @json(session('error_details'));
                if (errorDetails && errorDetails.errors && errorDetails.errors.length > 0) {
                    let errorHtml =
                        '<div class="alert alert-danger"><h6><i class="fas fa-exclamation-triangle"></i> Import Errors</h6><ul>';
                    errorDetails.errors.forEach(error => {
                        errorHtml += `<li>${error}</li>`;
                    });
                    errorHtml += '</ul>';

                    if (errorDetails.recommendations && errorDetails.recommendations.length > 0) {
                        errorHtml += '<h6><i class="fas fa-lightbulb"></i> Recommendations:</h6><ul>';
                        errorDetails.recommendations.forEach(rec => {
                            errorHtml += `<li>${rec}</li>`;
                        });
                        errorHtml += '</ul>';
                    }
                    errorHtml += '</div>';

                    // Insert error details after the card
                    $('.card').after(errorHtml);
                }
            @endif
        });
    </script>
@endsection
