@extends('templates.main')

@section('title_page')
    Overdue documents
@endsection

@section('breadcrumb_title')
    payreqs / overdue documents
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('user-payreqs.index') }}" class="btn btn-sm btn-outline-secondary">
                &larr; Back to My Payreqs
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Overdue documents</h3>
                    <p class="text-muted small mb-0 mt-1">Semua Payreq Advance yang sudah dibayar namun melewati due date,
                        dan Realization yang sudah disetujui namun melewati due date.</p>
                </div>
                <div class="card-body">
                    <h5 class="text-bold">Payreq Advance (paid, overdue)</h5>
                    @if ($overduePayreqs->isEmpty())
                        <p class="text-muted">Tidak ada payreq advance overdue.</p>
                    @else
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nomor</th>
                                        <th>Project</th>
                                        <th>Due date</th>
                                        <th class="text-right">Days overdue</th>
                                        <th class="text-right">Amount (IDR)</th>
                                        <th>Extensions</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($overduePayreqs as $payreq)
                                        @php
                                            $daysOd = \Carbon\Carbon::parse($payreq->due_date)->diffInDays(now());
                                            $canExtend = in_array($payreq->project ?? '', \App\Models\OverdueExtension::eligibleProjects(), true);
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><a
                                                    href="{{ route('user-payreqs.show', $payreq->id) }}">{{ $payreq->nomor }}</a>
                                            </td>
                                            <td>{{ $payreq->project }}</td>
                                            <td>{{ \Carbon\Carbon::parse($payreq->due_date)->format('d-M-Y') }}</td>
                                            <td class="text-right">{{ $daysOd }}</td>
                                            <td class="text-right">{{ number_format((float) $payreq->amount, 2) }}</td>
                                            <td class="small">{{ $payreq->overdue_extensions_total_count }} submitted,
                                                {{ $payreq->overdue_extensions_approved_count }} approved</td>
                                            <td>
                                                @if ($canExtend)
                                                    @if ($payreq->overdue_extensions_pending_count > 0)
                                                        <span class="badge badge-warning">Extension pending</span>
                                                    @else
                                                        <button type="button" class="btn btn-xs btn-primary"
                                                            data-toggle="modal"
                                                            data-target="#extension-modal-payreq-{{ $payreq->id }}">Request
                                                            Extension</button>
                                                    @endif
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <h5 class="text-bold mt-4">Realizations (approved, overdue)</h5>
                    @if ($overdueRealizations->isEmpty())
                        <p class="text-muted mb-0">Tidak ada realization overdue.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Realization No</th>
                                        <th>Payreq No</th>
                                        <th>Project</th>
                                        <th>Due date</th>
                                        <th class="text-right">Days overdue</th>
                                        <th>Extensions</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($overdueRealizations as $realization)
                                        @php
                                            $daysOd = \Carbon\Carbon::parse($realization->due_date)->diffInDays(now());
                                            $canExtend = in_array($realization->project ?? '', \App\Models\OverdueExtension::eligibleProjects(), true);
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $realization->nomor }}</td>
                                            <td>
                                                @if ($realization->payreq)
                                                    <a
                                                        href="{{ route('user-payreqs.show', $realization->payreq_id) }}">{{ $realization->payreq->nomor }}</a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $realization->project }}</td>
                                            <td>{{ \Carbon\Carbon::parse($realization->due_date)->format('d-M-Y') }}</td>
                                            <td class="text-right">{{ $daysOd }}</td>
                                            <td class="small">{{ $realization->overdue_extensions_total_count }} submitted,
                                                {{ $realization->overdue_extensions_approved_count }} approved</td>
                                            <td>
                                                @if ($canExtend)
                                                    @if ($realization->overdue_extensions_pending_count > 0)
                                                        <span class="badge badge-warning">Extension pending</span>
                                                    @else
                                                        <button type="button" class="btn btn-xs btn-primary"
                                                            data-toggle="modal"
                                                            data-target="#extension-modal-realization-{{ $realization->id }}">Request
                                                            Extension</button>
                                                    @endif
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                                <a href="{{ route('user-payreqs.realizations.add_details', ['realization_id' => $realization->id]) }}"
                                                    class="btn btn-xs btn-outline-secondary ml-1">Open realization</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    @include('user-payreqs.partials.extension-request-modals', [
        'payreqs' => $overduePayreqs,
        'realizations' => $overdueRealizations,
    ])
@endsection

@push('scripts')
    @if ($errors->any() && old('document_type') === 'payreq' && old('document_id'))
        <script>
            $(function() {
                $('#extension-modal-payreq-{{ (int) old('document_id') }}').modal('show');
            });
        </script>
    @endif
    @if ($errors->any() && old('document_type') === 'realization' && old('document_id'))
        <script>
            $(function() {
                $('#extension-modal-realization-{{ (int) old('document_id') }}').modal('show');
            });
        </script>
    @endif
@endpush
