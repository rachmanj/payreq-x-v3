@extends('templates.main')

@section('title_page')
    Edit Bilyet - Superadmin
@endsection

@section('breadcrumb_title')
    bilyet
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Navigation Links -->
            <x-bilyet-links page="list" />

            <!-- Edit Form -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <i class="fas fa-edit text-warning"></i> Edit Bilyet - Superadmin Mode
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('cashier.bilyets.index', ['page' => 'list']) }}"
                                class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>

                <form action="{{ route('cashier.bilyets.superadmin_update', $bilyet->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <!-- Superadmin Notice -->
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Superadmin Mode:</strong> You can edit all fields of this bilyet. All changes will be
                            logged in the audit trail.
                        </div>

                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-info-circle"></i> Basic Information
                                </h5>

                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label for="prefix">Prefix</label>
                                            <input type="text" name="prefix" id="prefix"
                                                class="form-control @error('prefix') is-invalid @enderror"
                                                value="{{ old('prefix', $bilyet->prefix) }}">
                                            @error('prefix')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-8">
                                        <div class="form-group">
                                            <label for="nomor">Bilyet Number <span class="text-danger">*</span></label>
                                            <input type="text" name="nomor" id="nomor"
                                                class="form-control @error('nomor') is-invalid @enderror"
                                                value="{{ old('nomor', $bilyet->nomor) }}">
                                            @error('nomor')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="type">Bilyet Type <span class="text-danger">*</span></label>
                                    <select name="type" id="type"
                                        class="form-control @error('type') is-invalid @enderror">
                                        <option value="">Select Type</option>
                                        <option value="cek" {{ old('type', $bilyet->type) == 'cek' ? 'selected' : '' }}>
                                            Check</option>
                                        <option value="bg" {{ old('type', $bilyet->type) == 'bg' ? 'selected' : '' }}>
                                            Bilyet Giro</option>
                                        <option value="LOA" {{ old('type', $bilyet->type) == 'LOA' ? 'selected' : '' }}>
                                            Letter of Authority</option>
                                        <option value="debit"
                                            {{ old('type', $bilyet->type) == 'debit' ? 'selected' : '' }}>
                                            Debit</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="giro_id">Bank Account <span class="text-danger">*</span></label>
                                    <select name="giro_id" id="giro_id"
                                        class="form-control select2bs4 @error('giro_id') is-invalid @enderror">
                                        <option value="">Select Bank Account</option>
                                        @foreach ($giros as $giro)
                                            <option value="{{ $giro->id }}"
                                                {{ old('giro_id', $bilyet->giro_id) == $giro->id ? 'selected' : '' }}>
                                                {{ $giro->bank->name ?? 'N/A' }} - {{ $giro->acc_no }}
                                                ({{ $giro->acc_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('giro_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status"
                                        class="form-control @error('status') is-invalid @enderror">
                                        <option value="onhand"
                                            {{ old('status', $bilyet->status) == 'onhand' ? 'selected' : '' }}>On Hand
                                        </option>
                                        <option value="release"
                                            {{ old('status', $bilyet->status) == 'release' ? 'selected' : '' }}>Released
                                        </option>
                                        <option value="cair"
                                            {{ old('status', $bilyet->status) == 'cair' ? 'selected' : '' }}>Settled
                                        </option>
                                        <option value="void"
                                            {{ old('status', $bilyet->status) == 'void' ? 'selected' : '' }}>Voided
                                        </option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <!-- Status Transition Rules -->
                                    <small class="form-text text-muted">
                                        <strong>Status Transition Rules:</strong><br>
                                        <span class="text-success">✓ Allowed:</span>
                                        @php
                                            $allowedTransitions = [
                                                'onhand' => ['release', 'void'],
                                                'release' => ['cair', 'void'],
                                                'cair' => [],
                                                'void' => [],
                                            ];
                                            $currentStatus = $bilyet->status;
                                            $allowed = $allowedTransitions[$currentStatus] ?? [];
                                        @endphp
                                        @if (!empty($allowed))
                                            @foreach ($allowed as $transition)
                                                {{ \App\Models\Bilyet::STATUS_LABELS[$transition] ?? $transition }}{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        @else
                                            <span class="text-danger">No transitions allowed from
                                                {{ $bilyet->status_label }}</span>
                                        @endif
                                        <br>
                                        <span class="text-warning">⚠️ Invalid transitions require justification in remarks
                                            field</span>
                                    </small>
                                </div>
                            </div>

                            <!-- Dates and Amount -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-calendar-alt"></i> Dates & Amount
                                </h5>

                                <div class="form-group">
                                    <label for="bilyet_date">Bilyet Date</label>
                                    <input type="date" name="bilyet_date" id="bilyet_date"
                                        class="form-control @error('bilyet_date') is-invalid @enderror"
                                        value="{{ old('bilyet_date', $bilyet->bilyet_date ? $bilyet->bilyet_date->format('Y-m-d') : '') }}">
                                    @error('bilyet_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="cair_date">Settlement Date</label>
                                    <input type="date" name="cair_date" id="cair_date"
                                        class="form-control @error('cair_date') is-invalid @enderror"
                                        value="{{ old('cair_date', $bilyet->cair_date ? $bilyet->cair_date->format('Y-m-d') : '') }}">
                                    @error('cair_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="receive_date">Receive Date</label>
                                    <input type="date" name="receive_date" id="receive_date"
                                        class="form-control @error('receive_date') is-invalid @enderror"
                                        value="{{ old('receive_date', $bilyet->receive_date ? $bilyet->receive_date->format('Y-m-d') : '') }}">
                                    @error('receive_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="amount">Amount</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" name="amount" id="amount" step="0.01"
                                            min="0" class="form-control @error('amount') is-invalid @enderror"
                                            value="{{ old('amount', $bilyet->amount) }}">
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-link"></i> Related Information
                                </h5>

                                <div class="form-group">
                                    <label for="loan_id">Loan</label>
                                    <select name="loan_id" id="loan_id"
                                        class="form-control select2bs4 @error('loan_id') is-invalid @enderror">
                                        <option value="">Select Loan</option>
                                        @foreach ($loans as $loan)
                                            <option value="{{ $loan->id }}"
                                                {{ old('loan_id', $bilyet->loan_id) == $loan->id ? 'selected' : '' }}>
                                                {{ $loan->loan_code }} - {{ $loan->description }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('loan_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="project">Project</label>
                                    <select name="project" id="project"
                                        class="form-control select2bs4 @error('project') is-invalid @enderror">
                                        <option value="">Select Project</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->code }}"
                                                {{ old('project', $bilyet->project) == $project->code ? 'selected' : '' }}>
                                                {{ $project->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('project')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-sticky-note"></i> Additional Notes
                                </h5>

                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea name="remarks" id="remarks" rows="4" class="form-control @error('remarks') is-invalid @enderror"
                                        placeholder="Enter any additional remarks or notes...">{{ old('remarks', $bilyet->remarks) }}</textarea>
                                    @error('remarks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <small class="form-text text-muted">
                                        <strong>Note:</strong> If you change the status to an invalid transition, please
                                        provide a detailed justification here (minimum 10 characters). This will be logged
                                        in the audit trail.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Read-only Information -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-secondary mb-3">
                                    <i class="fas fa-info-circle"></i> System Information (Read-only)
                                </h5>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Created By</label>
                                    <input type="text" class="form-control" readonly
                                        value="{{ $bilyet->creator->name ?? 'Unknown' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Created At</label>
                                    <input type="text" class="form-control" readonly
                                        value="{{ $bilyet->created_at ? $bilyet->created_at->format('d-m-Y H:i:s') : '-' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Last Updated</label>
                                    <input type="text" class="form-control" readonly
                                        value="{{ $bilyet->updated_at ? $bilyet->updated_at->format('d-m-Y H:i:s') : '-' }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ route('cashier.bilyets.index', ['page' => 'list']) }}"
                                    class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Update Bilyet
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <style>
        .card-header .card-title {
            font-weight: 600;
        }

        .form-group label {
            font-weight: 500;
            color: #495057;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .alert-warning {
            border-left: 4px solid #ffc107;
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
            font-weight: 500;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: #212529;
        }

        .select2-container {
            width: 100% !important;
        }

        .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
        }

        .form-control:read-only {
            background-color: #f8f9fa;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .card-footer .col-md-6 {
                margin-bottom: 10px;
            }

            .card-footer .col-md-6.text-right {
                text-align: left !important;
            }
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(function() {
            // Initialize Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Date validation
            $('#bilyet_date, #cair_date').on('change', function() {
                const bilyetDate = $('#bilyet_date').val();
                const cairDate = $('#cair_date').val();

                if (bilyetDate && cairDate && cairDate < bilyetDate) {
                    alert('Settlement date cannot be before bilyet date.');
                    $('#cair_date').val('');
                }
            });

            // Form validation before submit
            $('form').on('submit', function(e) {
                const nomor = $('#nomor').val();
                const type = $('#type').val();
                const giroId = $('#giro_id').val();
                const status = $('#status').val();

                if (!nomor || !type || !giroId || !status) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }

                // Confirm before submitting
                if (!confirm(
                        'Are you sure you want to update this bilyet? This action will be logged in the audit trail.'
                    )) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
@endsection
