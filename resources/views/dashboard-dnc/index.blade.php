@extends('templates.main')

@section('title_page')
    DnC Dashboard
@endsection

@section('breadcrumb_title')
    dashboard
@endsection

@section('content')

    {{-- ROW 1 --}}
    <div class="row">
        @include('dashboard-dnc.total-outgoings')
    </div>
    {{-- END ROW 1 --}}

    {{-- ROW 2 --}}
    <div class="row">
        <div class="col-3">
            @include('dashboard-dnc.outs-by-month')
        </div>
        <div class="col-3">
            {{-- @include('dashboard-dnc.release-byproject') --}}
        </div>
    </div>
    {{-- END ROW 2 --}}
    
    {{-- ROW 3 --}}
    
    {{-- END ROW 3 --}}

    {{-- ROW 4 --}}
    
    {{-- END ROW 4 --}}

    {{-- ROW 5 --}}
    
    {{-- END ROW 5 --}}



    {{-- @include('accounting-dashboard.row-3') --}}

@endsection-+o ++++-