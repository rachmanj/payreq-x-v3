@extends('templates.main')

@section('title_page')
    RAB Dashboard
@endsection

@section('breadcrumb_title')
    reports / anggaran / dashboard
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Summary</h3>
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-primary float-right mx-1"><i
                            class="fas fa-arrow-left"></i> Reports index</a>
                    <a href="{{ route('reports.anggaran.index') }}" class="btn btn-xs btn-secondary float-right mx-1">RAB
                        list</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Visible active rows</span>
                                    <span class="info-box-number">{{ number_format($stats['count_visible']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Approved budget lines</span>
                                    <span class="info-box-number">{{ number_format($stats['count_approved']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Avg. utilization (approved)</span>
                                    <span class="info-box-number">{{ number_format($stats['avg_utilization'], 2) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Total approved budget (IDR)</h3>
                                </div>
                                <div class="card-body">
                                    <p class="lead mb-0">Rp {{ number_format($stats['sum_budget_approved'], 2) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-outline card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">Total release to date (stored balance)</h3>
                                </div>
                                <div class="card-body">
                                    <p class="lead mb-0">Rp {{ number_format($stats['sum_balance_approved'], 2) }}</p>
                                    <small class="text-muted">Stored balances refresh via hourly sync and manual
                                        recalculation.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
