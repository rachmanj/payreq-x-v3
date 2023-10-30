@extends('templates.main')

@section('title_page')
    Verifications
@endsection

@section('breadcrumb_title')
    verifications
@endsection

@section('content')
<div class="row">
  <div class="col-sm-3 col-6">
    <div class="description-block border-right">
        <h5 class="description-header">Realization No</h5>
        <span class="description-text">{{ $realization->nomor }}</span>
    </div>
  </div>
  <div class="col-sm-3 col-6">
    <div class="description-block border-right">
      <h5 class="description-header">Payreq No</h5>
      <span class="description-text">{{ $realization->payreq->nomor }}</span>
    </div>
  </div>
  <div class="col-sm-3 col-6">
    <div class="description-block border-right">
      <h5 class="description-header">Payreq Amount</h5>
      <span class="description-text">{{ number_format($realization->payreq->amount, 2) }}</span>
    </div>
  </div>
  <div class="col-sm-3 col-6">
    <div class="description-block">
      <h5 class="description-header">Realization Amount</h5>
      <span class="description-text">{{ $realization_details->count() > 0 ? number_format($realization_details->sum('amount'), 2) : '0' }}</span>
    </div>
  </div>
</div>
<!-- /.row -->

@include('verifications.details_table')

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
<script>

// map $reazliation_details
@foreach ($realization_details as $item)
  $('#account_number_{{ $item->id }}').on('change', function () {
    var account_number_{{ $item->id }} = $('#account_number_{{ $item->id }}').val();
    
      $.ajax({
        url: '{{ route('get_account_name') }}',
        type: 'POST',
        dataType: 'json',
        data: {
          account_number: account_number_{{ $item->id }},
          realization_detail_id: {{ $item->id }},
          _token: '{{ csrf_token() }}'
        },
        success: function(data) {
          $('#account_name_{{ $item->id }}').val(data);
        }
      });
  })
@endforeach


</script>
@endsection