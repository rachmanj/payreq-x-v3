@extends('templates.main')

@section('title_page')
  Outgoing Payment Request  
@endsection

@section('breadcrumb_title')
    outgoing
@endsection

@section('content')
    <div class="row">
      <div class="col-7">
        <div class="card">
          <div class="card-header">
            <h6 class="card-title">Payreq No: {{ $payreq->payreq_no . ' | ' . $payreq->requestor->name . ' | IDR. ' . number_format($payreq->amount, 0)  }}</h3>
            <a href="{{ route('cashier.approveds.index') }}" class="btn btn-sm btn-success float-right"><i class="fas fa-arrow-left"></i> Back</a>
          </div>
          <div class="card-body">
            <form action="{{ route('cashier.approveds.store_pay', $payreq->id) }}" method="POST" id="split-update">
              @csrf @method('PUT')
              <div class="form-group">
                <label for="account_id">Account No</label>
                <select name="account_id" id="account_id" class="form-control">
                  {{-- <option value="">-- select account no --</option> --}}
                  @foreach ($accounts as $account)
                      <option value="{{ $account->id }}">{{ $account->account_number .' - '. $account->account_name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label for="date">Date</label>
                <input type="date" class="form-control" name="date" value="{{ old('date', date('Y-m-d')) }}">
              </div>
              <div class="form-group">
                <label for="amount">Amount</label>
                <input type="text" class="form-control" name="amount" value="{{ old('amount', $available_amount) }}">
              </div>
            </form>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-sm btn-primary" form="split-update"> Save</button>
          </div>
        </div>
        
      </div>
  
      <div class="col-5">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">Outgoing Info</h4>
          </div>
          <div class="card-body">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Date</th>
                  <th class="text-right">IDR</th>
                </tr>
              </thead>
              <tbody>
                @if ($outgoings->count() > 0)
                  @foreach ($outgoings as $item)
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>{{ date('d-M-Y', strtotime($item->outgoing_date)) }}</td>
                      <td class="text-right">{{ number_format($item->amount, 0) }}</td>
                    </tr>
                  @endforeach
                  <tr>
                    <th colspan="2">Total</th>
                    <th class="text-right">{{ number_format($outgoings->sum('amount'), 0) }}</th>
                  </tr>
                @else
                  <tr>
                    <td colspan="3" class="text-center">No data</td>
                  </tr>
                @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
@endsection