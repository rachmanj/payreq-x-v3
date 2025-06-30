@extends('templates.main')

@section('title_page')
    Exchange Rates
@endsection

@section('breadcrumb_title')
    Exchange Rates
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Edit Exchange Rate</h3>
                    <a href="{{ route('accounting.exchange-rates.index') }}"
                        class="btn btn-sm btn-secondary float-right text-white">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <form action="{{ route('accounting.exchange-rates.update', $exchangeRate->id) }}" method="POST"
                    id="exchangeRateForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="hidden" name="currency_to" id="currency_to" value="IDR">
                                    <label for="currency_from">Foreign Currency <span class="text-danger">*</span></label>
                                    <select name="currency_from" id="currency_from"
                                        class="form-control @error('currency_from') is-invalid @enderror" required>
                                        <option value="">Select Currency From</option>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->currency_code }}"
                                                {{ old('currency_from', $exchangeRate->currency_from) == $currency->currency_code ? 'selected' : '' }}>
                                                {{ $currency->currency_code }} - {{ $currency->currency_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('currency_from')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="exchange_rate">Exchange Rate to IDR <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="exchange_rate" id="exchange_rate"
                                        class="form-control @error('exchange_rate') is-invalid @enderror"
                                        value="{{ old('exchange_rate', number_format($exchangeRate->exchange_rate, 6)) }}"
                                        placeholder="Enter exchange rate (e.g., 15,750.00)" onkeyup="formatNumber(this)"
                                        required>
                                    @error('exchange_rate')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="text-muted">
                                        Example: If 1 USD = 15,750 IDR, enter 15,750.00
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="effective_date">Effective Date <span class="text-danger">*</span></label>
                                    <input type="date" name="effective_date" id="effective_date"
                                        class="form-control @error('effective_date') is-invalid @enderror"
                                        value="{{ old('effective_date', $exchangeRate->effective_date->format('Y-m-d')) }}"
                                        required>
                                    @error('effective_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Created By</label>
                                    <input type="text" class="form-control"
                                        value="{{ $exchangeRate->creator->name ?? 'N/A' }}" readonly>
                                    <small class="text-muted">Original creator of this record.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Created At</label>
                                    <input type="text" class="form-control"
                                        value="{{ $exchangeRate->created_at->format('Y-m-d H:i:s') }}" readonly>
                                    <small class="text-muted">When this record was created.</small>
                                </div>
                            </div>
                        </div>

                        @if ($exchangeRate->updated_at && $exchangeRate->updated_at != $exchangeRate->created_at)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Last Updated By</label>
                                        <input type="text" class="form-control"
                                            value="{{ $exchangeRate->updater->name ?? 'N/A' }}" readonly>
                                        <small class="text-muted">Last person who updated this record.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Last Updated At</label>
                                        <input type="text" class="form-control"
                                            value="{{ $exchangeRate->updated_at->format('Y-m-d H:i:s') }}" readonly>
                                        <small class="text-muted">When this record was last updated.</small>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Note:</strong> Updating this exchange rate will change the rate for the specific date
                            only.
                            If you need to update multiple dates, please use the bulk update feature from the list page.
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-6">
                                <a href="{{ route('accounting.exchange-rates.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <a href="{{ route('accounting.exchange-rates.show', $exchangeRate->id) }}"
                                    class="btn btn-info">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                            <div class="col-6">
                                <button type="submit" class="btn btn-warning btn-block">
                                    <i class="fas fa-save"></i> Update Exchange Rate
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
    <style>
        .form-group label .text-danger {
            font-weight: bold;
        }

        .card-warning .card-header {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: #212529;
        }

        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }

        .card-footer {
            background-color: rgba(0, 0, 0, 0.03);
            border-top: 1px solid rgba(0, 0, 0, 0.125);
        }

        .is-invalid {
            border-color: #dc3545;
        }

        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875em;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .table-borderless td {
            border: none;
            padding: 0.25rem 0.75rem;
        }

        .table-borderless td:first-child {
            padding-left: 0;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(function() {
            // Form validation
            $('#exchangeRateForm').submit(function(e) {
                e.preventDefault();

                // Reset previous validation
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                let isValid = true;

                // Validate Currency From
                if (!$('#currency_from').val()) {
                    $('#currency_from').addClass('is-invalid');
                    $('#currency_from').after(
                        '<div class="invalid-feedback">Currency From is required.</div>');
                    isValid = false;
                }

                // Check if currencies are different (currency_to is always IDR)
                if ($('#currency_from').val() && $('#currency_from').val() === 'IDR') {
                    $('#currency_from').addClass('is-invalid');
                    $('#currency_from').after(
                        '<div class="invalid-feedback">Currency From cannot be IDR as it is already the base currency.</div>'
                    );
                    isValid = false;
                }

                // Validate Exchange Rate
                const exchangeRate = parseNumber($('#exchange_rate').val());
                if (!$('#exchange_rate').val() || exchangeRate <= 0) {
                    $('#exchange_rate').addClass('is-invalid');
                    $('#exchange_rate').after(
                        '<div class="invalid-feedback">Please enter a valid exchange rate greater than 0.</div>'
                    );
                    isValid = false;
                }

                // Validate Effective Date
                if (!$('#effective_date').val()) {
                    $('#effective_date').addClass('is-invalid');
                    $('#effective_date').after(
                        '<div class="invalid-feedback">Effective Date is required.</div>');
                    isValid = false;
                }

                if (isValid) {
                    // Show confirmation dialog
                    const currencyFromCode = $('#currency_from').val();
                    const currencyToCode = 'IDR';
                    const currencyFromName = $('#currency_from option:selected').text().split(' - ')[1];
                    const currencyToName = 'Indonesian Rupiah';
                    const rate = $('#exchange_rate').val();
                    const effectiveDate = $('#effective_date').val();

                    const confirmMessage = `Are you sure you want to update this exchange rate?\n\n` +
                        `Currency: \n` +
                        `From: ${currencyFromCode} - ${currencyFromName}\n` +
                        `To: ${currencyToCode} - ${currencyToName}\n` +
                        `New Rate: ${rate}\n` +
                        `Effective Date: ${effectiveDate}`;

                    if (confirm(confirmMessage)) {
                        // Convert formatted exchange rate to numeric value before submit
                        const numericRate = parseNumber($('#exchange_rate').val());
                        $('#exchange_rate').val(numericRate);
                        this.submit();
                    }
                }
            });

            // Currency change validation
            $('#currency_from').change(function() {
                if ($(this).val() === 'IDR') {
                    $(this).addClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                    $(this).after(
                        '<div class="invalid-feedback">Currency From cannot be IDR as it is already the base currency.</div>'
                    );
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                }
            });

            // Exchange rate validation
            $('#exchange_rate').on('input', function() {
                const value = parseNumber($(this).val());

                if ($(this).val() && (isNaN(value) || value <= 0)) {
                    $(this).addClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                    $(this).after(
                        '<div class="invalid-feedback">Please enter a valid positive number.</div>');
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                }
            });

            // Effective date validation
            $('#effective_date').change(function() {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                    $(this).after('<div class="invalid-feedback">Effective Date is required.</div>');
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                }
            });
        });

        // Format number function for currency inputs
        function formatNumber(input) {
            // Store cursor position
            const cursorPosition = input.selectionStart;
            const originalLength = input.value.length;

            // Remove any non-digit characters except dots
            let value = input.value.replace(/[^\d.]/g, '');

            // Ensure only one decimal point
            let parts = value.split('.');
            if (parts.length > 2) {
                parts = [parts[0], parts.slice(1).join('')];
            }

            // Limit decimal places to 6
            if (parts[1] && parts[1].length > 6) {
                parts[1] = parts[1].substring(0, 6);
            }

            // Add thousand separators to integer part
            if (parts[0]) {
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            // Join with decimal part if exists
            const formattedValue = parts.length > 1 ? parts.join('.') : parts[0];
            input.value = formattedValue;

            // Restore cursor position
            const newLength = input.value.length;
            const lengthDiff = newLength - originalLength;
            const newCursorPosition = cursorPosition + lengthDiff;
            input.setSelectionRange(newCursorPosition, newCursorPosition);
        }

        // Function to parse number from formatted string
        function parseNumber(value) {
            if (!value || value === '') return 0;
            // Remove commas and convert to float
            const cleanValue = value.toString().replace(/,/g, '');
            const parsed = parseFloat(cleanValue);
            return isNaN(parsed) ? 0 : parsed;
        }
    </script>
@endsection
