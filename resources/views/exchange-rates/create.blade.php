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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Exchange Rate</h3>
                    <a href="{{ route('accounting.exchange-rates.index') }}" class="btn btn-sm btn-primary float-right">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <form action="{{ route('accounting.exchange-rates.store') }}" method="POST" id="exchangeRateForm">
                    @csrf
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
                                                {{ old('currency_from') == $currency->currency_code ? 'selected' : '' }}>
                                                {{ $currency->currency_code }} - {{ $currency->currency_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('currency_from')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            {{-- <div class="col-md-6">
                                <div class="form-group">
                                    <label for="currency_to">Currency To <span class="text-danger">*</span></label>
                                    <select name="currency_to" id="currency_to"
                                        class="form-control @error('currency_to') is-invalid @enderror" required>
                                        <option value="">Select Currency To</option>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->currency_code }}"
                                                {{ old('currency_to') == $currency->currency_code ? 'selected' : '' }}>
                                                {{ $currency->currency_code }} - {{ $currency->currency_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('currency_to')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div> --}}
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="exchange_rate">Exchange Rate to IDR <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="exchange_rate" id="exchange_rate"
                                        class="form-control @error('exchange_rate') is-invalid @enderror"
                                        value="{{ old('exchange_rate') }}"
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_from">Date From <span class="text-danger">*</span></label>
                                    <input type="date" name="date_from" id="date_from"
                                        class="form-control @error('date_from') is-invalid @enderror"
                                        value="{{ old('date_from', date('Y-m-d')) }}" required>
                                    @error('date_from')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_to">Date To <span class="text-danger">*</span></label>
                                    <input type="date" name="date_to" id="date_to"
                                        class="form-control @error('date_to') is-invalid @enderror"
                                        value="{{ old('date_to', date('Y-m-d')) }}" required>
                                    @error('date_to')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info" id="recordsInfo" style="display: none;">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Note:</strong> This will create <span id="recordCount">0</span> records
                                    (one for each date in the range).
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Created By</label>
                                    <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                                    <small class="text-muted">This field is automatically filled with your name.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-6">
                                <a href="{{ route('accounting.exchange-rates.index') }}"
                                    class="btn btn-secondary btn-block">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                            <div class="col-6">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> Save Exchange Rate
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

        .card-primary .card-header {
            background-color: #007bff;
            border-color: #007bff;
        }

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
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
                const exchangeRateValue = $('#exchange_rate').val();
                const exchangeRate = parseNumber(exchangeRateValue);
                if (!exchangeRateValue || exchangeRate <= 0 || isNaN(exchangeRate)) {
                    $('#exchange_rate').addClass('is-invalid');
                    $('#exchange_rate').after(
                        '<div class="invalid-feedback">Please enter a valid exchange rate greater than 0.</div>'
                    );
                    isValid = false;
                }

                // Validate Date From
                if (!$('#date_from').val()) {
                    $('#date_from').addClass('is-invalid');
                    $('#date_from').after('<div class="invalid-feedback">Date From is required.</div>');
                    isValid = false;
                }

                // Validate Date To
                if (!$('#date_to').val()) {
                    $('#date_to').addClass('is-invalid');
                    $('#date_to').after('<div class="invalid-feedback">Date To is required.</div>');
                    isValid = false;
                }

                // Validate date range
                if ($('#date_from').val() && $('#date_to').val()) {
                    const dateFrom = new Date($('#date_from').val());
                    const dateTo = new Date($('#date_to').val());

                    if (dateFrom > dateTo) {
                        $('#date_to').addClass('is-invalid');
                        $('#date_to').after(
                            '<div class="invalid-feedback">Date To must be greater than or equal to Date From.</div>'
                        );
                        isValid = false;
                    }
                }

                if (isValid) {
                    // Show confirmation dialog
                    const recordCount = calculateRecordCount();
                    const currencyFromCode = $('#currency_from').val();
                    const currencyToCode = 'IDR';
                    const currencyFromName = $('#currency_from option:selected').text().split(' - ')[1];
                    const currencyToName = 'Indonesian Rupiah';
                    const rate = $('#exchange_rate').val();

                    const dateFrom = new Date($('#date_from').val()).toLocaleDateString('en-GB');
                    const dateTo = new Date($('#date_to').val()).toLocaleDateString('en-GB');
                    const confirmMessage =
                        `Are you sure you want to create ${recordCount} exchange rate record(s)?\n\n` +
                        `Currency: \n` +
                        `From: ${currencyFromCode} - ${currencyFromName}\n` +
                        `To: ${currencyToCode} - ${currencyToName}\n` +
                        `Rate: ${rate}\n` +
                        `Date Range: ${dateFrom} to ${dateTo}\n`;

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
                const inputValue = $(this).val();
                const numericValue = parseNumber(inputValue);

                if (inputValue && (isNaN(numericValue) || numericValue <= 0)) {
                    $(this).addClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                    $(this).after(
                        '<div class="invalid-feedback">Please enter a valid positive number.</div>');
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                }
            });

            // Date range change handler
            $('#date_from, #date_to').change(function() {
                updateRecordCount();
                validateDateRange();
            });

            function validateDateRange() {
                if ($('#date_from').val() && $('#date_to').val()) {
                    const dateFrom = new Date($('#date_from').val());
                    const dateTo = new Date($('#date_to').val());

                    if (dateFrom > dateTo) {
                        $('#date_to').addClass('is-invalid');
                        $('#date_to').siblings('.invalid-feedback').remove();
                        $('#date_to').after(
                            '<div class="invalid-feedback">Date To must be greater than or equal to Date From.</div>'
                        );
                    } else {
                        $('#date_to').removeClass('is-invalid');
                        $('#date_to').siblings('.invalid-feedback').remove();
                    }
                }
            }

            function updateRecordCount() {
                const recordCount = calculateRecordCount();
                if (recordCount > 0) {
                    $('#recordCount').text(recordCount);
                    $('#recordsInfo').show();
                } else {
                    $('#recordsInfo').hide();
                }
            }

            function calculateRecordCount() {
                if (!$('#date_from').val() || !$('#date_to').val()) {
                    return 0;
                }

                const dateFrom = new Date($('#date_from').val());
                const dateTo = new Date($('#date_to').val());

                if (dateFrom > dateTo) {
                    return 0;
                }

                const timeDiff = dateTo.getTime() - dateFrom.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;

                return daysDiff;
            }

            // Initialize record count on page load
            updateRecordCount();
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
