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
            @if (Session::has('success'))
              <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                {{ Session::get('success') }}
              </div>
            @endif
            @if (Session::has('error'))
              <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                {{ Session::get('error') }}
              </div>
            @endif
            <h6 class="card-title">Split Payment Request No: {{ $payreq->payreq_num . ' | ' . $payreq->employee->fullname . ' | IDR. ' . number_format($payreq->payreq_idr, 0)  }}</h3>
          </div>
          <div class="card-body">
            <form action="{{ route('outgoing.split_update', $payreq->id) }}" method="POST" id="split-update">
              @csrf @method('PUT')
              <div class="form-group">
                <label for="account_id">Account No</label>
                <select name="account_id" id="account_id" class="form-control">
                  <option value="">-- select account no --</option>
                  @foreach ($accounts as $account)
                      <option value="{{ $account->id }}">{{ $account->account_no .' - '. $account->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label for="date">Date</label>
                <input type="date" class="form-control" name="date">
              </div>
              <div class="form-group">
                <label for="">Split Amount</label>
                <input type="text" class="form-control" name="split_amount" value="{{ old('split_amount', $payreq->payreq_idr - $payreq->splits->sum('amount')) }}">
              </div>
            </form>
          </div>
          <div class="card-footer">
            <a href="{{ route('outgoing.index') }}" class="btn btn-sm btn-success"><i class="fas fa-undo"></i> Back</a>
            <button type="submit" class="btn btn-sm btn-primary" form="split-update"> Save</button>
          </div>
        </div>
        
      </div>
      <div class="col-5">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">Split Info</h4>
          </div>
          <div class="card-body">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>date</th>
                  <th>amount</th>
                </tr>
              </thead>
              <tbody>
                @if ($payreq->splits->count() > 0)
                  @foreach ($payreq->splits as $split)
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>{{ date('d-M-Y', strtotime($split->date)) }}</td>
                      <td class="text-right">{{ number_format($split->amount, 0) }}</td>
                    </tr>
                  @endforeach
                  <tr>
                    <th colspan="2">Total</th>
                    <th class="text-right">{{ number_format($payreq->splits->sum('amount'), 0) }}</th>
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