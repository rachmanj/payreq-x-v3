@extends('templates.main')

@section('title_page')
    PCBC
@endsection

@section('breadcrumb_title')
    cashier / pcbc / create
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Info Alert -->
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-info"></i> Petty Cash Balance Control (PCBC)</h5>
                Record physical cash count by denomination and compare with system and SAP amounts. All amounts are in Indonesian Rupiah (IDR).
            </div>

            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Create New PCBC</h3>
                    <a href="{{ route('cashier.pcbc.index', ['page' => 'list']) }}"
                        class="btn btn-sm btn-secondary float-right"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('cashier.pcbc.store') }}" method="POST" id="pcbcForm">
                        @csrf

                        <!-- Basic Information Card -->
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-info-circle"></i> Basic Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="nomor">Document Number</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                </div>
                                                <input type="text" class="form-control @error('nomor') is-invalid @enderror"
                                                    id="nomor" name="nomor" value="auto generate" readonly
                                                    style="background-color: #e9ecef;">
                                            </div>
                                            <small class="form-text text-muted">Auto-generated on save</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="pcbc_date">PCBC Date <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                </div>
                                                <input type="date" class="form-control @error('pcbc_date') is-invalid @enderror"
                                                    id="pcbc_date" name="pcbc_date" value="{{ old('pcbc_date', date('Y-m-d')) }}"
                                                    required>
                                            </div>
                                            @error('pcbc_date')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="project">Project</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                                </div>
                                                <input type="text" class="form-control @error('project') is-invalid @enderror"
                                                    id="project" name="project" value="{{ old('project', auth()->user()->project) }}"
                                                    readonly style="background-color: #e9ecef;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @include('cashier.pcbc.create.kertas')

                        @include('cashier.pcbc.create.coin')

                        <!-- Amount Summary Card -->
                        <div class="card card-outline card-success mt-4">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-calculator"></i> Amount Summary</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-server"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">System Amount</span>
                                                <span class="info-box-number" id="system-amount-display">Rp 0,00</span>
                                                <small class="text-muted">From accounting system</small>
                                            </div>
                                        </div>
                                        <div class="form-group mt-2">
                                            <label for="system_amount">Enter System Amount 
                                                <i class="fas fa-info-circle" data-toggle="tooltip" 
                                                   title="Enter amount from accounting system. Use comma (,) as decimal separator."></i>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="text" class="form-control text-center" id="system_amount"
                                                    name="system_amount" value="{{ old('system_amount') }}"
                                                    placeholder="0,00"
                                                    oninput="formatAmountInput(this); updateAmountSummary();">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-money-bill-wave"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Physical Amount</span>
                                                <span class="info-box-number text-success" id="fisik-amount-display">Rp 0</span>
                                                <small class="text-muted">Auto-calculated from denominations</small>
                                            </div>
                                        </div>
                                        <div class="form-group mt-2">
                                            <label for="fisik_amount">Physical Amount (Read-only)</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="text" class="form-control text-center bg-light" id="fisik_amount"
                                                    name="fisik_amount" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-sap"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">SAP Amount</span>
                                                <span class="info-box-number" id="sap-amount-display">Rp 0,00</span>
                                                <small class="text-muted">From SAP system</small>
                                            </div>
                                        </div>
                                        <div class="form-group mt-2">
                                            <label for="sap_amount">Enter SAP Amount 
                                                <i class="fas fa-info-circle" data-toggle="tooltip" 
                                                   title="Enter amount from SAP system. Use comma (,) as decimal separator."></i>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="text" class="form-control text-center" id="sap_amount" name="sap_amount"
                                                    value="{{ old('sap_amount') }}"
                                                    placeholder="0,00"
                                                    oninput="formatAmountInput(this); updateAmountSummary();">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Variance Alert -->
                                <div class="alert alert-warning mt-3" id="variance-alert" style="display:none;">
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> Variance Detected</h5>
                                    <div id="variance-details"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Approval Section -->
                        <div class="card card-outline card-warning mt-4">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-check"></i> Approval Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="cashier">Cashier</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                </div>
                                                <input type="text" class="form-control bg-light" id="cashier" name="cashier"
                                                    value="{{ auth()->user()->name }}" readonly>
                                            </div>
                                            <small class="form-text text-muted">Current logged-in user</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="pemeriksa1">Pemeriksa (Checker) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-user-check"></i></span>
                                                </div>
                                                <input type="text" class="form-control @error('pemeriksa1') is-invalid @enderror"
                                                    id="pemeriksa1" name="pemeriksa1" value="{{ old('pemeriksa1') }}" 
                                                    placeholder="Enter checker name" required>
                                            </div>
                                            @error('pemeriksa1')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Person who checked the cash count</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="approved_by">Approved By <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                                                </div>
                                                <input type="text" class="form-control @error('approved_by') is-invalid @enderror"
                                                    value="{{ old('approved_by') }}" id="approved_by" name="approved_by" 
                                                    placeholder="Enter approver name" required>
                                            </div>
                                            @error('approved_by')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Person who approved this PCBC</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                            <i class="fas fa-save"></i> Create PCBC
                                        </button>
                                        <a href="{{ route('cashier.pcbc.index', ['page' => 'list']) }}"
                                            class="btn btn-secondary btn-lg">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="button" class="btn btn-info btn-lg" onclick="resetForm()">
                                            <i class="fas fa-redo"></i> Reset Form
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .money-result {
            background-color: #e9ecef;
            color: #495057;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .col-form-label {
            font-weight: bold;
            text-align: right;
            font-size: 0.9rem;
        }

        .form-group.row {
            margin-bottom: 0.5rem;
        }

        .info-box {
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            border-radius: 0.25rem;
            background-color: #fff;
            display: -ms-flexbox;
            display: flex;
            margin-bottom: 1rem;
            min-height: 80px;
            padding: 0.5rem;
            position: relative;
        }

        .info-box-icon {
            border-radius: 0.25rem;
            -ms-flex-align: center;
            align-items: center;
            display: -ms-flexbox;
            display: flex;
            font-size: 1.875rem;
            -ms-flex-pack: center;
            justify-content: center;
            text-align: center;
            width: 70px;
        }

        .info-box-content {
            -ms-flex: 1;
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

        .money-input {
            font-size: 1rem;
            font-weight: 500;
        }

        .card-outline {
            border-top: 3px solid;
        }

        .card-outline.card-primary {
            border-top-color: #007bff;
        }

        .card-outline.card-info {
            border-top-color: #17a2b8;
        }

        .card-outline.card-success {
            border-top-color: #28a745;
        }

        .card-outline.card-warning {
            border-top-color: #ffc107;
        }

        @media (max-width: 768px) {
            .info-box {
                margin-bottom: 0.5rem;
            }
            
            .col-md-4 {
                margin-bottom: 1rem;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Initialize all money inputs with value 0
            $('.money-input').val(0);

            // Calculate all results on page load
            calculateAllResults();
            updateAmountSummary();

            // Add event listeners to all money inputs
            $('.money-input').on('input', function() {
                calculateAllResults();
                updateAmountSummary();
            });

            // Form submission handler
            $('#pcbcForm').on('submit', function(e) {
                const btn = $('#submitBtn');
                btn.prop('disabled', true);
                btn.html('<i class="fas fa-spinner fa-spin"></i> Creating...');
            });

            // Auto-save to localStorage
            autoSaveForm();
            restoreFormData();
        });

        function calculateAllResults() {
            let totalAmount = 0;
            let kertasSubtotal = 0;
            let logamSubtotal = 0;

            // Paper money calculations
            kertasSubtotal += calculateDenomination('kertas_100rb', 100000);
            kertasSubtotal += calculateDenomination('kertas_50rb', 50000);
            kertasSubtotal += calculateDenomination('kertas_20rb', 20000);
            kertasSubtotal += calculateDenomination('kertas_10rb', 10000);
            kertasSubtotal += calculateDenomination('kertas_5rb', 5000);
            kertasSubtotal += calculateDenomination('kertas_2rb', 2000);
            kertasSubtotal += calculateDenomination('kertas_1rb', 1000);
            kertasSubtotal += calculateDenomination('kertas_500', 500);
            kertasSubtotal += calculateDenomination('kertas_100', 100);

            // Coin money calculations
            logamSubtotal += calculateDenomination('logam_1rb', 1000);
            logamSubtotal += calculateDenomination('logam_500', 500);
            logamSubtotal += calculateDenomination('logam_200', 200);
            logamSubtotal += calculateDenomination('logam_100', 100);
            logamSubtotal += calculateDenomination('logam_50', 50);
            logamSubtotal += calculateDenomination('logam_25', 25);

            totalAmount = kertasSubtotal + logamSubtotal;

            // Update subtotals
            $('#kertas-subtotal').text('Rp ' + formatNumber(kertasSubtotal));
            $('#logam-subtotal').text('Rp ' + formatNumber(logamSubtotal));

            // Update total physical amount
            const formattedAmount = formatNumber(totalAmount);
            $('#fisik_amount').val(formattedAmount);
            $('#fisik-amount-display').text('Rp ' + formattedAmount);
        }

        function calculateDenomination(fieldId, multiplier) {
            const value = parseInt($('#' + fieldId).val()) || 0;
            const result = value * multiplier;
            $('#' + fieldId + '_result').val(formatNumber(result));
            return result;
        }

        function formatNumber(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        }

        function formatAmountInput(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            if (value.length > 0) {
                value = parseInt(value);
                input.value = new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(value / 100);
            } else {
                input.value = '';
            }
        }

        function parseAmount(value) {
            if (!value) return 0;
            return parseFloat(value.replace(/\./g, '').replace(',', '.')) || 0;
        }

        function updateAmountSummary() {
            const systemAmount = parseAmount($('#system_amount').val());
            const fisikAmount = parseAmount($('#fisik_amount').val());
            const sapAmount = parseAmount($('#sap_amount').val());

            // Update displays
            $('#system-amount-display').text('Rp ' + (systemAmount > 0 ? formatNumber(systemAmount) : '0,00'));
            $('#sap-amount-display').text('Rp ' + (sapAmount > 0 ? formatNumber(sapAmount) : '0,00'));

            // Calculate variances
            const systemVariance = systemAmount - fisikAmount;
            const sapVariance = sapAmount - fisikAmount;
            const hasVariance = Math.abs(systemVariance) > 0.01 || Math.abs(sapVariance) > 0.01;

            // Show/hide variance alert
            if (hasVariance && (systemAmount > 0 || sapAmount > 0)) {
                let varianceText = '<ul class="mb-0">';
                if (systemAmount > 0) {
                    const varianceClass = Math.abs(systemVariance) > 1000 ? 'text-danger' : 'text-warning';
                    varianceText += `<li class="${varianceClass}">System Variance: Rp ${formatNumber(Math.abs(systemVariance))} 
                        (${systemVariance > 0 ? 'over' : 'under'})</li>`;
                }
                if (sapAmount > 0) {
                    const varianceClass = Math.abs(sapVariance) > 1000 ? 'text-danger' : 'text-warning';
                    varianceText += `<li class="${varianceClass}">SAP Variance: Rp ${formatNumber(Math.abs(sapVariance))} 
                        (${sapVariance > 0 ? 'over' : 'under'})</li>`;
                }
                varianceText += '</ul>';
                $('#variance-details').html(varianceText);
                $('#variance-alert').show();
            } else {
                $('#variance-alert').hide();
            }
        }

        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
                $('#pcbcForm')[0].reset();
                $('.money-input').val(0);
                calculateAllResults();
                updateAmountSummary();
                localStorage.removeItem('pcbc_form_data');
            }
        }

        function autoSaveForm() {
            $('#pcbcForm input, #pcbcForm select').on('change', function() {
                const formData = {};
                $('#pcbcForm').serializeArray().forEach(function(field) {
                    formData[field.name] = field.value;
                });
                localStorage.setItem('pcbc_form_data', JSON.stringify(formData));
            });
        }

        function restoreFormData() {
            const savedData = localStorage.getItem('pcbc_form_data');
            if (savedData && !$('#pcbc_date').val()) {
                try {
                    const formData = JSON.parse(savedData);
                    Object.keys(formData).forEach(function(key) {
                        const field = $('[name="' + key + '"]');
                        if (field.length && !field.prop('readonly')) {
                            field.val(formData[key]);
                        }
                    });
                    calculateAllResults();
                    updateAmountSummary();
                } catch (e) {
                    console.error('Error restoring form data:', e);
                }
            }
        }

        // Clear saved data on successful submission
        $(document).on('submit', '#pcbcForm', function() {
            localStorage.removeItem('pcbc_form_data');
        });

        function clearSection(type) {
            if (confirm(`Clear all ${type === 'kertas' ? 'paper money' : 'coin'} denominations?`)) {
                const prefixes = type === 'kertas' 
                    ? ['kertas_100rb', 'kertas_50rb', 'kertas_20rb', 'kertas_10rb', 'kertas_5rb', 'kertas_2rb', 'kertas_1rb', 'kertas_500', 'kertas_100']
                    : ['logam_1rb', 'logam_500', 'logam_200', 'logam_100', 'logam_50', 'logam_25'];
                
                prefixes.forEach(function(prefix) {
                    $('#' + prefix).val(0);
                    $('#' + prefix + '_result').val('');
                });
                
                calculateAllResults();
                updateAmountSummary();
            }
        }
    </script>
@endsection
