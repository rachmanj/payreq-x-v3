@extends('templates.main')

@section('title_page')
    RAB
@endsection

@section('breadcrumb_title')
    payreqs / rab / show
@endsection

@section('content')
    <div class="vj-show">
        <div class="vj-stat-grid vj-stat-grid-4 mb-3">
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-hashtag"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">RAB No</span>
                    <span class="vj-stat-value">{{ $anggaran->nomor }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-success">
                <div class="vj-stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Budget</span>
                    <span class="vj-stat-value">Rp. {{ number_format($anggaran->amount, 2) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-neutral">
                <div class="vj-stat-icon"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Release to Date</span>
                    <span class="vj-stat-value">Rp. {{ number_format($total_release, 2) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Progress</span>
                    <span class="vj-stat-value">{{ $progres_persen }}%</span>
                </div>
            </div>
        </div>

        @if ($spendingExceeded)
            <div class="vj-alert vj-alert-danger mb-3">
                <i class="fas fa-exclamation-circle mr-1"></i>
                Utilization exceeds 100% of this budget.
            </div>
        @elseif ($spendingWarning)
            <div class="vj-alert vj-alert-warning mb-3">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Utilization is at or above the alert threshold
                ({{ (int) ($anggaran->warning_threshold ?? 80) }}%).
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-info-circle"></i> RAB Detail
                        </h3>
                        <div class="vj-inline-actions">
                            @if ($anggaran->filename)
                                <a href="{{ asset('file_upload/') . '/' . $anggaran->filename }}"
                                    class="vj-action-item vj-action-item-xs vj-action-export" target="_blank">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Show RAB</span>
                                </a>
                            @endif
                            <a href="{{ route('user-payreqs.anggarans.index') }}" class="vj-action-item vj-action-print">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back</span>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">RAB No</dt>
                            <dd class="col-sm-8">
                                {{ $anggaran->nomor }}
                                @if ($anggaran->rab_no)
                                    | {{ $anggaran->rab_no }}
                                @endif
                            </dd>
                            <dt class="col-sm-4">Date | Periode</dt>
                            <dd class="col-sm-8">
                                {{ date('d-M-Y', strtotime($anggaran->date)) }} |
                                {{ $anggaran->periode_anggaran !== null ? date('d-M-Y', strtotime($anggaran->periode_anggaran)) : '-' }}
                            </dd>
                            <dt class="col-sm-4">Description</dt>
                            <dd class="col-sm-8">{{ $anggaran->description }}</dd>
                            <dt class="col-sm-4">For Project</dt>
                            <dd class="col-sm-8">
                                <span class="vj-chip vj-chip-info">{{ $anggaran->rab_project }}</span>
                            </dd>
                            <dt class="col-sm-4">Department</dt>
                            <dd class="col-sm-8">{{ $anggaran->createdBy->department->department_name }}</dd>
                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                @php
                                    $statusChip = match ($anggaran->status) {
                                        'approved' => 'vj-chip-success',
                                        'submitted' => 'vj-chip-warning',
                                        'rejected' => 'vj-chip-danger',
                                        'draft' => 'vj-chip-neutral',
                                        default => 'vj-chip-neutral',
                                    };
                                @endphp
                                <span class="vj-chip {{ $statusChip }}">{{ ucfirst($anggaran->status) }}</span>
                            </dd>
                            <dt class="col-sm-4">Fund status</dt>
                            <dd class="col-sm-8">
                                @php
                                    $fundChip = match ($anggaran->fund_status ?? 'pending') {
                                        'funded' => 'vj-chip-success',
                                        'pending' => 'vj-chip-warning',
                                        default => 'vj-chip-neutral',
                                    };
                                @endphp
                                <span class="vj-chip {{ $fundChip }}">{{ $anggaran->fund_status ?? 'pending' }}</span>
                            </dd>
                            <dt class="col-sm-4">Alert threshold</dt>
                            <dd class="col-sm-8">{{ (int) ($anggaran->warning_threshold ?? 80) }}%</dd>
                            <dt class="col-sm-4">Progress</dt>
                            <dd class="col-sm-8">
                                <div class="text-center">
                                    <small>{{ $progres_persen }}%</small>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped {{ $statusColor }} text-center"
                                            role="progressbar" style="width: {{ $progres_persen }}%"
                                            aria-valuenow="{{ $progres_persen }}" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        @if ($anggaran->details->isNotEmpty())
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-list"></i> Budget lines
                            </h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-sm table-bordered table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-right">Qty</th>
                                        <th>Unit</th>
                                        <th class="text-right">Unit price</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($anggaran->details as $line)
                                        <tr>
                                            <td>{{ $line->description }}</td>
                                            <td class="text-right">{{ number_format((float) $line->qty, 4) }}</td>
                                            <td>{{ $line->unit }}</td>
                                            <td class="text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                                            <td class="text-right">{{ number_format((float) $line->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-check-circle"></i> Approval Status
                        </h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Approver</th>
                                    <th>Status</th>
                                    <th>Comment</th>
                                    <th>Your reply</th>
                                    <th>Response at</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($approval_plans->count() > 0)
                                    @foreach ($approval_plans as $key => $item)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $item->approver->name }}</td>
                                            <td>
                                                @foreach ($approval_plan_status as $statusKey => $value)
                                                    @if ($statusKey == $item->status)
                                                        @php
                                                            $chipClass = match (true) {
                                                                $statusKey === 1 => 'vj-chip-success',
                                                                $statusKey === 2 => 'vj-chip-danger',
                                                                $statusKey === 3 => 'vj-chip-warning',
                                                                default => 'vj-chip-neutral',
                                                            };
                                                        @endphp
                                                        <span class="vj-chip {{ $chipClass }}">{{ $value }}</span>
                                                    @endif
                                                @endforeach
                                            </td>
                                            <td>{{ $item->remarks }}</td>
                                            @include('user-payreqs.partials.approval-plan-your-reply-cell', [
                                                'item' => $item,
                                            ])
                                            <td>{{ $item->status === 0 ? ' - ' : $item->updated_at->format('d-M-Y H:i:s') . ' wita' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="text-center">No Approval Plans Found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-invoice-dollar"></i> Payreq / Realization
                        </h3>
                    </div>
                    <div class="card-body">
                        <table id="payreq-buc" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Payreq/Realz No</th>
                                    <th>ApprovedDate</th>
                                    <th>Requestor</th>
                                    <th>Remarks</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            $("#payreq-buc").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('user-payreqs.anggarans.payreqs_data', $anggaran->id) }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor'
                    },
                    {
                        data: 'approved_at'
                    },
                    {
                        data: 'employee'
                    },
                    {
                        data: 'remarks'
                    },
                    {
                        data: 'amount'
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                    targets: [0, 5],
                    className: 'text-right'
                }]
            })
        });
    </script>
    @include('user-payreqs.partials.save-requestor-remark-script')
@endsection
