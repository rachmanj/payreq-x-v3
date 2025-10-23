@extends('templates.main')

@section('title_page')
    Loan Dashboard
@endsection

@section('breadcrumb_title')
    accounting / loans / dashboard
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <x-loan-links page="dashboard" />
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $statistics['total_loans'] ?? 0 }}</h3>
                    <p>Total Active Loans</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <a href="{{ route('accounting.loans.index') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $statistics['installments_due_this_month'] ?? 0 }}</h3>
                    <p>Due This Month</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <a href="{{ route('reports.loan.index') }}" class="small-box-footer">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>IDR {{ number_format($statistics['total_outstanding'] ?? 0, 0) }}</h3>
                    <p>Total Outstanding</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <a href="{{ route('reports.loan.dashboard') }}" class="small-box-footer">
                    View Report <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $statistics['overdue_installments'] ?? 0 }}</h3>
                    <p>Overdue Installments</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="#" class="small-box-footer">
                    Take Action <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment Method Breakdown (This Month)</h3>
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodChart" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Installments (Next 7 Days)</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Due Date</th>
                                <th>Creditor</th>
                                <th>Loan</th>
                                <th class="text-right">Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcoming_installments ?? [] as $installment)
                                <tr>
                                    <td>{{ $installment->due_date ? \Carbon\Carbon::parse($installment->due_date)->format('d-M-Y') : '-' }}
                                    </td>
                                    <td>{{ $installment->loan->creditor->name ?? 'N/A' }}</td>
                                    <td>{{ $installment->loan->loan_code ?? 'N/A' }}</td>
                                    <td class="text-right">{{ number_format($installment->bilyet_amount, 0) }}</td>
                                    <td>
                                        <a href="{{ route('accounting.loans.show', $installment->loan_id) }}"
                                            class="btn btn-xs btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No upcoming installments in the next 7
                                        days</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Loans by Creditor</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Creditor</th>
                                <th class="text-right">Active Loans</th>
                                <th class="text-right">Outstanding Amount</th>
                                <th class="text-right">Unpaid Installments</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loans_by_creditor ?? [] as $index => $creditor)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $creditor['name'] }}</td>
                                    <td class="text-right">{{ $creditor['loan_count'] }}</td>
                                    <td class="text-right">IDR {{ number_format($creditor['outstanding'], 0) }}</td>
                                    <td class="text-right">{{ $creditor['unpaid_installments'] }}</td>
                                    <td>
                                        <button class="btn btn-xs btn-info" data-toggle="collapse"
                                            data-target="#creditor-{{ $index }}">
                                            <i class="fas fa-eye"></i> Details
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse" id="creditor-{{ $index }}">
                                    <td colspan="6">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Loan Code</th>
                                                    <th>Description</th>
                                                    <th class="text-right">Principal</th>
                                                    <th class="text-right">Unpaid</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($creditor['loans'] ?? [] as $loan)
                                                    <tr>
                                                        <td>{{ $loan['code'] }}</td>
                                                        <td>{{ $loan['description'] }}</td>
                                                        <td class="text-right">IDR
                                                            {{ number_format($loan['principal'], 0) }}</td>
                                                        <td class="text-right">{{ $loan['unpaid_count'] }}</td>
                                                        <td>
                                                            <a href="{{ route('accounting.loans.show', $loan['id']) }}"
                                                                class="btn btn-xs btn-primary">
                                                                View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No active loans found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Payments (This Month)</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Paid Date</th>
                                <th>Loan</th>
                                <th>Creditor</th>
                                <th class="text-right">Amount</th>
                                <th>Payment Method</th>
                                <th>Bilyet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_payments ?? [] as $payment)
                                <tr>
                                    <td>{{ $payment->paid_date ? \Carbon\Carbon::parse($payment->paid_date)->format('d-M-Y') : '-' }}
                                    </td>
                                    <td>{{ $payment->loan->loan_code ?? 'N/A' }}</td>
                                    <td>{{ $payment->loan->creditor->name ?? 'N/A' }}</td>
                                    <td class="text-right">{{ number_format($payment->bilyet_amount, 0) }}</td>
                                    <td>
                                        @if ($payment->payment_method)
                                            <span
                                                class="badge badge-{{ $payment->payment_method == 'bilyet' ? 'primary' : 'info' }}">
                                                {{ $payment->payment_method_label }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($payment->bilyet)
                                            <a href="{{ route('cashier.bilyets.history', $payment->bilyet_id) }}"
                                                class="text-primary">
                                                {{ $payment->bilyet->full_nomor }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No payments this month</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    <script>
        $(function() {
            const paymentMethodData = @json($payment_method_stats ?? []);

            if (paymentMethodData && Object.keys(paymentMethodData).length > 0) {
                const ctx = document.getElementById('paymentMethodChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(paymentMethodData),
                        datasets: [{
                            data: Object.values(paymentMethodData),
                            backgroundColor: ['#007bff', '#17a2b8', '#28a745', '#ffc107', '#6c757d']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });
    </script>
@endsection
