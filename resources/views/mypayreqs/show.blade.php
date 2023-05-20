@extends('templates.main')

@section('title_page')
  My Payreqs
@endsection

@section('breadcrumb_title')
    payreqs
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
              <h3 class="card-title">Payment Request Detail</h3>
              <a href="{{ route('mypayreqs.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
              <div class="row">
                <dt class="col-sm-4">Payreq No</dt>
                <dd class="col-sm-8">: {{ $payreq->payreq_no }}</dd>
                <dt class="col-sm-4">Type</dt> 
                <dd class="col-sm-8">: {{ ucfirst($payreq->type) }}</dd>
                <dt class="col-sm-4">Payreq No</dt>
                <dd class="col-sm-8">: IDR {{ number_format($payreq->amount, 2) }}</dd>
                <dt class="col-sm-4">Purpose</dt>
                <dd class="col-sm-8">: {{ $payreq->remarks }}</dd>
                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">: {{ $payreq->status == 'submitted' ? 'Wait approve' : ucfirst($payreq->status) }}</dd>
              </div>
            </div>

            <div class="card-header">
              <h3 class="card-title">Approval Status</h3>
            </div>
            <div class="card-body">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Approver</th>
                    <th>Status</th>
                    <th>Comment</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($payreq->approval_plans as $key => $item)
                    <tr>
                      <td>{{ $key+1 }}</td>
                      <td>{{ $item->approver->name }}</td>
                      <td>{{ ucfirst($item->status) }}</td>
                      <td>{{ $item->comment }}</td>
                    </tr>
                  @endforeach
              </table>
            </div>

           

        </div>
    </div>
</div>
@endsection