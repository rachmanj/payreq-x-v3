@extends('templates.main')

@section('title_page')
  Loan Dashboard
@endsection

@section('breadcrumb_title')
    reports / loan / dashboard
@endsection

@section('content')
    <div class="vj-show">
        <div class="row mb-3">
            <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h4 class="mb-0 text-muted">Loan Reports Dashboard</h4>
                <a href="{{ route('reports.index') }}" class="vj-action-item vj-action-back">
                    <i class="fas fa-arrow-left"></i> Back to Index
                </a>
            </div>
        </div>

        <div class="vj-stat-grid mb-3">
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Total Outstanding Amount</span>
                    <span class="vj-stat-value">IDR {{ $dashboard_data['outstanding_installment_amount'] }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-warning">
                <div class="vj-stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Outstanding This Month</span>
                    <span class="vj-stat-value">IDR {{ $dashboard_data['outstanding_installment_amount_this_month'] }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-success">
                <div class="vj-stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Paid This Month</span>
                    <span class="vj-stat-value">IDR {{ $dashboard_data['paid_this_month'] }}</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-building"></i> Loans by Creditor
                        </h3>
                    </div>

                    <div class="card-body">
                        <div id="accordion">
                            @foreach ($dashboard_data['outstanding_installment_amount_by_creditors_detail'] as $creditor)
                                <div class="card card-outline card-primary mb-2">
                                    <div class="card-header">
                                        <h4 class="card-title w-100 mb-0">
                                            <a class="d-block w-100 d-flex flex-wrap align-items-center justify-content-between gap-2"
                                                data-toggle="collapse" href="#collapse{{ $creditor->index }}">
                                                <span>{{ $creditor->index }}. {{ $creditor->creditor_name }}</span>
                                                <span class="vj-chip vj-chip-neutral">IDR
                                                    {{ number_format($creditor->total, 2) }}</span>
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="collapse{{ $creditor->index }}" class="collapse"
                                        data-parent="#accordion">
                                        <div class="card-body table-responsive p-0">
                                            <table class="table table-striped table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Agreement</th>
                                                        <th>Desc</th>
                                                        <th class="text-right">Installment Left</th>
                                                        <th class="text-right">Outstanding Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($creditor['installments'] as $index => $loan)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $loan->loan_code }}</td>
                                                            <td>{{ $loan->description }}</td>
                                                            <td class="text-right">{{ $loan->number_of_installments_left }}
                                                            </td>
                                                            <td class="text-right">IDR
                                                                {{ number_format($loan->total, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection
