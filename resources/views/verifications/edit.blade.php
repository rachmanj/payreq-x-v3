@extends('templates.main')

@section('title_page')
    Verifications
@endsection

@section('breadcrumb_title')
    verifications
@endsection

@section('content')
    @php
        $remarks = filled($realization->remarks) ? $realization->remarks : $realization->payreq->remarks;
        $payreqAmount = $realization->payreq->amount;
        $realizationAmount = $realization_details->count() > 0 ? $realization_details->sum('amount') : 0;
        $variance = $payreqAmount - $realizationAmount;
    @endphp

    <div class="vj-show">
        <div class="vj-stat-grid vj-stat-grid-4 mb-3">
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-hashtag"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Realization No</span>
                    <span class="vj-stat-value">{{ $realization->nomor }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-neutral">
                <div class="vj-stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Payreq No</span>
                    <span class="vj-stat-value">{{ $realization->payreq->nomor }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-success">
                <div class="vj-stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Payreq Amount</span>
                    <span class="vj-stat-value">{{ number_format($payreqAmount, 2) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-receipt"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Realization Amount</span>
                    <span class="vj-stat-value">{{ number_format($realizationAmount, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-info-circle"></i> Verification Info
                        </h3>
                        <span class="vj-chip vj-chip-info">Variance: IDR {{ number_format($variance, 2) }}</span>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-3">
                            <dt class="col-sm-3">Employee</dt>
                            <dd class="col-sm-9">{{ $realization->payreq->requestor->name }}</dd>
                            <dt class="col-sm-3">Department</dt>
                            <dd class="col-sm-9 mb-0">{{ $realization->payreq->requestor->department->department_name }}</dd>
                        </dl>
                        <div class="vj-note">
                            <i class="fas fa-comment-alt"></i>
                            <div>
                                <strong>Remarks</strong>
                                <div class="text-break">{!! $remarks ? nl2br(e($remarks)) : '—' !!}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('verifications.edit_details_table')
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })

            @foreach ($realization_details as $item)
                $('#account_number_{{ $item->id }}').on('change', function() {
                    var account_number_{{ $item->id }} = $('#account_number_{{ $item->id }}')
                        .val();

                    $.ajax({
                        url: '{{ route('get_account_name') }}',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            account_number: account_number_{{ $item->id }},
                            realization_detail_id: {{ $item->id }},
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(data) {
                            $('#account_name_{{ $item->id }}').val(data);
                        }
                    });
                })
            @endforeach
        });
    </script>
@endsection
