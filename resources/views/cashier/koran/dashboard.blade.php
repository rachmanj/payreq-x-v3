@extends('templates.main')

@section('title_page')
    Rekening Koran
@endsection

@section('breadcrumb_title')
    cashier / koran / dashboard
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-koran-links page="dashboard" />

            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-university"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Accounts</span>
                            <span class="info-box-number">{{ $statistics['total_accounts'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Completed</span>
                            <span class="info-box-number">{{ $statistics['completed_months'] }}/{{ $statistics['total_months'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Missing</span>
                            <span class="info-box-number">{{ $statistics['missing_months'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-percentage"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Completion</span>
                            <span class="info-box-number">{{ $statistics['completion_percentage'] }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header koran-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <strong>Rekening Koran Dashboard - {{ $year }}</strong>
                        </h3>
                        <div class="year-selector">
                            <a href="{{ route('cashier.koran.index', ['page' => 'dashboard', 'year' => 2026]) }}"
                                class="year-btn {{ $year == 2026 ? 'active' : '' }}">
                                2026
                            </a>
                            <a href="{{ route('cashier.koran.index', ['page' => 'dashboard', 'year' => 2025]) }}"
                                class="year-btn {{ $year == 2025 ? 'active' : '' }}">
                                2025
                            </a>
                            <a href="{{ route('cashier.koran.index', ['page' => 'dashboard', 'year' => 2024]) }}"
                                class="year-btn {{ $year == 2024 ? 'active' : '' }}">
                                2024
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive koran-table-wrapper">
                        <table class="table table-sm koran-table">
                            <thead class="koran-table-header">
                                <tr>
                                    <th class="text-center" style="width: 40px">#</th>
                                    <th style="min-width: 150px">Account Number</th>
                                    <th style="min-width: 200px">Account Name</th>
                                    <th style="min-width: 80px">Project</th>
                                    <th class="text-center" style="min-width: 100px">Progress</th>
                                    <th class="text-center" style="min-width: 50px">Jan</th>
                                    <th class="text-center" style="min-width: 50px">Feb</th>
                                    <th class="text-center" style="min-width: 50px">Mar</th>
                                    <th class="text-center" style="min-width: 50px">Apr</th>
                                    <th class="text-center" style="min-width: 50px">May</th>
                                    <th class="text-center" style="min-width: 50px">Jun</th>
                                    <th class="text-center" style="min-width: 50px">Jul</th>
                                    <th class="text-center" style="min-width: 50px">Aug</th>
                                    <th class="text-center" style="min-width: 50px">Sep</th>
                                    <th class="text-center" style="min-width: 50px">Oct</th>
                                    <th class="text-center" style="min-width: 50px">Nov</th>
                                    <th class="text-center" style="min-width: 50px">Dec</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($korans as $koran)
                                    @foreach ($koran['giros'] as $index => $giro)
                                        <tr class="koran-table-row">
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <strong class="account-number">{{ $giro['acc_no'] }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $giro['bank_name'] }}</small>
                                            </td>
                                            <td>
                                                <span class="account-name">{{ $giro['acc_name'] }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $giro['project'] }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="progress-wrapper">
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar 
                                                            @if($giro['completion_percentage'] == 100) bg-success
                                                            @elseif($giro['completion_percentage'] >= 75) bg-info
                                                            @elseif($giro['completion_percentage'] >= 50) bg-warning
                                                            @else bg-danger
                                                            @endif" 
                                                            role="progressbar" 
                                                            style="width: {{ $giro['completion_percentage'] }}%"
                                                            aria-valuenow="{{ $giro['completion_percentage'] }}" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <small class="progress-text">
                                                        {{ $giro['completed_count'] }}/{{ $giro['total_months'] }} ({{ $giro['completion_percentage'] }}%)
                                                    </small>
                                                </div>
                                            </td>
                                            @foreach ($giro['data'] as $month)
                                                <td class="text-center status-cell">
                                                    @if ($month['status'] == false)
                                                        <span class="status-badge status-missing" 
                                                            data-toggle="tooltip" 
                                                            data-placement="top" 
                                                            title="Missing - {{ \Carbon\Carbon::create()->month($month['month'])->format('F') }}">
                                                            <i class="fas fa-times"></i>
                                                        </span>
                                                    @else
                                                        <a href="{{ $month['filename1'] }}" 
                                                            target="_blank" 
                                                            class="status-badge status-complete"
                                                            data-toggle="tooltip" 
                                                            data-placement="top" 
                                                            title="Uploaded: {{ $month['upload_date'] ?? 'N/A' }}">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>


        </div> <!-- /.col -->
    </div> <!-- /.row -->
@endsection

@section('styles')
    <style>
        .koran-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }

        .koran-header .card-title {
            color: white;
            font-size: 1.3rem;
        }

        .year-selector {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .year-btn {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .year-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            color: white;
            text-decoration: none;
        }

        .year-btn.active {
            background: white;
            color: #667eea;
            font-weight: 600;
            border-color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .info-box {
            box-shadow: 0 0 1px rgba(0, 0, 0, 0.125), 0 1px 3px rgba(0, 0, 0, 0.2);
            border-radius: 0.25rem;
            background-color: #fff;
            display: flex;
            margin-bottom: 1rem;
            min-height: 80px;
            padding: 0.5rem;
            position: relative;
        }

        .info-box-icon {
            border-radius: 0.25rem;
            align-items: center;
            display: flex;
            font-size: 1.875rem;
            justify-content: center;
            width: 70px;
        }

        .info-box-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.8;
            flex: 1;
            padding: 0 10px;
        }

        .info-box-text {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .info-box-number {
            display: block;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .koran-table-wrapper {
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: auto;
        }

        .koran-table {
            margin-bottom: 0;
            width: 100%;
        }

        .koran-table-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f4f6f9;
        }

        .koran-table-header th {
            background-color: #f4f6f9;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 12px 8px;
            white-space: nowrap;
            font-size: 0.85rem;
            color: #495057;
        }

        .koran-table-row {
            transition: all 0.2s ease;
        }

        .koran-table-row:nth-child(even) {
            background-color: #f8f9fa;
        }

        .koran-table-row:nth-child(odd) {
            background-color: #ffffff;
        }

        .koran-table-row:hover {
            background-color: #e3f2fd !important;
            transform: scale(1.001);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .koran-table-row td {
            padding: 12px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }

        .account-number {
            font-size: 0.9rem;
            color: #212529;
            font-weight: 600;
        }

        .account-name {
            font-size: 0.9rem;
            color: #495057;
        }

        .progress-wrapper {
            min-width: 100px;
        }

        .progress {
            margin-bottom: 4px;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.6s ease;
            font-size: 0.7rem;
            line-height: 20px;
            font-weight: 600;
            color: white;
        }

        .progress-text {
            display: block;
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 2px;
            font-weight: 500;
        }

        .status-cell {
            padding: 8px 4px !important;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .status-complete {
            background-color: #28a745;
            color: white;
            cursor: pointer;
        }

        .status-complete:hover {
            background-color: #218838;
            transform: scale(1.1);
            box-shadow: 0 2px 6px rgba(40, 167, 69, 0.4);
            text-decoration: none;
            color: white;
        }

        .status-missing {
            background-color: #dc3545;
            color: white;
        }

        .status-missing:hover {
            background-color: #c82333;
            transform: scale(1.1);
        }

        .badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            font-weight: 600;
        }

        @media (max-width: 1200px) {
            .koran-table-wrapper {
                font-size: 0.85rem;
            }

            .account-number,
            .account-name {
                font-size: 0.8rem;
            }

            .progress-wrapper {
                min-width: 80px;
            }

            .progress-text {
                font-size: 0.7rem;
            }
        }

        @media (max-width: 768px) {
            .koran-header .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
            }

            .year-selector {
                margin-top: 15px;
                width: 100%;
            }

            .year-btn {
                flex: 1;
                text-align: center;
            }

            .koran-table-wrapper {
                font-size: 0.75rem;
            }

            .koran-table-header th,
            .koran-table-row td {
                padding: 8px 4px;
            }

            .status-badge {
                width: 24px;
                height: 24px;
                font-size: 0.75rem;
            }

            .progress-wrapper {
                min-width: 60px;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
