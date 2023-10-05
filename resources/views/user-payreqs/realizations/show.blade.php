@extends('templates.main')

@section('title_page')
  My Realization
@endsection

@section('breadcrumb_title')
    realization
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
              <h3 class="card-title">Realization Info</h3>
              <a href="{{ route('user-payreqs.realizations.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
              <div class="row">
                <dt class="col-sm-4">Realization No</dt>
                <dd class="col-sm-8">: {{ $realization->nomor }}</dd>
                <dt class="col-sm-4">Amount</dt>
                <dd class="col-sm-8">: IDR {{ number_format($realization->realizationDetails->sum('amount'), 2) }}</dd>
                <dt class="col-sm-4">Payreq No</dt>
                <dd class="col-sm-8">: {{ $realization->payreq->nomor }}</dd>
                <dt class="col-sm-4">Payreq Remark</dt>
                <dd class="col-sm-8">: {{ $realization->payreq->remarks }}</dd>
                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">: {{ $realization->status == 'submitted' ? 'Wait approve' : ucfirst($realization->status) }}</dd>
                <dt class="col-sm-4">Submitted at</dt>
                <dd class="col-sm-8">: {{ $submit_at->addHours(8)->format('d-M-Y H:i:s') . ' wita'  }}</dd>
                <dt class="col-sm-4">Created at</dt>
                <dd class="col-sm-8">: {{ $realization->created_at->addHours(8)->format('d-M-Y H:i:s') . ' wita' }}</dd>
              </div>
            </div>

            <div class="card-header">
              <h3 class="card-title">Approval Status</h3>
              @if ($realization->status === 'approved')
              <form action="{{ route('user-payreqs.realizations.cancel') }}" method="POST">
                @csrf
                <input type="hidden" name="payreq_id" value="{{ $realization->id }}">
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

            <div class="card-header">
              <h3 class="card-title">Realization Details</h3>
              <h3 class="card-title float-right">Payreq Amount: IDR {{ number_format($realization->payreq->amount, 2) }} | Variant: IDR {{ number_format($realization->payreq->amount - $realization_details->sum('amount'), 2) }}</h3>
            </div>
            <div class="card-body">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                      <th>#</td>
                      <th>Desc</td>
                      <th class="text-right">Amount (IDR)</th>
                  </tr>
              </thead>
              @if ($realization_details->count() > 0) 
                        <tbody>
                            @foreach ($realization_details as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->description }} 
                                        @if ($item->unit_no != null)
                                            <br/>
                                            @if ($item->type === 'fuel')
                                                <small>Unit No: {{ $item->unit_no }}, {{ $item->type }} {{ $item->qty }} {{ $item->uom }}. HM: {{ $item->km_position }}</small>
                                            @else
                                                <small>{{ $item->type }}, HM: {{ $item->km_position }}</small>
                                            @endif 
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-right">Total</td>
                                <td class="text-right"><b>{{ number_format($realization_details->sum('amount'), 2) }}</b></td>
                            </tr>
                        </tfoot>
                    @else
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center">No Data Found</td>
                            </tr>
                        </tbody>
                    @endif
                
              </table>
            </div>

        </div>
    </div>
</div>
@endsection