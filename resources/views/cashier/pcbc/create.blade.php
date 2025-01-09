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

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New PCBC</h3>
                    <a href="{{ route('cashier.pcbc.index', ['page' => 'list']) }}"
                        class="btn btn-sm btn-secondary float-right"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('cashier.pcbc.store') }}" method="POST" id="pcbcForm">
                        @csrf

                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nomor">Nomor</label>
                                    <input type="text" class="form-control @error('nomor') is-invalid @enderror"
                                        id="nomor" name="nomor" value="auto generate" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pcbc_date">PCBC Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('pcbc_date') is-invalid @enderror"
                                        id="pcbc_date" name="pcbc_date" value="{{ old('pcbc_date', date('Y-m-d')) }}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="project">Project</label>
                                    <input type="text" class="form-control @error('project') is-invalid @enderror"
                                        id="project" name="project" value="{{ old('project', auth()->user()->project) }}"
                                        readonly>
                                </div>
                            </div>
                        </div>

                        @include('cashier.pcbc.create.kertas')

                        @include('cashier.pcbc.create.coin')

                        <!-- Total Amounts Section -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="system_amount">System Amount <i class="fas fa-info-circle"
                                            title="use comma as 2 digit decimal separator"></i></label>
                                    <input type="text" class="form-control text-center" id="system_amount"
                                        name="system_amount" value="{{ old('system_amount') }}"
                                        oninput="let value = this.value.replace(/[^0-9]/g, '');
                                        if (value.length > 0) {
                                            value = parseInt(value);
                                            this.value = new Intl.NumberFormat('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(value/100);
                                        } else {
                                            this.value = '';
                                        }">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fisik_amount">Physical Amount</label>
                                    <input type="text" class="form-control text-center" id="fisik_amount"
                                        name="fisik_amount" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sap_amount">SAP Amount <i class="fas fa-info-circle"
                                            title="use comma as 2 digit decimal separator"></i></label>
                                    <input type="text" class="form-control text-center" id="sap_amount" name="sap_amount"
                                        value="{{ old('sap_amount') }}"
                                        oninput="let value = this.value.replace(/[^0-9]/g, '');
                                                if (value.length > 0) {
                                                    value = parseInt(value);
                                                    this.value = new Intl.NumberFormat('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(value/100);
                                                } else {
                                                    this.value = '';
                                                }">
                                </div>
                            </div>
                        </div>

                        <!-- Approval Section -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cashier">Cashier</label>
                                    <input type="text" class="form-control" id="cashier" name="cashier"
                                        value="{{ auth()->user()->name }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pemeriksa1">Pemeriksa <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('pemeriksa1') is-invalid @enderror"
                                        id="pemeriksa1" name="pemeriksa1" value="{{ old('pemeriksa1') }}" required>
                                    @error('pemeriksa1')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="approved_by">Approved By <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('approved_by') is-invalid @enderror"
                                        value="{{ old('approved_by') }}" id="approved_by" name="approved_by" required>
                                    @error('approved_by')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-primary">Create PCBC</button>
                                <a href="{{ route('cashier.pcbc.index', ['page' => 'list']) }}"
                                    class="btn btn-sm btn-secondary">Cancel</a>
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
            /* font-weight: bold; */
            color: #495057;
            font-size: 0.8rem;
            /* Make font smaller */
        }

        .col-form-label {
            font-weight: bold;
            text-align: right;
            font-size: 0.9rem;
        }

        .form-group.row {
            margin-bottom: 0.5rem;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize all money inputs with value 0
            $('.money-input').val(0);

            // Calculate all results on page load
            calculateAllResults();

            // Add event listeners to all money inputs
            $('.money-input').on('input', function() {
                calculateAllResults();
            });
        });

        function calculateAllResults() {
            let totalAmount = 0;

            // Paper money calculations
            totalAmount += calculateDenomination('kertas_100rb', 100000);
            totalAmount += calculateDenomination('kertas_50rb', 50000);
            totalAmount += calculateDenomination('kertas_20rb', 20000);
            totalAmount += calculateDenomination('kertas_10rb', 10000);
            totalAmount += calculateDenomination('kertas_5rb', 5000);
            totalAmount += calculateDenomination('kertas_2rb', 2000);
            totalAmount += calculateDenomination('kertas_1rb', 1000);
            totalAmount += calculateDenomination('kertas_500', 500);
            totalAmount += calculateDenomination('kertas_100', 100);

            // Coin money calculations
            totalAmount += calculateDenomination('logam_1rb', 1000);
            totalAmount += calculateDenomination('logam_500', 500);
            totalAmount += calculateDenomination('logam_200', 200);
            totalAmount += calculateDenomination('logam_100', 100);
            totalAmount += calculateDenomination('logam_50', 50);
            totalAmount += calculateDenomination('logam_25', 25);

            // Update total physical amount
            $('#fisik_amount').val(formatNumber(totalAmount));
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
    </script>
@endsection
