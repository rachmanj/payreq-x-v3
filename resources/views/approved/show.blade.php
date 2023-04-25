@extends('templates.main')

@section('title_page')
    Payment Request
@endsection

@section('breadcrumb_title')
    payreqs
@endsection

@section('content')
    <div class="row">
      <div class="col-12">

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Detail Data</h3>
            <a href="{{ route('approved.all') }}" class="btn btn-sm btn-success float-right"><i class="fas fa-undo"></i> Back</a>
          </div>
          <div class="card-body">
            
              <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="employee">Employee Name</label>
                        <input type="text" id="employee" value="{{ $payreq->employee->name }}" class="form-control" disabled>
                      </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="payreq_num">Payreq No</label>
                        <input type="text" name="payreq_num" value="{{ $payreq->payreq_num }}" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="approve_date">Approved Date</label>
                        <input type="text" name="approve_date" value="{{ date('d-m-Y', strtotime($payreq->approve_date)) }}" class="form-control" disabled>
                      </div>
                </div>
              </div>
    
              <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="payreq_type">Type</label>
                        <input type="text" name="payreq_type" value="{{ $payreq->payreq_type }}" class="form-control" disabled>
                      </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="que_group">Priority</label>
                        <input type="text" name="que_group" value="{{ $payreq->que_group }}" class="form-control" disabled>
                      </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="payreq_idr">Amount</label>
                        <input type="text" name="payreq_idr" id="payreq_idr" value="{{ number_format($payreq->payreq_idr, 2) }}" class="form-control" disabled>
                    </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="outgoing_date">Outgoing Date</label>
                        <input type="text" name="outgoing_date" value="{{ $payreq->outgoing_date ? date('d-m-Y', strtotime($payreq->approve_date)) : '-'  }}" class="form-control" disabled>
                      </div>
                </div>
                <div class="col-4">
                  <div class="form-group">
                    <label for="budgeted">is Budgeted?</label>
                    <input type="text" name="outgoing_date" value="{{ $payreq->budgeted == 1 ? 'Yes' : 'Not Yet' }}" class="form-control" disabled>
                  </div>
                </div>
                <div class="col-4">
                  <div class="form-group">
                    <label for="periode_ofr">Periode OFR</label>
                    <input type="text" name="periode_ofr" value="{{ $payreq->periode_ofr ? date('d-m-Y', strtotime($payreq->periode_ofr)) : '-' }}" class="form-control" disabled>
                  </div>
                </div>
              </div>
    
             <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="realization_date">Realization Date</label>
                        <input type="text" name="realization_date" value="{{ $payreq->realization_date ? date('d-m-Y', strtotime($payreq->realization_date)) : '-' }}" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="realization_num">Realization No</label>
                        <input type="text" name="realization_num" value="{{ $payreq->realization_num }}" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="realization_amount">Realization Amount</label>
                        <input type="text" name="realization_amount" id="realization_amount" value="{{ $payreq->realization_amount ? number_format($payreq->realization_amount, 2) : '-' }}" class="form-control" disabled>
                    </div>
                </div>
             </div>

              <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="verify_date">Verification Date</label>
                        <input type="text" name="verify_date" value="{{ $payreq->verify_date ? date('d-m-Y', strtotime($payreq->verify_date)) : '-' }}" class="form-control" disabled>
                    </div>
                </div>
                <div class="col-8">
                    <div class="form-group">
                        <label for="rab_id">RAB No</label>
                        <input type="text" name="rab_id" value="{{ $payreq->rab->rab_no }}" class="form-control" disabled>
                    </div>
                </div>
              </div>
    
              <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea name="remarks" id="remarks" cols="30" rows="2" class="form-control" disabled>{{ $payreq->remarks }}</textarea>
                    </div>
                </div>
              </div>

              <div class="card-footer">
              </div>

          </div>
        </div>
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
</script>
@endsection