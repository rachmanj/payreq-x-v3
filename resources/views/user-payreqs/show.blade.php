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
              <a href="{{ route('user-payreqs.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
              <div class="row">
                <dt class="col-sm-4">Payreq No</dt>
                <dd class="col-sm-8">: {{ $payreq->nomor }}</dd>
                <dt class="col-sm-4">Type</dt> 
                <dd class="col-sm-8">: {{ ucfirst($payreq->type) }}</dd>
                <dt class="col-sm-4">Amount</dt>
                <dd class="col-sm-8">: IDR {{ number_format($payreq->amount, 2) }}</dd>
                <dt class="col-sm-4">Purpose</dt>
                <dd class="col-sm-8">: {{ $payreq->remarks }}</dd>
                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">: {{ $payreq->status == 'submitted' ? 'Wait approve' : ucfirst($payreq->status) }} {{ $paid_date }}</dd>
                <dt class="col-sm-4">Submitted at</dt>
                <dd class="col-sm-8">: {{ $submit_at }}</dd>
                <dt class="col-sm-4">Due date</dt>
                <dd class="col-sm-8">: {{ $due_date }}</dd>
                <dt class="col-sm-4">Created at</dt>
                <dd class="col-sm-8">: {{ $payreq->created_at->addHours(8)->format('d-M-Y H:i:s') . ' wita' }}</dd>
              </div>
            </div>

            <div class="card-header">
              <h3 class="card-title">Approval Status</h3>
              @if ($payreq->status === 'approved' && $payreq->type !== 'other')
              <form action="{{ route('user-payreqs.cancel') }}" method="POST">
                @csrf
                <input type="hidden" name="payreq_id" value="{{ $payreq->id }}">
                <button type="submit" class="btn btn-sm btn-danger d-inline float-right" onclick="return confirm('Are You sure You want to CANCEL this Payment Request? This transaction cannot be undone')">CANCEL</button>
              </form>
              @endif
            </div>
            <div class="card-body">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Approver</th>
                    <th>Status</th>
                    <th>Comment</th>
                    <th>Response at</th>
                  </tr>
                </thead>
                <tbody>
                  @if ($approval_plans->count() > 0)
                    @foreach ($approval_plans as $key => $item)
                      <tr>
                        <td>{{ $key+1 }}</td>
                        <td>{{ $item->approver->name }}</td>
                        @foreach ($approval_plan_status as $key => $value)
                          @if ($key == $item->status)
                            <td>{{ $value }}</td>
                          @endif
                        @endforeach
                        <td>{{ $item->remarks }}</td>
                        <td>{{ $item->status === 0 ? ' - ' : $item->updated_at->addHours(8)->format('d-M-Y H:i:s') . ' wita' }}</td>
                      </tr>
                    @endforeach
                  @else
                      <tr>
                        <td colspan="5" class="text-center">No Approval Plans Found</td>
                      </tr>
                  @endif
              </table>
            </div>

        </div>
    </div>
</div>
@endsection