@extends('templates.main')

@section('title_page')
    Cash On-Hand Statement
@endsection

@section('breadcrumb_title')
    cash-onhand-statement
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Cash On-Hand Statement</h3>
                    <div class="float-right">
                        <a href="{{ route('cashier.cashonhand-transactions.index') }}" class="btn btn-sm btn-default">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <button class="btn btn-sm btn-success" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <form action="{{ route('cashier.cashonhand-transactions.export-excel') }}" method="POST"
                            class="d-inline">
                            @csrf
                            <input type="hidden" name="account_id" value="{{ $account['id'] ?? '' }}">
                            <input type="hidden" name="start_date" value="{{ $startDate }}">
                            <input type="hidden" name="end_date" value="{{ $endDate }}">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="account-info">
                                <h5>{{ $account['account_number'] }}</h5>
                                <p>{{ $account['name'] }}</p>
                                <p>Period: {{ date('d-M-Y', strtotime($startDate)) }} to
                                    {{ date('d-M-Y', strtotime($endDate)) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped">
                            <thead>
                                <tr class="bg-light">
                                    <th class="text-center" width="5%">No</th>
                                    <th class="text-center" width="10%">Date</th>
                                    <th class="text-center" width="30%">Description</th>
                                    <th class="text-center" width="10%">Document</th>
                                    <th class="text-center" width="10%">Type</th>
                                    <th class="text-center" width="10%">Project</th>
                                    <th class="text-center" width="12%">Debit</th>
                                    <th class="text-center" width="12%">Credit</th>
                                    <th class="text-center" width="13%">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($statementLines as $index => $line)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-center">{{ $line['date'] }}</td>
                                        <td>{{ $line['description'] }}</td>
                                        <td class="text-center">{{ $line['doc_num'] }}</td>
                                        <td class="text-center">{{ $line['doc_type'] }}</td>
                                        <td class="text-center">{{ $line['project_code'] }}</td>
                                        <td class="text-right">{{ $line['debit'] }}</td>
                                        <td class="text-right">{{ $line['credit'] }}</td>
                                        <td class="text-right">{{ $line['balance'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .card-body,
            .card-body * {
                visibility: visible;
            }

            .card-body {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none;
            }

            .table th,
            .table td {
                padding: 5px;
                font-size: 12px;
            }
        }

        .table th,
        .table td {
            padding: 0.4rem;
            font-size: 0.85rem;
        }

        .table thead th {
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
@endsection
