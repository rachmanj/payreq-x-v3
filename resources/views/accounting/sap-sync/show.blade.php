@extends('templates.main')

@section('title_page')
    SAP Sync
@endsection

@section('breadcrumb_title')
    accounting / sap-sync / show
@endsection

@section('content')
    <div class="vj-show">
    {{-- Enhanced Header with Status Badge --}}
    <div class="row mb-3">
        <div class="col-12">
            @php
                $headerStatus = $vj->sap_journal_no
                    ? 'posted'
                    : ($vj->sap_reversed_at ? 'reversed' : 'pending');
                $headerCardClass = [
                    'posted' => 'card-success',
                    'reversed' => 'card-secondary',
                    'pending' => 'card-warning',
                ][$headerStatus];
                $headerGradient = [
                    'posted' => 'success',
                    'reversed' => 'secondary',
                    'pending' => 'warning',
                ][$headerStatus];
            @endphp
            <div class="card card-outline {{ $headerCardClass }}">
                <div class="card-header bg-gradient-{{ $headerGradient }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                @if ($headerStatus === 'posted')
                                    <span class="vj-chip vj-chip-on-dark">
                                        <i class="fas fa-check-circle"></i> POSTED
                                    </span>
                                @elseif ($headerStatus === 'reversed')
                                    <span class="vj-chip vj-chip-on-dark">
                                        <i class="fas fa-undo"></i> REVERSED
                                    </span>
                                @else
                                    <span class="vj-chip vj-chip-on-dark">
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
                            class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Information Cards Section --}}
    <div class="row mb-3">
        {{-- Journal Details Card --}}
        <div class="col-md-4">
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
                            <span class="vj-chip vj-chip-info">{{ $vj->project }}</span>
                        </dd>

                        <dt class="col-sm-5"><i class="fas fa-file-alt text-muted"></i> Type</dt>
                        <dd class="col-sm-7">
                            <span class="vj-chip vj-chip-neutral">{{ strtoupper($vj->type ?? 'REGULAR') }}</span>
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
        <div class="col-md-4">
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
                                    <span class="vj-inline-actions ml-2">
                                        <a href="{{ asset('file_upload/') . '/' . $vj->sap_filename }}"
                                            class="vj-action-item vj-action-item-xs vj-action-export" target="_blank">
                                            <i class="fas {{ $iconClass }}"></i>
                                            <span>View</span>
                                        </a>
                                    </span>
                                @endif
                                <span class="vj-inline-actions ml-2">
                                    <button type="button"
                                        class="vj-action-item vj-action-item-btn vj-action-item-xs vj-action-sap"
                                        data-toggle="modal" data-target="#upload-journal">
                                        <i class="fas fa-upload"></i>
                                        <span>Upload</span>
                                    </button>
                                </span>
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
                                    class="vj-chip {{ $vj->sap_submission_status === 'success' ? 'vj-chip-success' : 'vj-chip-danger' }}">
                                    {{ $vj->sap_submission_attempts }} attempt(s)
                                </span>
                            </dd>
                        @endif
                    </dl>

                    @if ($vj->sap_reversed_at)
                        <div class="vj-alert vj-alert-secondary mt-3 mb-0">
                            <h6 class="font-weight-bold mb-2">
                                <i class="fas fa-undo"></i> Reversal History
                            </h6>
                            <p class="mb-1">
                                <strong>Reversed by:</strong> {{ $vj->reversedBy->name }}
                                on {{ date('d-M-Y H:i', strtotime($vj->sap_reversed_at . '+8 hours')) }} wita
                            </p>
                            @if ($vj->sap_reversal_journal_no)
                                <p class="mb-1">
                                    <strong>SAP Reversal Journal No:</strong>
                                    <span class="vj-chip vj-chip-neutral">{{ $vj->sap_reversal_journal_no }}</span>
                                </p>
                            @endif
                            @if ($vj->sap_reversal_reason)
                                <p class="mb-0">
                                    <strong>Reason:</strong> {{ $vj->sap_reversal_reason }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Validation Card --}}
        <div class="col-md-4">
            <div class="card card-outline @if ($vj->validation_status === \App\Models\VerificationJournal::VALIDATION_VALIDATED) card-success @elseif ($vj->validation_status === \App\Models\VerificationJournal::VALIDATION_REJECTED) card-danger @else card-warning @endif">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-check"></i> Validation</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5"><i class="fas fa-flag text-muted"></i> Status</dt>
                        <dd class="col-sm-7">
                            @if ($vj->validation_status === \App\Models\VerificationJournal::VALIDATION_VALIDATED)
                                <span class="vj-chip vj-chip-success">Validated</span>
                            @elseif ($vj->validation_status === \App\Models\VerificationJournal::VALIDATION_REJECTED)
                                <span class="vj-chip vj-chip-danger">Rejected</span>
                            @else
                                <span class="vj-chip vj-chip-warning">Pending</span>
                            @endif
                        </dd>

                        @if ($vj->validated_at)
                            <dt class="col-sm-5"><i class="fas fa-user-check text-muted"></i> Reviewed By</dt>
                            <dd class="col-sm-7">
                                {{ $vj->validatedBy->name }}<br>
                                <small class="text-muted">
                                    {{ date('d-M-Y H:i', strtotime($vj->validated_at . '+8 hours')) }} wita
                                </small>
                            </dd>
                        @endif
                    </dl>

                    @if ($vj->validation_status === \App\Models\VerificationJournal::VALIDATION_REJECTED && $vj->rejection_reason)
                        <div class="vj-alert vj-alert-danger small mb-0 mt-3">
                            <strong>Rejected — reason from reviewer</strong>
                            <div class="mt-1 mb-0">{!! nl2br(e($vj->rejection_reason)) !!}</div>
                        </div>
                    @endif
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
                    <div class="vj-stat-grid">
                        <div class="vj-stat vj-stat-info">
                            <div class="vj-stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="vj-stat-body">
                                <span class="vj-stat-label">Total Amount</span>
                                <span class="vj-stat-value">Rp. {{ number_format($vj->amount, 2) }}</span>
                            </div>
                        </div>
                        <div class="vj-stat vj-stat-success">
                            <div class="vj-stat-icon">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="vj-stat-body">
                                <span class="vj-stat-label">Total Debit</span>
                                <span class="vj-stat-value">
                                    Rp. {{ number_format($vj_details->where('debit_credit', 'debit')->sum('amount'), 2) }}
                                </span>
                            </div>
                        </div>
                        <div class="vj-stat vj-stat-danger">
                            <div class="vj-stat-icon">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="vj-stat-body">
                                <span class="vj-stat-label">Total Credit</span>
                                <span class="vj-stat-value">
                                    Rp. {{ number_format($vj_details->where('debit_credit', 'credit')->sum('amount'), 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @if ($vj->description)
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div class="vj-note">
                                    <i class="fas fa-align-left"></i>
                                    <span>{{ $vj->description }}</span>
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
            <div class="vj-actions">
                @if ($canValidateVj && $vj->validation_status === \App\Models\VerificationJournal::VALIDATION_PENDING && empty($vj->sap_journal_no))
                    <div class="vj-actions-primary">
                        <button type="button" class="vj-btn vj-btn-success" id="validate-vj-btn">
                            <i class="fas fa-check"></i> Validate
                        </button>
                        <button type="button" class="vj-btn vj-btn-danger-outline" data-toggle="modal"
                            data-target="#reject-vj-modal">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                @endif

                @if (empty($vj->sap_journal_no) && $canSubmitToSap && $vj->validation_status === \App\Models\VerificationJournal::VALIDATION_VALIDATED)
                    <div class="vj-actions-primary">
                        <button type="button" class="vj-btn vj-btn-success" id="submit-to-sap-btn">
                            <i class="fas fa-paper-plane"></i> Submit to SAP B1
                        </button>
                    </div>
                @elseif (empty($vj->sap_journal_no) && $canSubmitToSap && $vj->validation_status !== \App\Models\VerificationJournal::VALIDATION_VALIDATED)
                    <div class="vj-actions-note">
                        <i class="fas fa-info-circle"></i>
                        <span>
                            This journal must be validated before it can be submitted to SAP B1.
                            @if ($vj->validation_status === \App\Models\VerificationJournal::VALIDATION_REJECTED)
                                Please address the rejection reason and have the journal re-validated.
                            @endif
                        </span>
                    </div>
                @endif

                @if ($vj->sap_journal_no && $canReverseSap)
                    <div class="vj-actions-primary">
                        @if ($vj->sap_je_jdt_num)
                            <button type="button" class="vj-btn vj-btn-danger" id="reverse-to-sap-btn"
                                data-toggle="modal" data-target="#reverse-sap-auto">
                                <i class="fas fa-undo"></i> Reverse in SAP B1
                            </button>
                        @else
                            <button type="button" class="vj-btn vj-btn-danger" data-toggle="modal"
                                data-target="#reverse-sap-manual">
                                <i class="fas fa-undo"></i> Record Manual Reversal
                            </button>
                        @endif
                    </div>
                @endif

                <div class="vj-actions-toolbar">
                    @if ($canEditVjDetails)
                        <a href="{{ route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $vj->id]) }}"
                            class="vj-action-item vj-action-edit">
                            <i class="fas fa-edit"></i>
                            <span>Edit Details</span>
                        </a>
                    @endif
                    <a href="{{ route('accounting.sap-sync.export', ['vj_id' => $vj->id]) }}"
                        class="vj-action-item vj-action-export">
                        <i class="fas fa-file-excel"></i>
                        <span>Export</span>
                    </a>
                    <a href="{{ route('verifications.journal.print', $vj->id) }}"
                        class="vj-action-item vj-action-print" target="_blank">
                        <i class="fas fa-print"></i>
                        <span>Print</span>
                    </a>
                    @if ($canManageSapInfo)
                        <button type="button"
                            class="vj-action-item vj-action-item-btn vj-action-sap {{ ! $canManageSapInfoForVj || $vj->sap_journal_no ? 'is-disabled' : '' }}"
                            data-toggle="modal" data-target="#update-sap"
                            @if (! $canManageSapInfoForVj || $vj->sap_journal_no) disabled @endif>
                            <i class="fas fa-sync"></i>
                            <span>Update SAP</span>
                        </button>
                        <form action="{{ route('accounting.sap-sync.cancel_sap_info') }}" method="POST"
                            class="cancel-sap-info-form vj-action-item-form">
                            @csrf
                            <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
                            <button type="submit"
                                class="vj-action-item vj-action-item-btn vj-action-cancel {{ ! $canManageSapInfoForVj || $vj->sap_journal_no ? 'is-disabled' : '' }}"
                                @if (! $canManageSapInfoForVj || $vj->sap_journal_no) disabled
                                    @if ($vj->sap_journal_no)
                                        title="Cannot cancel: Journal already submitted to SAP B1. Reversal must be done in SAP B1 first."
                                    @elseif ($vj->validation_status === \App\Models\VerificationJournal::VALIDATION_REJECTED)
                                        title="Cannot update SAP info while the journal is rejected."
                                    @endif
                                @endif>
                                <i class="fas fa-times-circle"></i>
                                <span>Cancel SAP</span>
                            </button>
                        </form>
                    @endif
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
                        <span class="vj-chip vj-chip-primary ml-2">{{ $vj_details->count() }} lines</span>
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
                                            <span class="vj-chip vj-chip-info">{{ $item['project'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="vj-chip vj-chip-neutral">{{ $item['cost_center'] }}</span>
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
                                @php
                                    $isReversal = ($log->action ?? 'submission') === 'reversal';
                                    $logChipClass = $isReversal
                                        ? ($log->status === 'success' ? 'vj-chip-neutral' : 'vj-chip-danger')
                                        : ($log->status === 'success' ? 'vj-chip-success' : 'vj-chip-danger');
                                    $logDateClass = $log->status === 'success'
                                        ? ($isReversal ? 'vj-timeline-date-neutral' : 'vj-timeline-date-success')
                                        : 'vj-timeline-date-danger';
                                    $logIconClass = $isReversal
                                        ? ($log->status === 'success' ? 'vj-timeline-icon-neutral' : 'vj-timeline-icon-danger')
                                        : ($log->status === 'success' ? 'vj-timeline-icon-success' : 'vj-timeline-icon-danger');
                                    $logIcon = $isReversal
                                        ? ($log->status === 'success' ? 'undo' : 'times-circle')
                                        : ($log->status === 'success' ? 'check-circle' : 'times-circle');
                                @endphp
                                <div class="time-label">
                                    <span class="vj-timeline-date {{ $logDateClass }}">
                                        {{ date('d M Y', strtotime($log->created_at)) }}
                                    </span>
                                </div>
                                <div>
                                    <i class="fas fa-{{ $logIcon }} {{ $logIconClass }}"></i>
                                    <div class="timeline-item">
                                        <span class="vj-timeline-time">
                                            <i class="fas fa-clock"></i> {{ date('H:i', strtotime($log->created_at)) }}
                                        </span>
                                        <h3 class="timeline-header">
                                            @if ($isReversal)
                                                Reversal -
                                                <span class="vj-chip {{ $logChipClass }}">
                                                    {{ $log->status === 'success' ? 'REVERSED' : 'FAILED' }}
                                                </span>
                                            @else
                                                Attempt #{{ $log->attempt_number }} -
                                                <span class="vj-chip {{ $logChipClass }}">
                                                    {{ strtoupper($log->status) }}
                                                </span>
                                            @endif
                                            @if ($log->user)
                                                <small class="text-muted">by {{ $log->user->name }}</small>
                                            @endif
                                        </h3>
                                        @if ($isReversal && $log->status === 'success')
                                            <div class="timeline-body">
                                                <p><strong>Original SAP Journal Number:</strong>
                                                    <span class="vj-chip vj-chip-neutral">{{ $log->sap_journal_number }}</span>
                                                </p>
                                                @if ($log->error_message)
                                                    <p class="mb-0"><strong>Reason:</strong> {{ $log->error_message }}</p>
                                                @endif
                                            </div>
                                        @elseif ($log->status === 'success')
                                            <div class="timeline-body">
                                                <p><strong>SAP Journal Number:</strong>
                                                    <span class="vj-chip vj-chip-success">{{ $log->sap_journal_number }}</span>
                                                </p>
                                            </div>
                                        @else
                                            <div class="timeline-body">
                                                <p class="mb-2"><strong>Error:</strong></p>
                                                <div class="vj-alert vj-alert-danger mb-0">
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
    </div>{{-- /.vj-show --}}

    {{-- MODAL UPDATE - SAP --}}
    @if ($canManageSapInfo && $canManageSapInfoForVj)
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
    @endif

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

    <form id="validate-vj-form" action="{{ route('accounting.sap-sync.validate') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
    </form>

    {{-- REJECT VJ --}}
    @if ($canValidateVj && $vj->validation_status === \App\Models\VerificationJournal::VALIDATION_PENDING && empty($vj->sap_journal_no))
        <div class="modal fade" id="reject-vj-modal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="reject-vj-form" action="{{ route('accounting.sap-sync.reject') }}" method="POST">
                        @csrf
                        <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-times-circle"></i> Reject Verification Journal
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-2">A reason is required so the creator knows what to fix.</p>
                            <div class="form-group">
                                <label for="vj_rejection_reason">Reason for rejection <span class="text-danger">*</span></label>
                                <textarea name="rejection_reason" id="vj_rejection_reason" class="form-control" rows="4"
                                    required maxlength="2000" minlength="1"
                                    placeholder="Describe why this verification journal is being rejected"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-danger" id="confirm-reject-vj-btn">
                                <i class="fas fa-times"></i> Confirm Reject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- REVERSE SAP AUTO --}}
    @if ($vj->sap_journal_no && $canReverseSap && $vj->sap_je_jdt_num)
        <div class="modal fade" id="reverse-sap-auto">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="reverse-sap-form" action="{{ route('accounting.sap-sync.reverse_to_sap') }}" method="POST">
                        @csrf
                        <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-undo"></i> Reverse Journal in SAP B1
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <h6 class="font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Important</h6>
                                <ul class="mb-0 pl-3">
                                    <li>This will call SAP B1's Cancel action and create a reversing journal entry.</li>
                                    <li>This app will unlock the journal so it can be edited and resubmitted.</li>
                                    <li>Linked realizations will return to <strong>verification-complete</strong>.</li>
                                </ul>
                            </div>
                            <dl class="row mb-3">
                                <dt class="col-sm-4">Journal No</dt>
                                <dd class="col-sm-8"><strong>{{ $vj->nomor }}</strong></dd>
                                <dt class="col-sm-4">SAP Journal No</dt>
                                <dd class="col-sm-8"><strong class="text-success">{{ $vj->sap_journal_no }}</strong></dd>
                                <dt class="col-sm-4">SAP Internal Key</dt>
                                <dd class="col-sm-8"><code>{{ $vj->sap_je_jdt_num }}</code></dd>
                            </dl>
                            <div class="form-group">
                                <label for="reverse_reason">Reason for reversal <span class="text-danger">*</span></label>
                                <textarea name="reason" id="reverse_reason" class="form-control" rows="3"
                                    required maxlength="1000"
                                    placeholder="Explain why this journal must be reversed..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-sm btn-danger" id="confirm-reverse-btn">
                                <i class="fas fa-undo"></i> Confirm Reverse in SAP B1
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- REVERSE SAP MANUAL --}}
    @if ($vj->sap_journal_no && $canReverseSap && ! $vj->sap_je_jdt_num)
        <div class="modal fade" id="reverse-sap-manual">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('accounting.sap-sync.record_manual_reversal') }}" method="POST"
                        id="manual-reverse-form">
                        @csrf
                        <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title">
                                <i class="fas fa-undo"></i> Record Manual Reversal
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                This journal was posted before automatic reversal tracking was available.
                                Reverse it in the SAP B1 client first, then record the reversal here to unlock
                                the journal in this app.
                            </div>
                            <dl class="row mb-3">
                                <dt class="col-sm-4">Journal No</dt>
                                <dd class="col-sm-8"><strong>{{ $vj->nomor }}</strong></dd>
                                <dt class="col-sm-4">SAP Journal No</dt>
                                <dd class="col-sm-8"><strong class="text-success">{{ $vj->sap_journal_no }}</strong></dd>
                            </dl>
                            <div class="form-group">
                                <label for="manual_sap_reversal_journal_no">SAP Reversal Journal No (optional)</label>
                                <input type="text" name="sap_reversal_journal_no" id="manual_sap_reversal_journal_no"
                                    class="form-control" maxlength="100"
                                    placeholder="Journal number created by SAP B1 after reverse">
                            </div>
                            <div class="form-group">
                                <label for="manual_reverse_reason">Reason for reversal <span
                                        class="text-danger">*</span></label>
                                <textarea name="reason" id="manual_reverse_reason" class="form-control" rows="3"
                                    required maxlength="1000"
                                    placeholder="Explain why this journal was reversed..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="fas fa-save"></i> Record Reversal & Unlock
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @push('styles')
        <style>
            /* ── VJ Show page design system ── */
            .vj-show .card-outline {
                border: 1px solid #e9ecef;
                border-radius: 10px;
                box-shadow: none;
                transition: box-shadow 0.2s ease;
            }

            .vj-show .card-outline:hover {
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
                transform: none;
            }

            .vj-show .card-outline .card-header {
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
                border-radius: 10px 10px 0 0;
                padding: 0.65rem 1rem;
            }

            .vj-show .card-outline .card-header .card-title {
                font-size: 0.9rem;
                font-weight: 600;
                color: #495057;
                margin-bottom: 0;
            }

            .vj-show .card-outline .card-header .card-title i {
                color: #6c757d;
                margin-right: 0.35rem;
            }

            .vj-show .card-outline .card-body {
                padding: 0.85rem 1rem;
            }

            .vj-show dl dt {
                font-size: 0.8125rem;
                color: #6c757d;
                font-weight: 500;
            }

            .vj-show dl dd {
                font-size: 0.875rem;
                margin-bottom: 0.5rem;
            }

            .vj-show dl dd:last-child {
                margin-bottom: 0;
            }

            /* Chips (badges) */
            .vj-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.2rem 0.55rem;
                border-radius: 6px;
                font-size: 0.75rem;
                font-weight: 600;
                line-height: 1.3;
                border: 1px solid transparent;
                white-space: nowrap;
            }

            .vj-chip-info {
                background: #e8f7fa;
                border-color: #b2ebf2;
                color: #0c6674;
            }

            .vj-chip-neutral {
                background: #f1f3f5;
                border-color: #dee2e6;
                color: #495057;
            }

            .vj-chip-success {
                background: #e8f5e9;
                border-color: #c8e6c9;
                color: #198754;
            }

            .vj-chip-danger {
                background: #ffebee;
                border-color: #ffcdd2;
                color: #c62828;
            }

            .vj-chip-warning {
                background: #fff8e1;
                border-color: #ffe082;
                color: #b8860b;
            }

            .vj-chip-primary {
                background: #e7f1ff;
                border-color: #b8d4fe;
                color: #0d47a1;
            }

            .vj-chip-on-dark {
                background: rgba(255, 255, 255, 0.95);
                border-color: rgba(255, 255, 255, 0.6);
                color: #343a40;
                font-size: 0.875rem;
                padding: 0.35rem 0.75rem;
                border-radius: 8px;
            }

            /* Financial stat cards */
            .vj-stat-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 0.75rem;
            }

            .vj-stat {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.85rem 1rem;
                border-radius: 10px;
                border: 1px solid #e9ecef;
                background: #fff;
            }

            .vj-stat-icon {
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1rem;
                flex-shrink: 0;
            }

            .vj-stat-info .vj-stat-icon {
                background: #e8f7fa;
                color: #17a2b8;
                border: 1px solid #b2ebf2;
            }

            .vj-stat-success .vj-stat-icon {
                background: #e8f5e9;
                color: #198754;
                border: 1px solid #c8e6c9;
            }

            .vj-stat-danger .vj-stat-icon {
                background: #ffebee;
                color: #dc3545;
                border: 1px solid #ffcdd2;
            }

            .vj-stat-body {
                display: flex;
                flex-direction: column;
                gap: 0.15rem;
                min-width: 0;
            }

            .vj-stat-label {
                font-size: 0.7rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                color: #6c757d;
            }

            .vj-stat-value {
                font-size: 1rem;
                font-weight: 700;
                color: #212529;
                line-height: 1.2;
            }

            /* Notes & alerts */
            .vj-note {
                display: flex;
                align-items: flex-start;
                gap: 0.5rem;
                padding: 0.65rem 0.75rem;
                border-radius: 8px;
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                color: #495057;
                font-size: 0.875rem;
                line-height: 1.45;
            }

            .vj-note i {
                margin-top: 0.15rem;
                color: #6c757d;
            }

            .vj-alert {
                padding: 0.65rem 0.75rem;
                border-radius: 8px;
                font-size: 0.875rem;
                line-height: 1.45;
                border: 1px solid;
            }

            .vj-alert-danger {
                background: #fff5f5;
                border-color: #f1c2c7;
                color: #842029;
            }

            .vj-alert-danger code {
                background: transparent;
                color: inherit;
                font-size: 0.8125rem;
                white-space: pre-wrap;
                word-break: break-word;
            }

            .vj-alert-secondary {
                background: #f8f9fa;
                border-color: #dee2e6;
                color: #495057;
            }

            /* Table */
            .vj-show .table-hover tbody tr:hover {
                background-color: rgba(0, 123, 255, 0.04);
            }

            /* Timeline */
            .vj-show .timeline {
                position: relative;
                padding: 0.5rem 0;
            }

            .vj-show .timeline > div:not(.time-label) {
                position: relative;
                padding-left: 3.25rem;
            }

            .vj-show .timeline-item {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 10px;
                padding: 0.85rem 1rem;
                margin: 0 0 1.25rem 0;
                position: relative;
            }

            .vj-show .timeline-item::before {
                content: '';
                position: absolute;
                left: -9px;
                top: 1.1rem;
                display: block;
                width: 0;
                height: 0;
                border: solid transparent;
                border-width: 7px;
                border-right-color: #e9ecef;
            }

            .vj-show .time-label {
                position: relative;
                padding: 0.5rem 0 0.25rem;
            }

            .vj-timeline-date {
                display: inline-flex;
                align-items: center;
                padding: 0.3rem 0.65rem;
                border-radius: 8px;
                font-size: 0.75rem;
                font-weight: 600;
                border: 1px solid transparent;
            }

            .vj-timeline-date-success {
                background: #e8f5e9;
                border-color: #c8e6c9;
                color: #198754;
            }

            .vj-timeline-date-danger {
                background: #ffebee;
                border-color: #ffcdd2;
                color: #c62828;
            }

            .vj-timeline-date-neutral {
                background: #f1f3f5;
                border-color: #dee2e6;
                color: #495057;
            }

            .vj-show .timeline > div:not(.time-label) > i.fas {
                position: absolute;
                left: 0;
                top: 0.65rem;
                width: 2rem;
                height: 2rem;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.8rem;
                border: 1px solid transparent;
            }

            .vj-timeline-icon-success {
                background: #e8f5e9;
                border-color: #c8e6c9;
                color: #198754;
            }

            .vj-timeline-icon-danger {
                background: #ffebee;
                border-color: #ffcdd2;
                color: #c62828;
            }

            .vj-timeline-icon-neutral {
                background: #f1f3f5;
                border-color: #dee2e6;
                color: #495057;
            }

            .vj-show .timeline-header {
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
                font-weight: 600;
                color: #343a40;
            }

            .vj-show .timeline-body {
                padding-top: 0.25rem;
                font-size: 0.875rem;
                color: #495057;
            }

            .vj-timeline-time {
                float: right;
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.2rem 0.55rem;
                background: #f1f3f5;
                border: 1px solid #e9ecef;
                border-radius: 6px;
                font-size: 0.75rem;
                font-weight: 500;
                color: #6c757d;
            }

            .vj-actions {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 10px;
                padding: 0.85rem 1rem;
            }

            .vj-actions-primary {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 0.75rem;
            }

            .vj-actions-note {
                display: flex;
                align-items: flex-start;
                gap: 0.5rem;
                padding: 0.65rem 0.75rem;
                margin-bottom: 0.75rem;
                border-radius: 8px;
                background: #e7f3ff;
                border: 1px solid #b8daff;
                color: #0c5460;
                font-size: 0.875rem;
                line-height: 1.45;
            }

            .vj-actions-note i {
                margin-top: 0.15rem;
                color: #17a2b8;
            }

            .vj-actions-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
                padding-top: 0.15rem;
                border-top: 1px solid #f1f3f5;
            }

            .vj-actions-primary + .vj-actions-toolbar {
                padding-top: 0.75rem;
            }

            .vj-actions-note + .vj-actions-toolbar {
                padding-top: 0.75rem;
            }

            .vj-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.45rem 0.9rem;
                border-radius: 8px;
                border: 1px solid transparent;
                font-size: 0.875rem;
                font-weight: 500;
                line-height: 1.2;
                transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
            }

            .vj-btn-success {
                background: #198754;
                color: #fff;
            }

            .vj-btn-success:hover {
                background: #157347;
                color: #fff;
            }

            .vj-btn-danger {
                background: #dc3545;
                color: #fff;
            }

            .vj-btn-danger:hover {
                background: #bb2d3b;
                color: #fff;
            }

            .vj-btn-danger-outline {
                background: #fff;
                color: #dc3545;
                border-color: #f1c2c7;
            }

            .vj-btn-danger-outline:hover {
                background: #fff5f5;
                color: #bb2d3b;
                border-color: #f1aeb5;
            }

            .vj-action-item,
            .vj-action-item-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.4rem 0.7rem;
                border: 1px solid transparent;
                border-radius: 8px;
                background: transparent;
                color: #495057;
                font-size: 0.8125rem;
                font-weight: 500;
                text-decoration: none;
                transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
            }

            .vj-action-edit {
                background: #fff8e1;
                border-color: #ffe082;
                color: #b8860b;
            }

            .vj-action-edit i {
                color: #f0ad4e;
            }

            .vj-action-edit:hover:not(.is-disabled) {
                background: #ffecb3;
                border-color: #ffd54f;
                color: #8a6d0a;
                text-decoration: none;
            }

            .vj-action-export {
                background: #e8f7fa;
                border-color: #b2ebf2;
                color: #0c6674;
            }

            .vj-action-export i {
                color: #17a2b8;
            }

            .vj-action-export:hover {
                background: #d1f2f7;
                border-color: #80deea;
                color: #0a4f5a;
                text-decoration: none;
            }

            .vj-action-print {
                background: #f1f3f5;
                border-color: #dee2e6;
                color: #495057;
            }

            .vj-action-print i {
                color: #6c757d;
            }

            .vj-action-print:hover {
                background: #e9ecef;
                border-color: #ced4da;
                color: #212529;
                text-decoration: none;
            }

            .vj-action-sap {
                background: #fff3e0;
                border-color: #ffcc80;
                color: #e65100;
            }

            .vj-action-sap i {
                color: #ff9800;
            }

            .vj-action-sap:hover:not(:disabled):not(.is-disabled) {
                background: #ffe0b2;
                border-color: #ffb74d;
                color: #bf360c;
            }

            .vj-action-cancel {
                background: #ffebee;
                border-color: #ffcdd2;
                color: #c62828;
            }

            .vj-action-cancel i {
                color: #dc3545;
            }

            .vj-action-cancel:hover:not(:disabled):not(.is-disabled) {
                background: #ffcdd2;
                border-color: #ef9a9a;
                color: #b71c1c;
            }

            .vj-action-item:hover,
            .vj-action-item-btn:hover:not(:disabled):not(.is-disabled) {
                text-decoration: none;
            }

            .vj-action-item i,
            .vj-action-item-btn i {
                width: 0.95rem;
                text-align: center;
                font-size: 0.8rem;
            }

            .vj-action-item.is-disabled,
            .vj-action-item-btn.is-disabled,
            .vj-action-item-btn:disabled {
                opacity: 0.45;
                pointer-events: none;
                cursor: not-allowed;
            }

            .vj-action-item-form {
                display: inline-flex;
                margin: 0;
            }

            .vj-inline-actions {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                vertical-align: middle;
            }

            .vj-action-item-xs {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .vj-action-item-xs i {
                font-size: 0.7rem;
                width: 0.85rem;
            }

            .vj-action-back {
                background: rgba(255, 255, 255, 0.95);
                border-color: rgba(255, 255, 255, 0.6);
                color: #343a40;
            }

            .vj-action-back:hover {
                background: #fff;
                border-color: #fff;
                color: #212529;
                text-decoration: none;
            }

            @media (max-width: 768px) {
                .vj-stat-grid {
                    grid-template-columns: 1fr;
                }

                .vj-show .timeline > div:not(.time-label) {
                    padding-left: 2.75rem;
                }

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
                                <li>If needed later, authorized users can reverse the posted journal from this page.</li>
                            </ul>
                        </div>
                        ${attemptsHtml}
                    </div>
                `;
            }

            $(document).ready(function() {
                const $submitBtn = $('#submit-to-sap-btn');
                const $validateBtn = $('#validate-vj-btn');

                if ($validateBtn.length) {
                    $validateBtn.on('click', function() {
                        Swal.fire({
                            title: 'Validate this verification journal?',
                            html: '<p>This journal will be marked as validated and can then be submitted to SAP B1.</p>',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, validate',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $validateBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Validating...');
                                $('#validate-vj-form').trigger('submit');
                            }
                        });
                    });
                }

                const $rejectForm = $('#reject-vj-form');
                if ($rejectForm.length) {
                    $rejectForm.on('submit', function(e) {
                        e.preventDefault();
                        const form = this;
                        const reason = $('#vj_rejection_reason').val().trim();

                        if (!reason) {
                            Swal.fire({
                                title: 'Reason required',
                                text: 'Please provide a reason for rejection.',
                                icon: 'warning',
                            });
                            return;
                        }

                        Swal.fire({
                            title: 'Reject this verification journal?',
                            html: '<p>The creator will see your rejection reason and can fix the journal before resubmitting for validation.</p>',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, reject',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#dc3545',
                            reverseButtons: true,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $('#confirm-reject-vj-btn').prop('disabled', true)
                                    .html('<i class="fas fa-spinner fa-spin"></i> Rejecting...');
                                form.submit();
                            }
                        });
                    });
                }

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

                @if ($canManageSapInfo)
                    $('.cancel-sap-info-form').on('submit', function(e) {
                        const $button = $(this).find('button');
                        if ($button.hasClass('is-disabled') || $button.prop('disabled')) {
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
                @endif

                const $reverseForm = $('#reverse-sap-form');
                if ($reverseForm.length) {
                    $reverseForm.on('submit', function(e) {
                        e.preventDefault();
                        const form = this;
                        const reason = $('#reverse_reason').val().trim();

                        if (!reason) {
                            Swal.fire({
                                title: 'Reason required',
                                text: 'Please provide a reason for the reversal.',
                                icon: 'warning',
                            });
                            return;
                        }

                        Swal.fire({
                            title: 'Reverse this journal in SAP B1?',
                            html: '<p>This will cancel the journal in SAP B1 and unlock it in this app for correction.</p>',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, reverse in SAP B1',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#dc3545',
                            reverseButtons: true,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.fire({
                                    title: 'Reversing...',
                                    html: 'Please wait while we cancel this journal in SAP B1.',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                        $('#confirm-reverse-btn').prop('disabled', true)
                                            .html('<i class="fas fa-spinner fa-spin"></i> Reversing...');
                                        form.submit();
                                    }
                                });
                            }
                        });
                    });
                }

                const $manualReverseForm = $('#manual-reverse-form');
                if ($manualReverseForm.length) {
                    $manualReverseForm.on('submit', function(e) {
                        e.preventDefault();
                        const form = this;
                        const reason = $('#manual_reverse_reason').val().trim();

                        if (!reason) {
                            Swal.fire({
                                title: 'Reason required',
                                text: 'Please provide a reason for the reversal.',
                                icon: 'warning',
                            });
                            return;
                        }

                        Swal.fire({
                            title: 'Record manual reversal?',
                            html: '<p>Confirm that you have already reversed this journal in SAP B1. This will unlock the journal in this app.</p>',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, record & unlock',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                }

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
