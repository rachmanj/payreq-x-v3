@extends('templates.main')

@section('title_page')
    SAP Sync
@endsection

@section('breadcrumb_title')
    accounting / sap-sync / show
@endsection

@section('content')
    {{-- Enhanced Header with Status Badge --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline {{ $vj->sap_journal_no ? 'card-success' : 'card-warning' }}">
                <div class="card-header bg-gradient-{{ $vj->sap_journal_no ? 'success' : 'warning' }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                @if ($vj->sap_journal_no)
                                    <span class="badge badge-light badge-lg">
                                        <i class="fas fa-check-circle"></i> POSTED
                                    </span>
                                @else
                                    <span class="badge badge-light badge-lg">
                                        <i class="fas fa-clock"></i> PENDING
                                    </span>
                                @endif
                            </div>
                            <div>
                                <h3 class="card-title mb-0 text-white">Verification Journal</h3>
                                <small class="text-white-50">{{ $vj->nomor }}</small>
                            </div>
                        </div>
                        <a href="{{ route('accounting.sap-sync.index', ['page' => $vj->project]) }}"
                            class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Information Cards Section --}}
    <div class="row mb-3">
        {{-- Journal Details Card --}}
        <div class="col-md-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice"></i> Journal Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5"><i class="fas fa-hashtag text-muted"></i> Journal No</dt>
                        <dd class="col-sm-7"><strong>{{ $vj->nomor }}</strong></dd>

                        <dt class="col-sm-5"><i class="fas fa-calendar text-muted"></i> Date</dt>
                        <dd class="col-sm-7">{{ date('d-M-Y', strtotime($vj->date)) }}</dd>

                        <dt class="col-sm-5"><i class="fas fa-project-diagram text-muted"></i> Project</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-info">{{ $vj->project }}</span>
                        </dd>

                        <dt class="col-sm-5"><i class="fas fa-file-alt text-muted"></i> Type</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-secondary">{{ strtoupper($vj->type ?? 'REGULAR') }}</span>
                        </dd>

                        <dt class="col-sm-5"><i class="fas fa-user text-muted"></i> Created by</dt>
                        <dd class="col-sm-7">
                            {{ $vj->createdBy->name }}<br>
                            <small class="text-muted">
                                {{ date('d-M-Y H:i', strtotime($vj->created_at . '+8 hours')) }} wita
                            </small>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- SAP Integration Card --}}
        <div class="col-md-6">
            <div class="card card-outline {{ $vj->sap_journal_no ? 'card-success' : 'card-warning' }}">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sync-alt"></i> SAP Integration
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5"><i class="fas fa-book text-muted"></i> SAP Journal No</dt>
                        <dd class="col-sm-7">
                            @if ($vj->sap_journal_no)
                                <strong class="text-success">{{ $vj->sap_journal_no }}</strong>
                                @if ($vj->sap_filename)
                                    @php
                                        $fileExtension = strtolower(pathinfo($vj->sap_filename, PATHINFO_EXTENSION));
                                        $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
                                        $iconClass = $isImage ? 'fa-file-image' : 'fa-file-pdf';
                                    @endphp
                                    <a href="{{ asset('file_upload/') . '/' . $vj->sap_filename }}"
                                        class="btn btn-xs btn-success ml-2" target="_blank">
                                        <i class="fas {{ $iconClass }}"></i> View
                                    </a>
                                @endif
                                <button type="button" class="btn btn-xs btn-info ml-2" data-toggle="modal"
                                    data-target="#upload-journal">
                                    <i class="fas fa-upload"></i> Upload
                                </button>
                            @else
                                <span class="text-muted">Not submitted</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5"><i class="fas fa-user-check text-muted"></i> Posted By</dt>
                        <dd class="col-sm-7">
                            @if ($vj->posted_by)
                                {{ $vj->postedBy->name }}<br>
                                <small class="text-muted">
                                    {{ date('d-M-Y H:i', strtotime($vj->updated_at . '+8 hours')) }} wita
                                </small>
                            @else
                                <span class="text-muted">Not posted yet</span>
                            @endif
                        </dd>

                        @if ($vj->sap_submission_attempts > 0)
                            <dt class="col-sm-5"><i class="fas fa-history text-muted"></i> Submission Attempts</dt>
                            <dd class="col-sm-7">
                                <span
                                    class="badge badge-{{ $vj->sap_submission_status === 'success' ? 'success' : 'danger' }}">
                                    {{ $vj->sap_submission_attempts }} attempt(s)
                                </span>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Financial Summary Card --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-money-bill-wave"></i> Financial Summary</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Amount</span>
                                    <span class="info-box-number">
                                        Rp. {{ number_format($vj->amount, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-arrow-down"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Debit</span>
                                    <span class="info-box-number">
                                        Rp. {{ number_format($vj_details->where('debit_credit', 'debit')->sum('amount'), 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger">
                                    <i class="fas fa-arrow-up"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Credit</span>
                                    <span class="info-box-number">
                                        Rp. {{ number_format($vj_details->where('debit_credit', 'credit')->sum('amount'), 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if ($vj->description)
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div class="description-block">
                                    <span class="description-text">
                                        <i class="fas fa-align-left text-muted"></i> {{ $vj->description }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons Section --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tools"></i> Actions</h3>
                </div>
                <div class="card-body">
                    {{-- Primary Actions --}}
                    @if (empty($vj->sap_journal_no))
                        <div class="mb-3">
                            <button type="button" class="btn btn-success btn-lg btn-block" id="submit-to-sap-btn">
                                <i class="fas fa-paper-plane"></i> Submit to SAP B1
                            </button>
                        </div>
                    @endif

                    {{-- Secondary Actions --}}
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <a href="{{ route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $vj->id]) }}"
                                class="btn btn-warning btn-block {{ empty($vj->sap_journal_no) ? '' : 'disabled' }}">
                                <i class="fas fa-edit"></i> Edit VJ Details
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <a href="{{ route('accounting.sap-sync.export', ['vj_id' => $vj->id]) }}"
                                class="btn btn-info btn-block">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <a href="{{ route('verifications.journal.print', $vj->id) }}"
                                class="btn btn-secondary btn-block" target="_blank">
                                <i class="fas fa-print"></i> Print Journal
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <button class="btn btn-warning btn-block {{ $vj->sap_journal_no ? 'disabled' : '' }}"
                                data-toggle="modal" data-target="#update-sap">
                                <i class="fas fa-sync"></i> Update SAP Info
                            </button>
                        </div>
                    </div>

                    {{-- Danger Actions --}}
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <form action="{{ route('accounting.sap-sync.cancel_sap_info') }}" method="POST"
                                class="d-inline cancel-sap-info-form">
                                @csrf
                                <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
                                <button type="submit"
                                    class="btn btn-danger btn-block {{ $vj->sap_journal_no ? 'disabled' : '' }}"
                                    title="{{ $vj->sap_journal_no ? 'Cannot cancel: Journal already submitted to SAP B1. Reversal must be done in SAP B1 first.' : '' }}">
                                    <i class="fas fa-times-circle"></i> Cancel SAP Info
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced Table Section --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Journal Entries
                        <span class="badge badge-primary ml-2">{{ $vj_details->count() }} lines</span>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%" class="text-center">#</th>
                                    <th width="20%">Account</th>
                                    <th width="25%">Description</th>
                                    <th width="10%" class="text-center">Project</th>
                                    <th width="10%" class="text-center">Cost Center</th>
                                    <th width="15%" class="text-right">Debit (IDR)</th>
                                    <th width="15%" class="text-right">Credit (IDR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vj_details as $key => $item)
                                    <tr>
                                        <td class="text-center">{{ $key + 1 }}</td>
                                        <td>
                                            <strong>{{ $item['account_code'] }}</strong><br>
                                            @if ($item['account_name'] === 'not found')
                                                <small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> {{ $item['account_name'] }}
                                                </small>
                                            @else
                                                <small class="text-muted">{{ $item['account_name'] }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $item['description'] }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $item['project'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-secondary">{{ $item['cost_center'] }}</span>
                                        </td>
                                        @if ($item['debit_credit'] === 'debit')
                                            <td class="text-right text-success font-weight-bold">
                                                {{ number_format($item['amount'], 2) }}
                                            </td>
                                            <td class="text-right text-muted">0.00</td>
                                        @else
                                            <td class="text-right text-muted">0.00</td>
                                            <td class="text-right text-danger font-weight-bold">
                                                {{ number_format($item['amount'], 2) }}
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                                <tr class="table-info font-weight-bold">
                                    <td colspan="5" class="text-right">TOTAL</td>
                                    <td class="text-right text-success">
                                        {{ number_format($vj_details->where('debit_credit', 'debit')->sum('amount'), 2) }}
                                    </td>
                                    <td class="text-right text-danger">
                                        {{ number_format($vj_details->where('debit_credit', 'credit')->sum('amount'), 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Submission History Section --}}
    @if (isset($submissionLogs) && $submissionLogs->count() > 0)
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i> Submission History
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach ($submissionLogs as $log)
                                <div class="time-label">
                                    <span class="bg-{{ $log->status === 'success' ? 'success' : 'danger' }}">
                                        {{ date('d M Y', strtotime($log->created_at)) }}
                                    </span>
                                </div>
                                <div>
                                    <i
                                        class="fas fa-{{ $log->status === 'success' ? 'check-circle bg-success' : 'times-circle bg-danger' }}"></i>
                                    <div class="timeline-item">
                                        <span class="time">
                                            <i class="fas fa-clock"></i> {{ date('H:i', strtotime($log->created_at)) }}
                                        </span>
                                        <h3 class="timeline-header">
                                            Attempt #{{ $log->attempt_number }} -
                                            <span
                                                class="badge badge-{{ $log->status === 'success' ? 'success' : 'danger' }}">
                                                {{ strtoupper($log->status) }}
                                            </span>
                                            @if ($log->user)
                                                <small class="text-muted">by {{ $log->user->name }}</small>
                                            @endif
                                        </h3>
                                        @if ($log->status === 'success')
                                            <div class="timeline-body">
                                                <p><strong>SAP Journal Number:</strong>
                                                    <span class="badge badge-success">{{ $log->sap_journal_number }}</span>
                                                </p>
                                            </div>
                                        @else
                                            <div class="timeline-body">
                                                <p><strong>Error:</strong></p>
                                                <div class="alert alert-danger mb-0">
                                                    <code>{{ $log->error_message }}</code>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL UPDATE - SAP --}}
    <div class="modal fade" id="update-sap">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update SAP Info</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('accounting.sap-sync.update_sap_info') }}" method="POST">
                    @csrf
                    <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="sap_posting_date">SAP Posting Date</label>
                            <input type="date" name="sap_posting_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label for="sap_journal_no">SAP Journal No</label>
                            <input type="text" name="sap_journal_no" class="form-control">
                        </div>
                    </div>

                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- UPLOAD DOCUMENT --}}
    <div class="modal fade" id="upload-journal">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload"></i> Upload SAP Journal Document
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('accounting.sap-sync.upload_sap_journal') }}" method="POST"
                        enctype="multipart/form-data" id="upload-document-form">
                        @csrf
                        <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
                        <div class="form-group">
                            <label for="sap_journal_file">Document File</label>
                            <input type="file" name="sap_journal_file" class="form-control" id="sap_journal_file"
                                accept=".pdf,.jpg,.jpeg,.png,.gif,.bmp,.webp" required>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Accepted formats: PDF, JPG, JPEG, PNG, GIF, BMP, WEBP
                                (Max: 10MB)
                            </small>
                        </div>
                        @if ($vj->sap_filename)
                            <div class="alert alert-info">
                                <i class="fas fa-exclamation-circle"></i> Uploading a new file will replace the existing
                                document.
                            </div>
                        @endif
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-upload"></i> Upload Document
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <form id="submit-sap-form" action="{{ route('accounting.sap-sync.submit_to_sap') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
    </form>

    @push('styles')
        <style>
            /* Status Badge Large */
            .badge-lg {
                font-size: 1rem;
                padding: 0.5rem 1rem;
            }

            /* Card Hover Effects */
            .card-outline {
                transition: all 0.3s ease;
            }

            .card-outline:hover {
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                transform: translateY(-2px);
            }

            /* Table Enhancements */
            .table-hover tbody tr:hover {
                background-color: rgba(0, 123, 255, 0.05);
            }

            /* Info Box Styling */
            .info-box {
                display: block;
                min-height: 90px;
                background: #fff;
                width: 100%;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
                border-radius: 2px;
                margin-bottom: 15px;
            }

            .info-box-icon {
                border-top-left-radius: 2px;
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
                border-bottom-left-radius: 2px;
                display: block;
                float: left;
                height: 90px;
                width: 90px;
                text-align: center;
                font-size: 45px;
                line-height: 90px;
                background: rgba(0, 0, 0, 0.2);
            }

            .info-box-content {
                padding: 5px 10px;
                margin-left: 90px;
            }

            .info-box-text {
                text-transform: uppercase;
                font-weight: 600;
                font-size: 13px;
            }

            .info-box-number {
                display: block;
                font-weight: bold;
                font-size: 18px;
            }

            /* Description Block */
            .description-block {
                padding: 10px;
                background: #f8f9fa;
                border-radius: 4px;
            }

            .description-text {
                color: #495057;
                font-size: 14px;
            }

            /* Timeline Styling */
            .timeline {
                position: relative;
                padding: 20px 0;
            }

            .timeline-item {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 3px;
                padding: 12px;
                margin: 0 0 20px 60px;
                position: relative;
            }

            .timeline-item::before {
                content: '';
                position: absolute;
                left: -15px;
                top: 26px;
                display: block;
                width: 0;
                height: 0;
                border: solid transparent;
                border-width: 8px;
                border-right-color: #ddd;
            }

            .time-label {
                position: relative;
                padding: 10px 0;
            }

            .time-label span {
                padding: 5px 10px;
                border-radius: 4px;
                color: #fff;
                font-weight: 600;
            }

            .timeline-item i {
                position: absolute;
                left: -45px;
                top: 20px;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 14px;
            }

            .timeline-header {
                margin-bottom: 10px;
                font-size: 16px;
            }

            .timeline-body {
                padding-top: 10px;
            }

            .time {
                float: right;
                padding: 5px 10px;
                background: #f4f4f4;
                border-radius: 4px;
                font-size: 12px;
            }

            /* Responsive Improvements */
            @media (max-width: 768px) {
                .btn-block {
                    font-size: 14px;
                    padding: 8px 12px;
                }

                .info-box {
                    margin-bottom: 10px;
                }

                .card-body {
                    padding: 15px;
                }

                .timeline-item {
                    margin-left: 40px;
                }
            }
        </style>
    @endpush

    @php
        $submissionMeta = [
            'journal' => [
                'number' => $vj->nomor,
                'date' => date('d-M-Y', strtotime($vj->date)),
                'project' => $vj->project,
                'type' => strtoupper($vj->type ?? 'REGULAR'),
                'amount' => 'Rp. ' . number_format($vj->amount, 2),
                'lines' => $vj_details->count() . ' lines',
                'status' => $vj->sap_journal_no ? 'Posted' : 'Not Posted',
                'status_badge' => $vj->sap_journal_no ? 'success' : 'warning',
            ],
            'attempts' => [
                'count' => (int) $vj->sap_submission_attempts,
                'lastAttemptAt' => $vj->sap_submitted_at
                    ? date('d-M-Y H:i', strtotime($vj->sap_submitted_at . '+8 hours')) . ' wita'
                    : null,
                'lastError' => $vj->sap_submission_error,
            ],
        ];
    @endphp

    @push('scripts')
        <script>
            const submissionMeta = @json($submissionMeta);

            function buildSubmissionSummaryHtml(meta) {
                const journal = meta.journal;
                const attempts = meta.attempts;
                const attemptsHtml = attempts.count > 0 ? `
                    <div class="mt-3 alert alert-danger">
                        <h6 class="font-weight-bold"><i class="fas fa-history"></i> Previous Submission Attempts</h6>
                        <p class="mb-1"><strong>Attempts:</strong> ${attempts.count}</p>
                        ${attempts.lastAttemptAt ? `<p class="mb-1"><strong>Last Attempt:</strong> ${attempts.lastAttemptAt}</p>` : ''}
                        ${attempts.lastError ? `<div class="alert alert-danger mb-0"><code>${attempts.lastError}</code></div>` : ''}
                    </div>
                ` : '';

                return `
                    <div class="text-left">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="font-weight-bold"><i class="fas fa-info-circle text-primary"></i> Journal Information</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><td><strong>Journal No:</strong></td><td>${journal.number}</td></tr>
                                    <tr><td><strong>Date:</strong></td><td>${journal.date}</td></tr>
                                    <tr><td><strong>Project:</strong></td><td><span class="badge badge-info">${journal.project}</span></td></tr>
                                    <tr><td><strong>Type:</strong></td><td><span class="badge badge-secondary">${journal.type}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="font-weight-bold"><i class="fas fa-chart-line text-success"></i> Financial Summary</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><td><strong>Total Amount:</strong></td><td><strong>${journal.amount}</strong></td></tr>
                                    <tr><td><strong>Total Lines:</strong></td><td><strong>${journal.lines}</strong></td></tr>
                                    <tr><td><strong>Status:</strong></td><td><span class="badge badge-${journal.status_badge}">${journal.status}</span></td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="mt-3 alert alert-warning">
                            <h6 class="font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Important Notes</h6>
                            <ul class="mb-0 pl-3">
                                <li>The journal will be saved as a <strong>draft</strong> in SAP B1.</li>
                                <li>Please ensure account codes, projects, and cost centers exist in SAP B1.</li>
                                <li>If SAP rejects the submission, you can retry after fixing the issue.</li>
                                <li>Reversals must be performed directly in SAP B1.</li>
                            </ul>
                        </div>
                        ${attemptsHtml}
                    </div>
                `;
            }

            $(document).ready(function() {
                const $submitBtn = $('#submit-to-sap-btn');

                if ($submitBtn.length) {
                    $submitBtn.on('click', function() {
                        Swal.fire({
                            title: 'Submit this journal to SAP B1?',
                            html: buildSubmissionSummaryHtml(submissionMeta),
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, submit to SAP B1',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true,
                            width: '60rem',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.fire({
                                    title: 'Submitting...',
                                    html: 'Please wait while we save this journal as a draft in SAP B1.',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                        $('#submit-sap-form').trigger('submit');
                                    }
                                });
                            }
                        });
                    });

                    $('#submit-sap-form').on('submit', function() {
                        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
                    });
                }

                $('.cancel-sap-info-form').on('submit', function(e) {
                    const isDisabled = $(this).find('button').hasClass('disabled');
                    if (isDisabled) {
                        return;
                    }

                    e.preventDefault();
                    const form = this;
                    Swal.fire({
                        title: 'Cancel SAP Info?',
                        html: '<p>This will clear the SAP submission info for this journal. This action cannot be undone.</p>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, cancel it',
                        cancelButtonText: 'Keep SAP Info',
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });

                @if (session('success'))
                    toastr.success('{{ session('success') }}');
                @endif

                @if (session('error'))
                    toastr.error('{{ session('error') }}');
                @endif
            });
        </script>
    @endpush
@endsection
