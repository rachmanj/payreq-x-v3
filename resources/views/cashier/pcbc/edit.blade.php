@extends('templates.main')

@section('title_page')
    Edit PCBC
@endsection

@section('breadcrumb_title')
    cashier / pcbc / edit
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit PCBC</h3>
                    <a href="{{ route('cashier.pcbc.index', ['page' => 'list']) }}"
                        class="btn btn-sm btn-secondary float-right">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('cashier.pcbc.update_pcbc', $pcbc->id) }}" method="POST" id="pcbcForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nomor">Nomor</label>
                                    <input type="text" class="form-control" id="nomor" name="nomor"
                                        value="{{ $pcbc->nomor }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pcbc_date">PCBC Date</label>
                                    <input type="date" class="form-control @error('pcbc_date') is-invalid @enderror"
                                        id="pcbc_date" name="pcbc_date" value="{{ old('pcbc_date', $pcbc->pcbc_date) }}"
                                        required>
                                    @error('pcbc_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="project">Project</label>
                                    <input type="text" class="form-control" id="project" name="project"
                                        value="{{ $pcbc->project }}" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Paper Money Section -->
                        @include('cashier.pcbc.edit.kertas')

                        <!-- Coin Money Section -->
                        @include('cashier.pcbc.edit.coin')

                        <!-- Total Amounts Section -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="system_amount">System Amount <i class="fas fa-info-circle"
                                            title="use comma as 2 digit decimal separator"></i></label>
                                    <input type="text" class="form-control text-center" id="system_amount"
                                        name="system_amount"
                                        value="{{ old('system_amount', number_format($pcbc->system_amount, 2, ',', '.')) }}"
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
                                        name="fisik_amount" value="{{ $pcbc->fisik_amount }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sap_amount">SAP Amount <i class="fas fa-info-circle"
                                            title="use comma as 2 digit decimal separator"></i></label>
                                    <input type="text" class="form-control text-center" id="sap_amount" name="sap_amount"
                                        value="{{ old('sap_amount', number_format($pcbc->sap_amount, 2, ',', '.')) }}"
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
                                    <label for="created_by">Created By</label>
                                    <input type="text" class="form-control" id="created_by" name="created_by"
                                        value="{{ old('created_by', $pcbc->createdBy->name ?? '') }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pemeriksa2">Pemeriksa</label>
                                    <input type="text" class="form-control" id="pemeriksa1" name="pemeriksa1"
                                        value="{{ old('pemeriksa1', $pcbc->pemeriksa1) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="approved_by">Approved By</label>
                                    <input type="text" class="form-control" id="approved_by" name="approved_by"
                                        value="{{ old('approved_by', $pcbc->approved_by) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm">Update PCBC</button>
                                <a href="{{ route('cashier.pcbc.index', ['page' => 'list']) }}"
                                    class="btn btn-secondary btn-sm">Cancel</a>
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
            // Initialize all money inputs
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
