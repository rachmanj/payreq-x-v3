@extends('templates.main')

@section('title_page')
    Search Payment Request
@endsection

@section('breadcrumb_title')
    search
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <form action="{{ route('search.display') }}" method="POST">
          @csrf
          <div class="col-6">
            <label>Input Payreq No</label>
            <div class="input-group mb-3">
              <input type="text" name="payreq_no" class="form-control rounded-0" autocomplete="off">
              <span class="input-group-append">
                <button type="submit" class="btn btn-success btn-flat">Go!</button>
              </span>
            </div>
          </div>
        </form>
      </div>
      <div class="card-body">
        <table class="table table-striped" >
          <thead>
            <tr>
              <th>Requestor</th>
              <th>PayreqNo</th>
              <th>Type</th>
              <th>IDR</th>
              <th>ApprvDate</th>
              <th>OutgoingDate</th>
              <th>RealzNo</th>
              <th>RealzDate</th>
              <th>VerifyDate</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @if ($payreq)
            <tr>
                
                    <td>{{ $payreq->employee->name }}</td>
                    <td><a href="{{ route('search.edit', $payreq->id) }}">{{ $payreq->payreq_num }}</a></td>
                    <td>{{ $payreq->payreq_type }}</td>
                    <td>{{ number_format($payreq->payreq_idr, 0) }}</td>
                    <td>{{ $payreq->approve_date ? date('d-m-Y', strtotime($payreq->approve_date)) : '-'}}</td>
                    <td>{{ $payreq->outgoing_date ? date('d-m-Y', strtotime($payreq->outgoing_date)) : '-' }}</td>
                    <td>{{ $payreq->realization_num	}}</td>
                    <td>{{ $payreq->realization_date ? date('d-m-Y', strtotime($payreq->realization_date)) : '-'	}}</td>
                    <td>{{ $payreq->verify_date ? date('d-m-Y', strtotime($payreq->verify_date)) : '-'	}}</td>
                    <td></td>
                  
            </tr>
            @else
                <tr>
                    <td colspan="9" class="text-center">No Data Found</td>
                </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection