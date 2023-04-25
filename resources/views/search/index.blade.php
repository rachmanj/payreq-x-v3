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
              <input type="text" name="payreq_no" class="form-control rounded-0">
              <span class="input-group-append">
                <button type="submit" class="btn btn-success btn-flat">Go!</button>
              </span>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection