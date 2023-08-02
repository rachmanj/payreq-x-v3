@extends('templates.main')

@section('title_page')
    Payreqs Dashboard
@endsection

@section('breadcrumb_title')
    dashboard
@endsection

@section('content')

    {{-- ROW GENERAL --}}
    <div class="row">
        {{-- @include('accounting-dashboard.total-this-month-outs') --}}
        @include('accounting-dashboard.rekaps')
    </div>
    {{-- END GENERAL --}}

    {{-- ROW DNC --}}
    <div class="row">
        @include('accounting-dashboard.rekaps_dnc')
    </div>
    {{-- END DNC --}}

    {{-- ROW ACTIVITY-CHART --}}
    <div class="row">
      <div class="col-8">
          @include('accounting-dashboard.chart-activity')
      </div>
      <div class="col-4">
            @include('accounting-dashboard.not-budgeted')
      </div>
  </div>
  {{-- END ROW ACTIVITY-CHART --}}

    {{-- ROW 2 --}}
    <div class="row">
        <div class="col-12">
            @include('accounting-dashboard.personel-activities')
        </div>
    </div>
    {{-- END ROW 2 --}}
    
    {{-- ROW 3 --}}
    <div class="row">
        <div class="col-6">
            @include('accounting-dashboard.monthly-outgoing')
        </div>
        <div class="col-4">
            {{-- @include('accounting-dashboard.not-budgeted') --}}
        </div>
    </div>
    {{-- END ROW 3 --}}

    {{-- ROW 5 CHART --}}
    <div class="row">
        <div class="col-12">
            @include('accounting-dashboard.chart')
        </div>
    </div>
    {{-- END ROW 5 CHART --}}

    {{-- LINE CHART OUTGOING BY CATEGORY
    <div class="row">
        <div class="col-12">
            @include('accounting-dashboard.chart-outgoing-by-category')
        </div>
    </div>
     END LINE CHART OUTGOING BY CATEGORY --}}

    {{-- ROW 4 --}}
    <div class="row">
        <div class="col-12">
            @include('accounting-dashboard.adv-by-dept')
        </div>
    </div>
    {{-- END ROW 4 --}}

    {{-- ROW 5 --}}
    <div class="row">
        <div class="col-12">
            @include('accounting-dashboard.adv-by-category')
        </div>
    </div>
    {{-- END ROW 5 --}}

    

    {{-- @include('accounting-dashboard.row-3') --}}

@endsection

@section('scripts')
<script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
<script>
    // tooltip
    $(function () {
      $('[data-toggle="tooltip"]').tooltip()
    })
</script>

 {{-- CHART SCRIPT --}}
 @include('accounting-dashboard.chart-script')

@endsection