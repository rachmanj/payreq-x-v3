@extends('templates.main')

@section('title_page')
    My Realization
@endsection

@section('breadcrumb_title')
    realization
@endsection

@section('content')
    <div class="vj-show">
        <div class="vj-stat-grid vj-stat-grid-4 mb-3">
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-hashtag"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Realization No</span>
                    <span class="vj-stat-value">{{ $realization->nomor }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-success">
                <div class="vj-stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Amount</span>
                    <span class="vj-stat-value">IDR {{ number_format($realization->realizationDetails->sum('amount'), 2) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-neutral">
                <div class="vj-stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Payreq No</span>
                    <span class="vj-stat-value">{{ $realization->payreq->nomor }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-info-circle"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Status</span>
                    <span class="vj-stat-value">
                        @if ($realization->status === 'submitted')
                            <span class="vj-chip vj-chip-warning">Wait approve</span>
                        @elseif ($realization->status === 'approved')
                            <span class="vj-chip vj-chip-success">Approved</span>
                        @elseif ($realization->status === 'rejected')
                            <span class="vj-chip vj-chip-danger">Rejected</span>
                        @else
                            <span class="vj-chip vj-chip-neutral">{{ ucfirst($realization->status) }}</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-info-circle"></i> Realization Info
                        </h3>
                        <a href="{{ route('user-payreqs.realizations.index') }}" class="vj-action-item vj-action-print">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back</span>
                        </a>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Payreq Remark</dt>
                            <dd class="col-sm-8">{{ $realization->payreq->remarks }}</dd>
                            <dt class="col-sm-4">Submitted at</dt>
                            <dd class="col-sm-8">{{ $submit_at->format('d-M-Y H:i:s') . ' wita' }}</dd>
                            <dt class="col-sm-4">Created at</dt>
                            <dd class="col-sm-8">{{ $realization->created_at->format('d-M-Y H:i:s') . ' wita' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-check-circle"></i> Approval Status
                        </h3>
                        @if ($realization->status === 'approved')
                            <div class="vj-inline-actions">
                                @if ($realization->payreq->cancel_count > 2)
                                    <span class="vj-chip vj-chip-warning">
                                        <i class="fas fa-info-circle"></i> Canceled 3 times
                                    </span>
                                @endif
                                <form action="{{ route('user-payreqs.realizations.cancel', $realization->id) }}" method="POST"
                                    class="vj-action-item-form">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="vj-action-item vj-action-item-btn vj-action-cancel {{ $realization->payreq->cancel_count > 2 ? 'is-disabled' : '' }}"
                                        onclick="return confirm('Are You sure You want to CANCEL this Payment Request? This transaction cannot be undone')"
                                        @if ($realization->payreq->cancel_count > 2) disabled @endif>
                                        <i class="fas fa-ban"></i>
                                        <span>Cancel</span>
                                    </button>
                                </form>
                            </div>
                        @endif
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
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-list"></i> Realization Details
                        </h3>
                        <div class="vj-inline-actions">
                            <span class="vj-chip vj-chip-neutral">Payreq: IDR
                                {{ number_format($realization->payreq->amount, 2) }}</span>
                            <span class="vj-chip vj-chip-info">Variance: IDR
                                {{ number_format($realization->payreq->amount - $realization_details->sum('amount'), 2) }}</span>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Desc</th>
                                    <th>Expense date</th>
                                    <th class="text-right">Amount (IDR)</th>
                                </tr>
                            </thead>
                            @if ($realization_details->count() > 0)
                                <tbody>
                                    @foreach ($realization_details as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item->description }}
                                                @if ($item->unit_no != null)
                                                    <br />
                                                    @if ($item->type === 'fuel')
                                                        <small>Unit No: {{ $item->unit_no }}, {{ $item->type }}
                                                            {{ $item->qty }} {{ $item->uom }}. HM:
                                                            {{ $item->km_position }}</small>
                                                    @else
                                                        <small>{{ $item->type }}, HM: {{ $item->km_position }}</small>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>{{ $item->expense_date ? $item->expense_date->format('d-M-Y') : '—' }}</td>
                                            <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-right">Total</td>
                                        <td class="text-right">
                                            <b>{{ number_format($realization_details->sum('amount'), 2) }}</b>
                                        </td>
                                    </tr>
                                </tfoot>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center">No Data Found</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    @include('user-payreqs.partials.save-requestor-remark-script')
@endsection
