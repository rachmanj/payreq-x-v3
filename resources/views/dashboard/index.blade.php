@extends('templates.main')

@section('title_page')
    User's Dashboard
@endsection

@section('breadcrumb_title')
    dashboard
@endsection

@section('content')

    <div class="row">
        <h3>Welcome to Payreq Sytem, {{ auth()->user()->name }} ... </h3>
    </div>

    <div class="row">
        @can('akses_approvals')
            @include('dashboard.row1')
        @endcan
    </div>

    <div class="row">
        @include('dashboard.user-payreqs')
    </div>

    
@endsection

@section('scripts')
<script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
@endsection