@extends('templates.main')

@section('title_page')
    Approval Request
@endsection

@section('breadcrumb_title')
    payreqs
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Payreq Info</h3>
                    <a href="{{ route('approvals.request.payreqs.index') }}" class="btn btn-xs btn-primary float-right mx-2"
                        id="back-button"><i class="fas fa-arrow-left"></i> Back</a>
                    <button type="button" class="btn btn-xs btn-warning float-right" data-toggle="modal"
                        data-target="#approvals-update"><b>APPROVAL</b></button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq No</h5>
                <span class="description-text">{{ $payreq->nomor }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Requestor</h5>
                <span class="description-text">{{ $payreq->requestor->name }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq Type</h5>
                <span class="description-text">{{ $payreq->type }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq Amount</h5>
                <span class="description-text">{{ number_format($payreq->amount, 2) }}</span>
            </div>
        </div>
    </div>

    <hr>

    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <label>Description</label>
                <textarea name="" id="" cols="30" rows="2" class="form-control" readonly>{{ $payreq->remarks }}</textarea>
            </div>
        </div>
    </div>

    @if ($payreq->rab_id != null)
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label for="anggaran">RAB</label>
                    <input type="text" class="form-control"
                        value="{{ $payreq->anggaran->nomor }} {{ $payreq->anggaran->rab_no ? '|' . $payreq->anggaran->rab_no : '' }} | {{ $payreq->anggaran->description }}"
                        readonly>
                </div>
            </div>
        </div>
    @endif
    <!-- /.row -->

    @include('approvals-request.payreqs.details_table')

    {{-- modal update --}}
    <div class="modal fade" id="approvals-update">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Approval for Payreq No. {{ $payreq->nomor }}</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <form action="{{ route('approvals.plan.update', $document->id) }}" method="POST" class="approval-form">
                    @csrf @method('PUT')
                    <input type="hidden" name="document_type" value="payreq">

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
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            // Handle AJAX form submission for approval forms
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
                        // Close the modal
                        modal.modal('hide');

                        // Show success message with Toastr
                        toastr.success(response.message);

                        // Redirect back to the index page after a short delay
                        setTimeout(function() {
                            window.location.href =
                                "{{ route('approvals.request.payreqs.index') }}";
                        }, 1500);
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        var errorMessage = xhr.responseJSON ? xhr.responseJSON.message :
                            'An error occurred';
                        toastr.error(errorMessage);
                    }
                });
            });
        });
    </script>
@endsection
