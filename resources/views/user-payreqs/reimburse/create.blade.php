@extends('templates.main')

@section('title_page')
    Payment Request
@endsection

@section('breadcrumb_title')
    approved
@endsection

@section('content')
    <div class="row">
      <div class="col-12">

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">New Payment Request - Reimburse</h3>
            <a href="{{ route('user-payreqs.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
          </div>
          <div class="card-body">
            <form action="{{ route('payreq-reimburse.store') }}" method="POST">
              @csrf

              <input type="hidden" name="form_type" value="advance">
              <div class="row">
                <div class="col-4">
                  <div class="form-group">
                    <label for="payreq_no">Payreq No <small>(auto generated)</small></label>
                    <input type="text" name="payreq_no" value="{{ $payreq_no }}" class="form-control" readonly>
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
                <label for="remarks">Purpose</label>
                <textarea name="remarks" id="remarks" cols="30" rows="2" class="form-control @error('remarks') is-invalid @enderror" autofocus>{{ old('remarks') }}</textarea>
                @error('remarks')
                <div class="invalid-feedback">
                  {{ $message }}
                </div>
                @enderror
              </div>

              <div class="card-footer">
                <div class="row">
                  <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-block" id="btn-draft"><i class="fas fa-save"></i> Add Details</button>
                  </div>
                </div>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
@endsection