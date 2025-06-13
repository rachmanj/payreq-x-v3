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
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Exchange Rate Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('accounting.exchange-rates.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <a href="{{ route('accounting.exchange-rates.edit', $exchangeRate->id) }}"
                            class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form action="{{ route('accounting.exchange-rates.destroy', $exchangeRate->id) }}" method="POST"
                            style="display: inline;" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-exchange-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Currency Pair</span>
                                    <span class="info-box-number">
                                        {{ $exchangeRate->currency_from }} → {{ $exchangeRate->currency_to }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Exchange Rate</span>
                                    <span
                                        class="info-box-number">{{ number_format($exchangeRate->exchange_rate, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-calendar"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Effective Date</span>
                                    <span
                                        class="info-box-number">{{ $exchangeRate->effective_date->format('l, F j, Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($relatedRates->count() > 0)
                        <hr>
                        <h5><i class="fas fa-history"></i> Related Exchange Rates (Same Currency Pair)</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Rate</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($relatedRates as $rate)
                                        <tr class="{{ $rate->id == $exchangeRate->id ? 'table-active' : '' }}">
                                            <td>
                                                {{ $rate->effective_date->format('Y-m-d') }}
                                                @if ($rate->id == $exchangeRate->id)
                                                    <span class="badge badge-primary">Current</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($rate->exchange_rate, 2) }}</td>
                                            <td>{{ $rate->creator->name ?? 'N/A' }}</td>
                                            <td>
                                                @if ($rate->id != $exchangeRate->id)
                                                    <a href="{{ route('accounting.exchange-rates.show', $rate->id) }}"
                                                        class="btn btn-info btn-xs" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    <a href="{{ route('accounting.exchange-rates.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    @can('edit_exchange_rates')
                        <a href="{{ route('accounting.exchange-rates.edit', $exchangeRate->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    @endcan
                    <div class="btn-group float-right">
                        <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-cog"></i> Actions
                        </button>
                        <div class="dropdown-menu">
                            @can('create_exchange_rates')
                                <a class="dropdown-item" href="{{ route('accounting.exchange-rates.create') }}">
                                    <i class="fas fa-plus"></i> Add New Rate
                                </a>
                            @endcan
                            @can('export_exchange_rates')
                                @can('create_exchange_rates')
                                    <div class="dropdown-divider"></div>
                                @endcan
                                <a class="dropdown-item"
                                    href="{{ route('accounting.exchange-rates.export') }}?currency_from={{ $exchangeRate->currency_from }}&currency_to={{ $exchangeRate->currency_to }}">
                                    <i class="fas fa-file-excel"></i> Export This Pair
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .card-info .card-header {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .info-box {
            display: flex;
            min-height: 90px;
            background: #fff;
            width: 100%;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
            border-radius: 0.25rem;
            margin-bottom: 15px;
        }

        .info-box .info-box-icon {
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 90px;
            text-align: center;
            font-size: 45px;
            background: rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }

        .info-box .info-box-icon.bg-info {
            background-color: #17a2b8 !important;
            color: #fff;
        }

        .info-box .info-box-icon.bg-success {
            background-color: #28a745 !important;
            color: #fff;
        }

        .info-box .info-box-icon.bg-warning {
            background-color: #ffc107 !important;
            color: #212529;
        }

        .info-box .info-box-content {
            padding: 15px 10px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .info-box .info-box-text {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 14px;
        }

        .info-box .info-box-number {
            display: block;
            font-weight: bold;
            font-size: 18px;
        }

        .callout {
            border-radius: 0.25rem;
            margin: 0 0 20px 0;
            padding: 15px 30px 15px 15px;
            border-left: 5px solid #eee;
        }

        .callout.callout-info {
            border-left-color: #17a2b8;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .callout h5 {
            margin-top: 0;
            font-weight: 600;
        }

        .callout p {
            margin-bottom: 0;
        }

        .table-borderless td {
            border: none;
            padding: 0.25rem 0.75rem;
        }

        .table-borderless td:first-child {
            padding-left: 0;
            font-weight: 600;
        }

        .table-active {
            background-color: rgba(0, 123, 255, 0.075);
        }

        .badge-primary {
            background-color: #007bff;
        }

        .btn-group .dropdown-menu {
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 0.25rem;
        }

        .dropdown-item {
            padding: 0.25rem 1rem;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .card-footer {
            background-color: rgba(0, 0, 0, 0.03);
            border-top: 1px solid rgba(0, 0, 0, 0.125);
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
    </style>
@endsection

@section('scripts')
    <script>
        $(function() {
            // Delete confirmation
            $('.delete-form').submit(function(e) {
                e.preventDefault();

                if (confirm(
                        'Are you sure you want to delete this exchange rate? This action cannot be undone.'
                    )) {
                    this.submit();
                }
            });

            // Dropdown toggle for mobile
            $('.dropdown-toggle').click(function(e) {
                e.preventDefault();
                $(this).next('.dropdown-menu').toggle();
            });

            // Close dropdown when clicking outside
            $(document).click(function(e) {
                if (!$(e.target).closest('.btn-group').length) {
                    $('.dropdown-menu').hide();
                }
            });

            // Copy exchange rate value to clipboard
            $('.info-box-number').click(function() {
                const text = $(this).text().replace(/,/g, '');

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function() {
                        showTemporaryMessage('Exchange rate copied to clipboard!');
                    });
                } else {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showTemporaryMessage('Exchange rate copied to clipboard!');
                }
            });

            // Add tooltips
            $('.info-box-number').attr('title', 'Click to copy exchange rate');
            $('.info-box-number').css('cursor', 'pointer');

            function showTemporaryMessage(message) {
                const alertHtml = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                        ${message}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                `;

                $('body').append(alertHtml);

                // Auto hide after 3 seconds
                setTimeout(() => {
                    $('.alert').fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        });
    </script>
@endsection
