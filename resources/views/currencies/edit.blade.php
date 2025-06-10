@extends('templates.main')

@section('title_page')
    Edit Currency
@endsection

@section('breadcrumb_title')
    Edit Currency
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Currency: {{ $currency->currency_code }}</h3>
                </div>

                <form action="{{ route('currencies.update', $currency->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="currency_code">Currency Code <span class="text-danger">*</span></label>
                                    <input type="text" name="currency_code" id="currency_code"
                                        class="form-control @error('currency_code') is-invalid @enderror"
                                        value="{{ old('currency_code', $currency->currency_code) }}" maxlength="3"
                                        placeholder="e.g., USD, EUR, IDR" style="text-transform: uppercase;">
                                    @error('currency_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Enter 3-character currency code (will be
                                        automatically converted to uppercase)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="currency_name">Currency Name <span class="text-danger">*</span></label>
                                    <input type="text" name="currency_name" id="currency_name"
                                        class="form-control @error('currency_name') is-invalid @enderror"
                                        value="{{ old('currency_name', $currency->currency_name) }}" maxlength="100"
                                        placeholder="e.g., US Dollar, Euro, Indonesian Rupiah">
                                    @error('currency_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="symbol">Currency Symbol</label>
                                    <input type="text" name="symbol" id="symbol"
                                        class="form-control @error('symbol') is-invalid @enderror"
                                        value="{{ old('symbol', $currency->symbol) }}" maxlength="10"
                                        placeholder="e.g., $, €, Rp">
                                    @error('symbol')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Optional currency symbol</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active">Status</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                            value="1" {{ old('is_active', $currency->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Check to make this currency active</small>
                                </div>
                            </div>
                        </div>

                        <!-- Audit Trail Information -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-info-circle"></i> Audit Trail
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Created By:</strong>
                                                {{ $currency->creator ? $currency->creator->name : 'System' }}<br>
                                                <strong>Created At:</strong>
                                                {{ $currency->created_at ? $currency->created_at->format('d M Y H:i:s') : '-' }}
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Last Updated By:</strong>
                                                {{ $currency->updater ? $currency->updater->name : 'Never Updated' }}<br>
                                                <strong>Updated At:</strong>
                                                {{ $currency->updated_at && $currency->updated_at != $currency->created_at ? $currency->updated_at->format('d M Y H:i:s') : 'Never Updated' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-4">
                                <a href="{{ route('currencies.index') }}" class="btn btn-secondary btn-block">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="{{ route('currencies.show', $currency->id) }}" class="btn btn-info btn-block">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> Update Currency
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Auto-uppercase currency code
            $('#currency_code').on('input', function() {
                this.value = this.value.toUpperCase();
            });

            // Form validation
            $('form').on('submit', function(e) {
                let isValid = true;
                let firstError = null;

                // Validate currency code
                const currencyCode = $('#currency_code').val().trim();
                if (!currencyCode) {
                    showError('#currency_code', 'Currency code is required');
                    isValid = false;
                } else if (currencyCode.length !== 3) {
                    showError('#currency_code', 'Currency code must be exactly 3 characters');
                    isValid = false;
                } else {
                    clearError('#currency_code');
                }

                // Validate currency name
                const currencyName = $('#currency_name').val().trim();
                if (!currencyName) {
                    showError('#currency_name', 'Currency name is required');
                    isValid = false;
                } else {
                    clearError('#currency_name');
                }

                if (!isValid) {
                    e.preventDefault();
                    if (firstError) {
                        firstError.focus();
                    }
                }
            });

            function showError(selector, message) {
                const field = $(selector);
                if (!firstError) firstError = field;

                field.addClass('is-invalid');
                field.siblings('.invalid-feedback').remove();
                field.after('<div class="invalid-feedback">' + message + '</div>');
            }

            function clearError(selector) {
                const field = $(selector);
                field.removeClass('is-invalid');
                field.siblings('.invalid-feedback').remove();
            }

            // Clear errors on input
            $('.form-control').on('input', function() {
                clearError('#' + this.id);
            });
        });
    </script>
@endsection
