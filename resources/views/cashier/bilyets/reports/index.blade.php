@extends('templates.main')

@section('title_page', 'Bilyet Reports & Analytics')

@section('content')
    <x-bilyet-links page="reports" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar"></i> Bilyet Reports & Analytics
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('cashier.bilyets.index') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-arrow-left"></i> Back to Bilyets
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_from">Date From</label>
                                    <input type="date" id="date_from" class="form-control form-control-sm"
                                        value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_to">Date To</label>
                                    <input type="date" id="date_to" class="form-control form-control-sm"
                                        value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="project">Project</label>
                                    <select id="project" class="form-control form-control-sm">
                                        <option value="">All Projects</option>
                                        <option value="project1">Project 1</option>
                                        <option value="project2">Project 2</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="button" id="loadReport" class="btn btn-sm btn-primary">
                                            <i class="fas fa-sync"></i> Load Report
                                        </button>
                                        <button type="button" id="exportReport" class="btn btn-sm btn-success">
                                            <i class="fas fa-download"></i> Export
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loading Indicator -->
                        <div id="loadingIndicator" class="text-center" style="display: none;">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Loading report data...</p>
                        </div>

                        <!-- Report Content -->
                        <div id="reportContent" style="display: none;">
                            <!-- Performance Metrics -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info">
                                            <i class="fas fa-file-invoice"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Bilyets</span>
                                            <span class="info-box-number" id="totalBilyets">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Amount</span>
                                            <span class="info-box-number" id="totalAmount">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning">
                                            <i class="fas fa-clock"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Avg Processing Time</span>
                                            <span class="info-box-number" id="avgProcessingTime">0 days</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary">
                                            <i class="fas fa-chart-line"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Settlement Rate</span>
                                            <span class="info-box-number" id="settlementRate">0%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Charts Row -->
                            <div class="row">
                                <!-- Status Distribution Chart -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Status Distribution</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="statusChart" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <!-- Type Distribution Chart -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Type Distribution</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="typeChart" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Monthly Trends Chart -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Monthly Trends</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="monthlyTrendsChart" width="800" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Distribution Table -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Bank Distribution</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Bank</th>
                                                            <th>Count</th>
                                                            <th>Total Amount</th>
                                                            <th>Percentage</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="bankDistributionTable">
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Activity Table -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Top Active Users</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>User</th>
                                                            <th>Activity Count</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="userActivityTable">
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Load initial report
            loadReport();

            $('#loadReport').click(function() {
                loadReport();
            });

            $('#exportReport').click(function() {
                exportReport();
            });

            function loadReport() {
                $('#loadingIndicator').show();
                $('#reportContent').hide();

                const params = {
                    date_from: $('#date_from').val(),
                    date_to: $('#date_to').val(),
                    project: $('#project').val()
                };

                // Load dashboard data
                $.get('{{ route('cashier.bilyets.reports.dashboard') }}', params)
                    .done(function(data) {
                        updateDashboard(data);
                        $('#loadingIndicator').hide();
                        $('#reportContent').show();
                    })
                    .fail(function() {
                        $('#loadingIndicator').hide();
                        alert('Failed to load report data');
                    });

                // Load analytics data
                $.get('{{ route('cashier.bilyets.reports.analytics') }}', params)
                    .done(function(data) {
                        updateAnalytics(data);
                    });
            }

            function updateDashboard(data) {
                // Update performance metrics
                $('#totalBilyets').text(data.performance_metrics.total_bilyets);
                $('#totalAmount').text(formatCurrency(data.performance_metrics.total_amount));
                $('#avgProcessingTime').text(data.performance_metrics.average_amount.toFixed(2));

                // Calculate settlement rate
                const settlementRate = data.performance_metrics.total_bilyets > 0 ?
                    (data.performance_metrics.settled_count / data.performance_metrics.total_bilyets * 100).toFixed(
                        1) :
                    0;
                $('#settlementRate').text(settlementRate + '%');

                // Update charts
                updateStatusChart(data.status_distribution);
                updateTypeChart(data.type_distribution);
                updateMonthlyTrendsChart(data.monthly_trends);
                updateBankDistributionTable(data.bank_distribution);
                updateUserActivityTable(data.user_activity);
            }

            function updateAnalytics(data) {
                $('#avgProcessingTime').text(data.processing_times.average_days + ' days');
            }

            function updateStatusChart(data) {
                const ctx = document.getElementById('statusChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(data),
                        datasets: [{
                            data: Object.values(data).map(item => item.count),
                            backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            function updateTypeChart(data) {
                const ctx = document.getElementById('typeChart').getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: Object.keys(data),
                        datasets: [{
                            data: Object.values(data).map(item => item.count),
                            backgroundColor: ['#17a2b8', '#6f42c1', '#fd7e14']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            function updateMonthlyTrendsChart(data) {
                const ctx = document.getElementById('monthlyTrendsChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(item => item.month),
                        datasets: [{
                            label: 'Count',
                            data: data.map(item => item.count),
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            yAxisID: 'y'
                        }, {
                            label: 'Total Amount',
                            data: data.map(item => item.total_amount),
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        }
                    }
                });
            }

            function updateBankDistributionTable(data) {
                const tbody = $('#bankDistributionTable');
                tbody.empty();

                const totalAmount = Object.values(data).reduce((sum, item) => sum + item.total_amount, 0);

                Object.entries(data).forEach(([bank, info]) => {
                    const percentage = totalAmount > 0 ? (info.total_amount / totalAmount * 100).toFixed(
                        1) : 0;
                    tbody.append(`
                <tr>
                    <td>${bank}</td>
                    <td>${info.count}</td>
                    <td>${formatCurrency(info.total_amount)}</td>
                    <td>${percentage}%</td>
                </tr>
            `);
                });
            }

            function updateUserActivityTable(data) {
                const tbody = $('#userActivityTable');
                tbody.empty();

                data.forEach(user => {
                    tbody.append(`
                <tr>
                    <td>${user.user.name || 'Unknown'}</td>
                    <td>${user.activity_count}</td>
                    <td>${Object.keys(user.actions).join(', ')}</td>
                </tr>
            `);
                });
            }

            function exportReport() {
                const params = {
                    date_from: $('#date_from').val(),
                    date_to: $('#date_to').val(),
                    project: $('#project').val(),
                    format: 'excel'
                };

                const queryString = new URLSearchParams(params).toString();
                window.open('{{ route('cashier.bilyets.reports.export') }}?' + queryString, '_blank');
            }

            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(amount);
            }
        });
    </script>
@endsection

@section('styles')
    <style>
        .card-header .active {
            color: black;
            text-transform: uppercase;
        }
    </style>
@endsection
