@extends('templates.main')

@section('title_page')
    Currency Details
@endsection

@section('breadcrumb_title')
    Currency Details
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Header Section -->
            <div class="card bg-white shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">{{ $currency->currency_code }} - {{ $currency->currency_name }}</h4>
                            <p class="text-muted mb-0">
                                <i
                                    class="fas fa-{{ $currency->is_active ? 'check-circle text-success' : 'times-circle text-secondary' }} mr-1"></i>
                                {{ $currency->is_active ? 'Active' : 'Inactive' }} Currency
                                @if ($currency->symbol)
                                    | Symbol: {{ $currency->symbol }}
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-{{ $currency->is_active ? 'success' : 'secondary' }} badge-lg">
                                {{ $currency->is_active ? 'ACTIVE' : 'INACTIVE' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Section -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Currency Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">Currency Code:</dt>
                                <dd class="col-sm-9">
                                    <span class="badge badge-primary">{{ $currency->currency_code }}</span>
                                </dd>

                                <dt class="col-sm-3">Currency Name:</dt>
                                <dd class="col-sm-9">{{ $currency->currency_name }}</dd>

                                <dt class="col-sm-3">Symbol:</dt>
                                <dd class="col-sm-9">
                                    @if ($currency->symbol)
                                        <span class="badge badge-info">{{ $currency->symbol }}</span>
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-3">Status:</dt>
                                <dd class="col-sm-9">
                                    @if ($currency->is_active)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Active
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-times"></i> Inactive
                                        </span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>

                    <!-- Exchange Rates Usage -->
                    @if ($currency->exchangeRatesFrom->count() > 0 || $currency->exchangeRatesTo->count() > 0)
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-exchange-alt"></i> Exchange Rates Usage
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><strong>From {{ $currency->currency_code }}:</strong></h6>
                                        @if ($currency->exchangeRatesFrom->count() > 0)
                                            <ul class="list-unstyled">
                                                @foreach ($currency->exchangeRatesFrom->take(5) as $rate)
                                                    <li>
                                                        <i class="fas fa-arrow-right text-primary"></i>
                                                        {{ $currency->currency_code }} → {{ $rate->currency_to }}
                                                        <small
                                                            class="text-muted">({{ $rate->effective_date->format('d M Y') }})</small>
                                                    </li>
                                                @endforeach
                                                @if ($currency->exchangeRatesFrom->count() > 5)
                                                    <li><small class="text-muted">... and
                                                            {{ $currency->exchangeRatesFrom->count() - 5 }} more</small>
                                                    </li>
                                                @endif
                                            </ul>
                                        @else
                                            <p class="text-muted">No exchange rates found</p>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <h6><strong>To {{ $currency->currency_code }}:</strong></h6>
                                        @if ($currency->exchangeRatesTo->count() > 0)
                                            <ul class="list-unstyled">
                                                @foreach ($currency->exchangeRatesTo->take(5) as $rate)
                                                    <li>
                                                        <i class="fas fa-arrow-left text-success"></i>
                                                        {{ $rate->currency_from }} → {{ $currency->currency_code }}
                                                        <small
                                                            class="text-muted">({{ $rate->effective_date->format('d M Y') }})</small>
                                                    </li>
                                                @endforeach
                                                @if ($currency->exchangeRatesTo->count() > 5)
                                                    <li><small class="text-muted">... and
                                                            {{ $currency->exchangeRatesTo->count() - 5 }} more</small></li>
                                                @endif
                                            </ul>
                                        @else
                                            <p class="text-muted">No exchange rates found</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Audit Trail Section -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history"></i> Audit Trail
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="time-label">
                                    <span class="bg-success">Creation</span>
                                </div>
                                <div>
                                    <i class="fas fa-plus bg-success"></i>
                                    <div class="timeline-item">
                                        <h3 class="timeline-header">
                                            <strong>Created</strong>
                                        </h3>
                                        <div class="timeline-body">
                                            <strong>By:</strong>
                                            {{ $currency->creator ? $currency->creator->name : 'System' }}<br>
                                            <strong>Date:</strong>
                                            {{ $currency->created_at ? $currency->created_at->format('d M Y H:i:s') : '-' }}
                                        </div>
                                    </div>
                                </div>

                                @if ($currency->updated_at && $currency->updated_at != $currency->created_at)
                                    <div class="time-label">
                                        <span class="bg-warning">Last Update</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-edit bg-warning"></i>
                                        <div class="timeline-item">
                                            <h3 class="timeline-header">
                                                <strong>Last Modified</strong>
                                            </h3>
                                            <div class="timeline-body">
                                                <strong>By:</strong>
                                                {{ $currency->updater ? $currency->updater->name : 'System' }}<br>
                                                <strong>Date:</strong> {{ $currency->updated_at->format('d M Y H:i:s') }}
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div>
                                    <i class="fas fa-clock bg-gray"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Card -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar"></i> Statistics
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info">
                                            <i class="fas fa-exchange-alt"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Exchange Rates</span>
                                            <span class="info-box-number">
                                                {{ $currency->exchangeRatesFrom->count() + $currency->exchangeRatesTo->count() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-footer">
                            <a href="{{ route('currencies.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <a href="{{ route('currencies.edit', $currency->id) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <div class="btn-group float-right">
                                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                    <i class="fas fa-cog"></i> Actions
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('currencies.create') }}">
                                        <i class="fas fa-plus"></i> Add New Currency
                                    </a>
                                    @if (Route::has('accounting.exchange-rates.index'))
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item"
                                            href="{{ route('accounting.exchange-rates.index') }}?currency_from={{ $currency->currency_code }}">
                                            <i class="fas fa-exchange-alt"></i> View Exchange Rates From
                                            {{ $currency->currency_code }}
                                        </a>
                                        <a class="dropdown-item"
                                            href="{{ route('accounting.exchange-rates.index') }}?currency_to={{ $currency->currency_code }}">
                                            <i class="fas fa-exchange-alt"></i> View Exchange Rates To
                                            {{ $currency->currency_code }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .badge-lg {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .timeline {
            position: relative;
            margin: 0 0 30px 0;
            padding: 0;
            list-style: none;
        }

        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 31px;
            width: 4px;
            background: #dee2e6;
        }

        .timeline>div {
            margin-bottom: 15px;
            position: relative;
        }

        .timeline>div>.timeline-item {
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
            border-radius: 3px;
            margin-top: 0;
            background: #fff;
            margin-left: 60px;
            padding: 10px;
            border: 1px solid #dee2e6;
        }

        .timeline>div>.fas {
            width: 30px;
            height: 30px;
            font-size: 15px;
            line-height: 30px;
            position: absolute;
            color: #666;
            background: #dee2e6;
            border-radius: 50%;
            text-align: center;
            left: 18px;
            top: 0;
        }

        .timeline>.time-label>span {
            font-weight: 600;
            color: #fff;
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .timeline-header {
            margin-top: 0;
            color: #555;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .timeline-body {
            color: #777;
            font-size: 14px;
        }
    </style>
@endsection
