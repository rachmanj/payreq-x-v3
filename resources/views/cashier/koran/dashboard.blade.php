@extends('templates.main')

@section('title_page')
    Rekening Koran
@endsection

@section('breadcrumb_title')
    cashier / koran / dashboard
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-koran-links page="dashboard" />

            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-university"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Accounts</span>
                            <span class="info-box-number">{{ $statistics['total_accounts'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Completed</span>
                            <span class="info-box-number">{{ $statistics['completed_months'] }}/{{ $statistics['total_months'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Missing</span>
                            <span class="info-box-number">{{ $statistics['missing_months'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-percentage"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Completion</span>
                            <span class="info-box-number">{{ $statistics['completion_percentage'] }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header koran-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <strong>Rekening Koran Dashboard - {{ $year }}</strong>
                        </h3>
                        <div class="year-selector">
                            @foreach ([2026, 2025, 2024] as $yearOption)
                                <a href="{{ route('cashier.koran.index', ['page' => 'dashboard', 'year' => $yearOption]) }}"
                                    class="year-btn {{ (int) $year === $yearOption ? 'active' : '' }}">
                                    {{ $yearOption }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="koran-recon-legend mt-2 small text-white">
                        <span class="mr-3" style="opacity:.85"><i class="fas fa-mouse-pointer mr-1"></i>Klik sel untuk detail / upload</span>
                        <span class="mr-3"><i class="fas fa-times-circle text-danger mr-1"></i>Belum upload</span>
                        <span class="mr-3"><i class="fas fa-check-circle text-success mr-1"></i>Terupload</span>
                        <span class="mr-3"><i class="fas fa-balance-scale text-primary mr-1"></i>Belum rekonsiliasi</span>
                        <span class="mr-3"><i class="fas fa-spinner text-warning mr-1"></i>Memproses</span>
                        <span class="mr-3"><i class="fas fa-tasks text-info mr-1"></i>Dalam review</span>
                        <span><i class="fas fa-check-double text-success mr-1"></i>Selesai</span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive koran-table-wrapper">
                        <table class="table table-sm koran-table">
                            <thead class="koran-table-header">
                                <tr>
                                    <th class="text-center" style="width: 40px">#</th>
                                    <th style="min-width: 150px">Account Number</th>
                                    <th style="min-width: 200px">Account Name</th>
                                    <th style="min-width: 80px">Project</th>
                                    <th class="text-center" style="min-width: 100px">Progress</th>
                                    @foreach (['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $monthLabel)
                                        <th class="text-center" style="min-width: 56px">{{ $monthLabel }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($korans as $koran)
                                    @foreach ($koran['giros'] as $index => $giro)
                                        <tr class="koran-table-row">
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <strong class="account-number">{{ $giro['acc_no'] }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $giro['bank_name'] }}</small>
                                            </td>
                                            <td>
                                                <span class="account-name">{{ $giro['acc_name'] }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $giro['project'] }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="progress-wrapper">
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar
                                                            @if ($giro['completion_percentage'] == 100) bg-success
                                                            @elseif($giro['completion_percentage'] >= 75) bg-info
                                                            @elseif($giro['completion_percentage'] >= 50) bg-warning
                                                            @else bg-danger
                                                            @endif"
                                                            role="progressbar"
                                                            style="width: {{ $giro['completion_percentage'] }}%"
                                                            aria-valuenow="{{ $giro['completion_percentage'] }}"
                                                            aria-valuemin="0"
                                                            aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <small class="progress-text">
                                                        {{ $giro['completed_count'] }}/{{ $giro['total_months'] }} ({{ $giro['completion_percentage'] }}%)
                                                    </small>
                                                </div>
                                            </td>
                                            @foreach ($giro['data'] as $month)
                                                @php
                                                    $canDeleteCell = $canDeleteKoran && ($hasElevatedKoranAccess || $giro['project'] === auth()->user()->project);
                                                    $brId = $month['reconciliation_id'] ?? null;
                                                    $brStatus = $month['reconciliation_status'] ?? null;
                                                    $brValidation = $month['reconciliation_validation_status'] ?? null;
                                                    $reconciliationLocked = $brId && (
                                                        $brStatus === \App\Models\BankReconciliation::STATUS_COMPLETED
                                                        || $brValidation === \App\Models\BankReconciliation::VALIDATION_PENDING
                                                    );

                                                    $reconDotClass = 'recon-dot-none';
                                                    $reconDotIcon = 'fa-balance-scale';
                                                    if ($month['status']) {
                                                        if ($brId && $brStatus === \App\Models\BankReconciliation::STATUS_COMPLETED && $brValidation === \App\Models\BankReconciliation::VALIDATION_VALIDATED) {
                                                            $reconDotClass = 'recon-dot-done';
                                                            $reconDotIcon = 'fa-check-double';
                                                        } elseif ($brId && $brValidation === \App\Models\BankReconciliation::VALIDATION_PENDING) {
                                                            $reconDotClass = 'recon-dot-pending';
                                                            $reconDotIcon = 'fa-user-check';
                                                        } elseif ($brId && $brValidation === \App\Models\BankReconciliation::VALIDATION_REJECTED) {
                                                            $reconDotClass = 'recon-dot-danger';
                                                            $reconDotIcon = 'fa-undo';
                                                        } elseif ($brId && $brStatus === \App\Models\BankReconciliation::STATUS_FAILED) {
                                                            $reconDotClass = 'recon-dot-danger';
                                                            $reconDotIcon = 'fa-exclamation-triangle';
                                                        } elseif ($brId && $brStatus === \App\Models\BankReconciliation::STATUS_PROCESSING) {
                                                            $reconDotClass = 'recon-dot-processing';
                                                            $reconDotIcon = 'fa-spinner fa-spin';
                                                        } elseif ($brId && in_array($brStatus, [\App\Models\BankReconciliation::STATUS_IN_REVIEW, \App\Models\BankReconciliation::STATUS_DRAFT], true)) {
                                                            $reconDotClass = 'recon-dot-review';
                                                            $reconDotIcon = 'fa-tasks';
                                                        } elseif ($brId) {
                                                            $reconDotClass = 'recon-dot-review';
                                                            $reconDotIcon = 'fa-tasks';
                                                        } else {
                                                            $reconDotClass = 'recon-dot-start';
                                                            $reconDotIcon = 'fa-balance-scale';
                                                        }
                                                    }

                                                    $cellTemplateId = 'koran-cell-template-'.$giro['giro_id'].'-'.$month['month'];
                                                @endphp
                                                <td class="text-center status-cell">
                                                    <button type="button"
                                                        class="koran-cell-trigger {{ $month['status'] ? 'koran-cell-uploaded' : 'koran-cell-missing' }}"
                                                        data-toggle="modal"
                                                        data-target="#koran-cell-modal"
                                                        data-has-file="{{ $month['status'] ? '1' : '0' }}"
                                                        data-giro-id="{{ $giro['giro_id'] }}"
                                                        data-acc-no="{{ $giro['acc_no'] }}"
                                                        data-acc-name="{{ $giro['acc_name'] }}"
                                                        data-bank-name="{{ $giro['bank_name'] }}"
                                                        data-project="{{ $giro['project'] }}"
                                                        data-month="{{ $month['month'] }}"
                                                        data-month-label="{{ $month['month_label'] }}"
                                                        data-periode="{{ $month['periode'] }}"
                                                        data-year="{{ $year }}"
                                                        data-dokumen-id="{{ $month['dokumen_id'] ?? '' }}"
                                                        data-filename="{{ $month['filename1'] ?? '' }}"
                                                        data-upload-date="{{ $month['upload_date'] ?? '' }}"
                                                        data-uploaded-by="{{ $month['uploaded_by'] ?? '' }}"
                                                        data-can-upload="{{ $canUploadKoran ? '1' : '0' }}"
                                                        data-can-delete="{{ $canDeleteCell ? '1' : '0' }}"
                                                        data-reconciliation-locked="{{ $reconciliationLocked ? '1' : '0' }}"
                                                        data-template-id="{{ $month['status'] ? $cellTemplateId : '' }}"
                                                        title="{{ $month['status'] ? 'Uploaded — click for details' : 'Missing — click to upload' }}">
                                                        <span class="koran-cell-main-icon">
                                                            <i class="fas {{ $month['status'] ? 'fa-check' : 'fa-times' }}"></i>
                                                        </span>
                                                        @if ($month['status'])
                                                            <span class="koran-cell-recon-dot {{ $reconDotClass }}">
                                                                <i class="fas {{ $reconDotIcon }}"></i>
                                                            </span>
                                                        @endif
                                                    </button>

                                                    @if ($month['status'])
                                                        <template id="{{ $cellTemplateId }}">
                                                            @include('cashier.koran.partials.cell-reconciliation-block', [
                                                                'month' => $month,
                                                                'giro' => $giro,
                                                                'year' => $year,
                                                            ])
                                                        </template>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="koran-cell-modal" tabindex="-1" role="dialog" aria-labelledby="koranCellModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="koranCellModalLabel">
                        <i class="fas fa-university mr-2"></i>
                        <span id="koran-modal-title-text">Rekening Koran</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="koran-modal-upload-panel" style="display: none;">
                        <div class="alert alert-light border mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Account</small>
                                    <strong id="koran-upload-acc-display"></strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Periode</small>
                                    <strong id="koran-upload-periode-display"></strong>
                                </div>
                            </div>
                        </div>

                        @if ($canUploadKoran)
                            <form action="{{ route('cashier.koran.upload') }}" method="POST" enctype="multipart/form-data" id="koran-modal-upload-form">
                                @csrf
                                <input type="hidden" name="giro_id" id="koran-upload-giro-id">
                                <input type="hidden" name="periode" id="koran-upload-periode">
                                <input type="hidden" name="type" value="koran">

                                <div class="form-group">
                                    <label for="koran-upload-file">Bank Statement PDF</label>
                                    <input type="file" name="file_upload" id="koran-upload-file" class="form-control" accept="application/pdf,.pdf" required>
                                </div>

                                <div class="form-group">
                                    <label for="koran-upload-remarks">Remarks</label>
                                    <textarea name="remarks" id="koran-upload-remarks" class="form-control" rows="2"></textarea>
                                </div>

                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-upload mr-1"></i> Upload Rekening Koran
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-lock mr-1"></i> You do not have permission to upload bank statements.
                            </div>
                        @endif
                    </div>

                    <div id="koran-modal-detail-panel" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Uploaded</small>
                                <strong id="koran-detail-upload-date">-</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Uploaded by</small>
                                <strong id="koran-detail-uploaded-by">-</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Project</small>
                                <strong id="koran-detail-project">-</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <a href="#" id="koran-detail-view-pdf" target="_blank" class="btn btn-info btn-sm">
                                <i class="fas fa-file-pdf mr-1"></i> View PDF
                            </a>
                        </div>

                        <hr>

                        <div id="koran-detail-reconciliation"></div>

                        <div id="koran-modal-delete-section" class="mt-4 pt-3 border-top" style="display: none;">
                            <h6 class="text-danger"><i class="fas fa-trash-alt mr-1"></i> Delete uploaded statement</h6>
                            <p class="text-muted small mb-2" id="koran-modal-delete-help">
                                This removes the PDF file and database record. Reconciliation sessions linked to this file will lose their PDF reference.
                            </p>
                            <div class="alert alert-warning small py-2 mb-2" id="koran-modal-delete-locked-notice" style="display: none;">
                                <i class="fas fa-lock mr-1"></i>
                                Delete is disabled because bank reconciliation is completed or pending validation.
                            </div>
                            <form id="koran-modal-delete-form" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-outline-danger btn-sm" id="koran-modal-delete-btn">
                                    <i class="fas fa-trash mr-1"></i> Delete PDF
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .koran-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }

        .koran-header .card-title {
            color: white;
            font-size: 1.3rem;
        }

        .year-selector {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .year-btn {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .year-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            color: white;
            text-decoration: none;
        }

        .year-btn.active {
            background: white;
            color: #667eea;
            font-weight: 600;
            border-color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .koran-recon-legend {
            opacity: 0.95;
        }

        .info-box {
            box-shadow: 0 0 1px rgba(0, 0, 0, 0.125), 0 1px 3px rgba(0, 0, 0, 0.2);
            border-radius: 0.25rem;
            background-color: #fff;
            display: flex;
            margin-bottom: 1rem;
            min-height: 80px;
            padding: 0.5rem;
            position: relative;
        }

        .info-box-icon {
            border-radius: 0.25rem;
            align-items: center;
            display: flex;
            font-size: 1.875rem;
            justify-content: center;
            width: 70px;
        }

        .info-box-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.8;
            flex: 1;
            padding: 0 10px;
        }

        .info-box-text {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .info-box-number {
            display: block;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .koran-table-wrapper {
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: auto;
        }

        .koran-table {
            margin-bottom: 0;
            width: 100%;
        }

        .koran-table-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f4f6f9;
        }

        .koran-table-header th {
            background-color: #f4f6f9;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 12px 8px;
            white-space: nowrap;
            font-size: 0.85rem;
            color: #495057;
        }

        .koran-table-row {
            transition: all 0.2s ease;
        }

        .koran-table-row:nth-child(even) {
            background-color: #f8f9fa;
        }

        .koran-table-row:nth-child(odd) {
            background-color: #ffffff;
        }

        .koran-table-row:hover {
            background-color: #e3f2fd !important;
        }

        .koran-table-row td {
            padding: 12px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }

        .account-number {
            font-size: 0.9rem;
            color: #212529;
            font-weight: 600;
        }

        .account-name {
            font-size: 0.9rem;
            color: #495057;
        }

        .progress-wrapper {
            min-width: 100px;
        }

        .progress {
            margin-bottom: 4px;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        .progress-text {
            display: block;
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 2px;
            font-weight: 500;
        }

        .status-cell {
            padding: 8px 4px !important;
        }

        .koran-cell-trigger {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: 2px solid transparent;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 0;
        }

        .koran-cell-trigger:hover {
            transform: scale(1.08);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .koran-cell-missing {
            background-color: #fdecea;
            border-color: #f5c6cb;
        }

        .koran-cell-missing .koran-cell-main-icon {
            color: #dc3545;
        }

        .koran-cell-uploaded {
            background-color: #e8f5e9;
            border-color: #c3e6cb;
        }

        .koran-cell-uploaded .koran-cell-main-icon {
            color: #28a745;
        }

        .koran-cell-main-icon {
            font-size: 0.95rem;
        }

        .koran-cell-recon-dot {
            position: absolute;
            bottom: -4px;
            right: -4px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.55rem;
            color: #fff;
            border: 2px solid #fff;
        }

        .recon-dot-start { background-color: #007bff; }
        .recon-dot-processing { background-color: #ffc107; color: #212529; }
        .recon-dot-review { background-color: #17a2b8; }
        .recon-dot-pending { background-color: #6f42c1; }
        .recon-dot-danger { background-color: #dc3545; }
        .recon-dot-done { background-color: #28a745; }

        .badge-purple {
            background-color: #6f42c1;
            color: #fff;
        }

        .btn-outline-purple {
            color: #6f42c1;
            border-color: #6f42c1;
        }

        .btn-outline-purple:hover {
            color: #fff;
            background-color: #6f42c1;
            border-color: #6f42c1;
        }

        @media (max-width: 768px) {
            .koran-header .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
            }

            .year-selector {
                margin-top: 15px;
                width: 100%;
            }

            .year-btn {
                flex: 1;
                text-align: center;
            }

            .koran-cell-trigger {
                width: 36px;
                height: 36px;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(function() {
            var deleteBaseUrl = @json(url('/cashier/koran'));

            $('#koran-cell-modal').on('show.bs.modal', function(event) {
                var $trigger = $(event.relatedTarget);
                if (!$trigger.hasClass('koran-cell-trigger')) {
                    return;
                }

                var hasFile = $trigger.data('has-file') === 1 || $trigger.data('has-file') === '1';
                var accNo = $trigger.data('acc-no');
                var accName = $trigger.data('acc-name');
                var monthLabel = $trigger.data('month-label');
                var year = $trigger.data('year');
                var periode = $trigger.data('periode');
                var giroId = $trigger.data('giro-id');
                var project = $trigger.data('project');
                var dokumenId = $trigger.data('dokumen-id');
                var canDelete = $trigger.data('can-delete') === 1 || $trigger.data('can-delete') === '1';
                var reconciliationLocked = $trigger.data('reconciliation-locked') === 1 || $trigger.data('reconciliation-locked') === '1';

                $('#koran-modal-title-text').text(accNo + ' — ' + accName + ' · ' + monthLabel + ' ' + year);

                if (!hasFile) {
                    $('#koran-modal-upload-panel').show();
                    $('#koran-modal-detail-panel').hide();
                    $('#koran-upload-acc-display').text(accNo + ' · ' + accName + ' (' + project + ')');
                    $('#koran-upload-periode-display').text(monthLabel + ' ' + year);
                    $('#koran-upload-giro-id').val(giroId);
                    $('#koran-upload-periode').val(periode);
                    $('#koran-upload-file').val('');
                    $('#koran-upload-remarks').val('');
                    return;
                }

                $('#koran-modal-upload-panel').hide();
                $('#koran-modal-detail-panel').show();

                $('#koran-detail-upload-date').text($trigger.data('upload-date') || '-');
                $('#koran-detail-uploaded-by').text($trigger.data('uploaded-by') || '-');
                $('#koran-detail-project').text(project || '-');
                $('#koran-detail-view-pdf').attr('href', $trigger.data('filename') || '#');

                var templateId = $trigger.data('template-id');
                var $reconContainer = $('#koran-detail-reconciliation');
                $reconContainer.empty();

                if (templateId) {
                    var template = document.getElementById(templateId);
                    if (template && template.content) {
                        $reconContainer.append(document.importNode(template.content, true));
                    }
                }

                if (canDelete && dokumenId) {
                    $('#koran-modal-delete-section').show();
                    $('#koran-modal-delete-form').attr('action', deleteBaseUrl + '/' + dokumenId);

                    if (reconciliationLocked) {
                        $('#koran-modal-delete-locked-notice').show();
                        $('#koran-modal-delete-btn').prop('disabled', true).addClass('disabled');
                    } else {
                        $('#koran-modal-delete-locked-notice').hide();
                        $('#koran-modal-delete-btn').prop('disabled', false).removeClass('disabled');
                    }
                } else {
                    $('#koran-modal-delete-section').hide();
                    $('#koran-modal-delete-locked-notice').hide();
                    $('#koran-modal-delete-btn').prop('disabled', false).removeClass('disabled');
                }
            });

            $('#koran-modal-delete-btn').on('click', function() {
                if ($(this).prop('disabled')) {
                    return;
                }

                Swal.fire({
                    title: 'Delete bank statement?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $('#koran-modal-delete-form').submit();
                    }
                });
            });
        });
    </script>
@endsection
