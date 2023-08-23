@extends('templates.main')

@section('title_page')
    Dashboard
@endsection

@section('breadcrumb_title')
    dashboard
@endsection

@section('content')
    <div class="row">
      <div class="col-12">

        <div class="card card-info">
          <div class="card-header">
            <h3 class="card-title">Payment Request Data</h3>
            <a href="{{ route('dashboard.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-undo"></i> Back</a>
          </div>

          <div class="card-body">
            <dl class="row">
              <dt class="col-sm-4">Payreq No</dt>
              <dd class="col-sm-8">: {{ $payreq->payreq_num }}</dd>
              <dt class="col-sm-4">Approval Date</dt>
              <dd class="col-sm-8">: {{ $payreq->approve_date ? date('d F Y', strtotime($payreq->approve_date)) : '-' }}</dd>
              <dt class="col-sm-4">Outgoing Date</dt>
              <dd class="col-sm-8">: {{ $payreq->outgoing_date ? date('d F Y', strtotime($payreq->outgoing_date)) : '-' }}</dd>
              <dt class="col-sm-4">Realization No</dt>
              <dd class="col-sm-8">: {{ $payreq->realization_num ? $payreq->realization_num : '-' }}</dd>
              <dt class="col-sm-4">Realization Date</dt>
              <dd class="col-sm-8">: {{ $payreq->realization_date ? date('d F Y', strtotime($payreq->realization_date)) : '-' }}</dd>
              <dt class="col-sm-4">Remarks</dt>
              <dd class="col-sm-8">: {{ $payreq->remarks ? $payreq->remarks : '-' }}</dd>
            </dl>
          </div>
        </div>
      </div>
    </div>
@endsection