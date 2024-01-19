@extends('templates.main')

@section('title_page')
  Equipments
@endsection

@section('breadcrumb_title')
    equipments
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card racd-info">
      <div class="card-header">
        <h3 class="card-title">Equipment Synchronization</h3>
      </div>
      <div class="form-horizontal">
        <div class="card-body">
            <p style="color: blue;">This action is to synchronize Equipments belongs to ARK-Fleet server with this Payreq-X.</p>
          <div class="form-group row">
            <label class="col-sm-4 col-form-label">Equipments ARK-Fleet count: </label>
            <div class="col-sm-6">
              <input type="text" class="form-control" value="{{ $api_count }}" readonly>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-4 col-form-label">Equipments local count: </label>
            <div class="col-sm-6">
              <input type="text" class="form-control" value="{{ $local_count }}" readonly>
            </div>
          </div>
        </div>
        <div class="card-footer text-center">
          <a href="{{ route('equipments.sync.sync_equipments') }}" type="submit" class="btn btn-info" onclick="return confirm('Are you sure you want to sync?')" style="width: 60%">Synchronize</a>
        </div>
      </div>
    </div> 
  </div>
</div>

@endsection