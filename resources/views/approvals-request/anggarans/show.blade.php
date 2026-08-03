@extends('templates.main')

@section('title_page')
    Approval Request
@endsection

@section('breadcrumb_title')
    anggarans
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
            <div class="vj-stat vj-stat-neutral">
                <div class="vj-stat-icon"><i class="fas fa-user"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Requestor</span>
                    <span class="vj-stat-value">{{ $anggaran->createdBy->name }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-tag"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">RAB Type</span>
                    <span class="vj-stat-value">{{ ucfirst($anggaran->type) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-success">
                <div class="vj-stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">RAB Amount</span>
                    <span class="vj-stat-value">{{ number_format($anggaran->amount, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-info-circle"></i> RAB Info
                        </h3>
                        <div class="vj-inline-actions">
                            <button type="button" class="vj-btn vj-btn-warning" data-toggle="modal"
                                data-target="#approvals-update">
                                <i class="fas fa-gavel"></i> Approval
                            </button>
                            <a href="{{ route('approvals.request.anggarans.index') }}" class="vj-action-item vj-action-print"
                                id="back-button">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back</span>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label>Description</label>
                            <textarea cols="30" rows="2" class="form-control" readonly>{{ $anggaran->description }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('approvals-request.anggarans.details_table')
    </div>

    {{-- modal update --}}
    <div class="modal fade" id="approvals-update">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Approval for RAB No. {{ $anggaran->nomor }}</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <form action="{{ route('approvals.plan.update', $document->id) }}" method="POST" class="approval-form">
                    @csrf @method('PUT')
                    <input type="hidden" name="document_type" value="rab">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="status">Approval Status</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="">-- change status --</option>
                                        <option value="1">Approved</option>
                                        <option value="2">Revise</option>
                                        <option value="3">Reject</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div id="remarks-container" class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea name="remarks" id="approval-remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                    @include('approvals-request.partials.modal-footer')
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script>
        $(function() {
            $('.approval-form').on('submit', function(e) {
                e.preventDefault();

                var form = $(this);
                var url = form.attr('action');
                var modal = form.closest('.modal');

                $.ajax({
                    type: "POST",
                    url: url,
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        modal.modal('hide');
                        toastr.success(response.message);

                        setTimeout(function() {
                            window.location.href =
                                "{{ route('approvals.request.anggarans.index') }}";
                        }, 1500);
                    },
                    error: function(xhr) {
                        var errorMessage = xhr.responseJSON ? xhr.responseJSON.message :
                            'An error occurred';
                        toastr.error(errorMessage);
                    }
                });
            });
        });
    </script>
@endsection
