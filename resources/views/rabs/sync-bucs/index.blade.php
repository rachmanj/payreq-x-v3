@extends('templates.main')

@section('title_page')
  RABs
@endsection

@section('breadcrumb_title')
    rabs
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card racd-info">
      <div class="card-header">
        <h3 class="card-title">RABs BUC Sync</h3>
      </div>
      <div class="form-horizontal">
        <div class="card-body">
          <div class="form-group row">
            <label class="col-sm-4 col-form-label">RABs Payreq-Support count: </label>
            <div class="col-sm-6">
              <input type="text" class="form-control" value="{{ $rab_count }}" readonly>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-4 col-form-label">RABs Payreq-X count: </label>
            <div class="col-sm-6">
              <input type="text" class="form-control" value="{{ $local_count }}" readonly>
            </div>
          </div>
        </div>
        <div class="card-footer text-center">
          <a href="{{ route('rabs.sync.sync_rabs') }}" type="submit" class="btn btn-info" onclick="return confirm('Are you sure you want to sync?')" style="width: 60%">Synchronize</a>
        </div>
      </div>
    </div> 
  </div>
</div>

@endsection