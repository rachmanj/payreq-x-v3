@extends('templates.main')

@section('title_page')
    Incoming Payment
@endsection

@section('breadcrumb_title')
    cashier / incoming / create
@endsection

@section('content')
<div class="vj-show">
<div class="row">
    <div class="col-12">

      <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <h3 class="card-title mb-0"><i class="fas fa-plus-circle"></i> New Incoming Payment</h3>
          <a href="{{ route('cashier.incomings.index') }}" class="vj-action-item vj-action-back"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
          <form action="{{ route('cashier.incomings.store') }}" method="POST">
            @csrf

            <input type="hidden" name="form_type" value="advance">
            <div class="row">
              <div class="col-4">
                <div class="form-group">
                  <label for="payreq_no">Cashier's Name</small></label>
                  <input type="text" name="cashier_name" class="form-control" value="{{ auth()->user()->name }}" readonly>
                </div>
              </div>
              <div class="col-4">
                <div class="form-group">
                  <label for="project">Project</label>
                  <input type="text" name="project" value="{{ auth()->user()->project }}" class="form-control" readonly>
                </div>
              </div>
              <div class="col-4">
                <div class="form-group">
                  <label for="department">Department</label>
                  <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                  <input type="text" name="department" value="{{ auth()->user()->department->department_name}}" class="form-control" readonly>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="remarks">Description</label>
              <textarea name="description" id="description" cols="30" rows="2" class="form-control @error('description') is-invalid @enderror" autofocus>{{ old('description') }}</textarea>
              @error('description')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
              @enderror
            </div>

            <div class="row align-items-center">
              <div class="col-6">
                <div class="form-group">
                  <label for="amount">Amount</label>
                  <input type="text" name="amount" id="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror">
                  @error('amount')
                  <div class="invalid-feedback">
                    {{ $message }}
                  </div>
                  @enderror
                </div>
              </div>
              
              <input type="hidden" name="test" value="ninja">
              <div class="col-6">
                <div class="form-group">
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="customSwitch1" name="will_post">
                    <label class="custom-control-label" for="customSwitch1">Don't Show this in Incoming Journal Creation</label>
                  </div>
                </div>
              </div>
            </div>
            

            

            <div class="card-footer">
              <div class="row">
                <div class="col-6">
                  <button type="submit" class="vj-btn vj-btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
              </div>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection
