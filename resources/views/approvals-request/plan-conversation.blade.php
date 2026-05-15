@extends('templates.main')

@section('title_page')
    Requestor reply
@endsection

@section('breadcrumb_title')
    approvals
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">{{ $documentTitle }}</h3>
                    <a href="{{ $backRoute }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Document type</dt>
                        <dd class="col-sm-9">{{ strtoupper($plan->document_type) }}</dd>
                        <dt class="col-sm-3">Approval status (this step)</dt>
                        <dd class="col-sm-9">{{ $approval_plan_status[$plan->status] ?? $plan->status }}</dd>
                    </dl>
                    <hr>
                    <div class="form-group">
                        <label>Your note to requestor</label>
                        <textarea class="form-control" rows="3" readonly>{{ $plan->remarks }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Requestor reply</label>
                        <textarea class="form-control" rows="4" readonly>{{ $plan->requestor_remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
