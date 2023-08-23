@extends('templates.main')

@section('title_page')
    My Payreq
@endsection

@section('breadcrumb_title')
    realization
@endsection

@section('content')
    <div class="row">
      <div class="col-12">

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">New Realization Payreq</h3>
            <a href="{{ route('user-payreqs.realizations.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
          </div>
          <div class="card-body">
            <form action="{{ route('user-payreqs.realizations.store') }}" method="POST">
              @csrf

              <div class="row">
                <div class="col-4">
                  <div class="form-group">
                    <label for="realization_no">Realization No <small>(auto generated)</small></label>
                    <input type="text" name="realization_no" value="{{ $realization_no }}" class="form-control" readonly>
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

              <div class="row">
                <div class="col-6">
                  <div class="form-group">
                      <label for="payreq_no">Payment Request No</label>
                      <select name="payreq_no" class="form-control">
                        @foreach ($user_payreqs as $payreq)
                          <option value="{{ $payreq->id }}">Payreq No.{{ $payreq->payreq_no }} | Amount: IDR {{ $payreq->amount }} </option>
                        @endforeach
                      </select>
                  </div>
                </div>
                <div class="col-3">
                  <div class="form-group">
                    <label for="realization_amount">Realization Amount</label>
                    <input type="text" name="realization_amount" value="" class="form-control" readonly>
                  </div>
                </div>
                <div class="col-3">
                  <div class="form-group">
                    <label for="variant">Variant</label>
                    <input type="text" name="variant" value="" class="form-control" readonly>
                  </div>
                </div>
              </div>
            </div>

              {{-- REALIZATION DETAILS --}}
              <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                  Realization Details
                </h3>
              </div>

              <div class="card-body">
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Description</th>
                      <th>Position</th>
                      <th>Email</th>
                      <th>Phone</th>
                    </tr>
                  </thead>
                </table>
              </div>
            </div>

              <div class="card-footer">
                <div class="row">
                  <div class="col-6">
                    <button type="submit" class="btn btn-primary btn-block" id="btn-draft"><i class="fas fa-save"></i> Save as Draft</button>
                  </div>
                  <div class="col-6">
                    <button type="submit" class="btn btn-warning btn-block" id="btn-submit"><i class="fas fa-paper-plane"></i> Save and Submit</button>
                  </div>
                </div>
              </div>
            </form>

          </div> {{-- card -body --}}
       
      </div>
    </div>
@endsection

@section('styles')
  <!-- Select2 -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
<!-- Select2 -->
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  }) 

  // btn-save as draft
  $('#btn-draft').click(function() {
    // add attribute name="draft" to form
    $('form').append('<input type="hidden" name="draft" value="1">');
  });

  // btn-save and submit
  $('#btn-submit').click(function() {
    // add attribute name="draft" to form
    $('form').append('<input type="hidden" name="draft" value="0">');
  });
</script>
@endsection