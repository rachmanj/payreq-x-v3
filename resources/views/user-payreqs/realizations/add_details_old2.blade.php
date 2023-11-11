@extends('templates.main')

@section('title_page')
    Realization Payreq
@endsection

@section('breadcrumb_title')
    realization
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

{{-- DETAILS SECTION --}}
@include('user-payreqs.realizations.add_details_form')

@include('user-payreqs.realizations.add_details_table')
{{-- END DETAILS SECTION --}}

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
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })

    // get equipments list using ajax
    // let url  = "{{ env('URL_ARKFLEET')}}/equipments"


    $.ajax({
      url: "{{ env('URL_ARKFLEET')}}/equipments",
          type: "GET",
          success: function(data, status) {
            
            let equipments = data.data
            
            //  get value of project_equipment from controller 
            let project_equipment = "{{ $project_equipment }}"
        
            let filtered_equipments = []
            if (project_equipment == 'all') {
              filtered_equipments = equipments
            } else {
              filtered_equipments = equipments.filter(equipment => equipment.project == project_equipment)
            }
        
            let select = document.getElementById('unit_no')

            for (let i = 0; i < filtered_equipments.length; i++) {
              let equipment = filtered_equipments[i]
              let option = document.createElement('option')
              option.value = equipment.unit_code
              option.text = equipment.unit_code + ' - ' + equipment.plant_group + ' - ' + equipment.model
              select.add(option)
            }

            // let selected equipment
            let unit_no = "{{ old('unit_no') }}"

            if (unit_no) {
              $('#unit_no').val(unit_no).trigger('change')
            }

          },
          error: function(xhr, status, error) {
            let select = document.getElementById('unit_no')
            let option = document.createElement('option')
            option.value = ""
            option.text = "Server Error"
            select.add(option)
          }
    })
})
</script>
@endsection
