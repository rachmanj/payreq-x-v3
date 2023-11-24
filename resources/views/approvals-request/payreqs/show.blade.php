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
              <h3 class="card-title">Advance Info</h3>
              <a href="{{ route('approvals.request.payreqs.index') }}" class="btn btn-xs btn-primary float-right mx-2"><i class="fas fa-arrow-left"></i> Back</a>
              <button type="button" class="btn btn-xs btn-warning float-right" data-toggle="modal" data-target="#approvals-update"><b>APPROVAL</b></button>
          </div>
      </div>
  </div>
</div>

<div class="row">
  <div class="col-sm-4 col-6">
    <div class="description-block border-right">
        <h5 class="description-header">Payreq No</h5>
        <span class="description-text">{{ $payreq->nomor }}</span>
    </div>
  </div>
  <div class="col-sm-4 col-6">
    <div class="description-block border-right">
      <h5 class="description-header">Payreq Type</h5>
      <span class="description-text">{{ $payreq->type }}</span>
    </div>
  </div>
  <div class="col-sm-4 col-6">
    <div class="description-block border-right">
      <h5 class="description-header">Payreq Amount</h5>
      <span class="description-text">{{ number_format($payreq->amount, 2) }}</span>
    </div>
  </div>
</div>
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

          <form action="{{ route('approvals.plan.update', $document->id) }}" method="POST">
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
                          <div id="remarks" class="form-group">
                              <label for="remarks">Remarks</label>
                              <textarea name="remarks" id="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
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
