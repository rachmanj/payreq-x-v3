@extends('templates.main')

@section('title_page')
    Requestor reply
@endsection

@section('breadcrumb_title')
    approvals
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-comments"></i> {{ $documentTitle }}
                        </h3>
                        <a href="{{ $backRoute }}" class="vj-action-item vj-action-print">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back</span>
                        </a>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Document type</dt>
                            <dd class="col-sm-9">
                                <span class="vj-chip vj-chip-neutral">{{ strtoupper($plan->document_type) }}</span>
                            </dd>
                            <dt class="col-sm-3">Approval status (this step)</dt>
                            <dd class="col-sm-9">
                                <span class="vj-chip vj-chip-warning">{{ $approval_plan_status[$plan->status] ?? $plan->status }}</span>
                            </dd>
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
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection
