@extends('templates.main')

@section('title_page')
    
@endsection

@section('breadcrumb_title')
    search
@endsection

@section('content')
<div class="container-fluid">
  <h2 class="text-center display-4">Search</h2>
  <div class="row">
      <div class="col-md-8 offset-md-2">
          <form action="{{ route('search.display') }}" method="POST">
            @csrf
              <div class="input-group">
                  <input type="search" name="document_no" class="form-control form-control-lg" placeholder="Type document number here" required >
                  <div class="input-group-append">
                      <button type="submit" class="btn btn-lg btn-default">
                          <i class="fa fa-search"></i>
                      </button>
                  </div>
              </div>
          </form>
      </div>
  </div>
</div>
@if ($payreqs->count() > 0)
    <div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payment Request</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Document No</th>
                            <th>Type</th>
                            <th>Employee</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payreqs as $item)
                            <tr>
                                <td><a href="{{ route('search.show', ['doctype' => 'payreq', 'doc_id' => $item->id]) }}">{{ $item->nomor }}</a></td>
                                <td>{{ $item->type }}</td>
                                <td>{{ $item->requestor->name }}</td>
                                <td>{{ number_format($item->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

@if ($realizations->count() > 0)
    <div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Realizations</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>DocumentNo</th>
                            <th>PayreqNo</th>
                            <th>Employee</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($realizations as $item)
                            <tr>
                                <td><a href="{{ route('search.show', ['doctype' => 'realization', 'doc_id' => $item->id]) }}">{{ $item->nomor }}</a></td>
                                <td>{{ $item->payreq->nomor }}</td>
                                <td>{{ $item->payreq->requestor->name }}</td>
                                <td>{{ number_format($item->realizationDetails->sum('amount'), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

@endsection