@extends('templates.main')

@section('title_page')
    Your Dashboard
@endsection

@section('breadcrumb_title')
    dashboard
@endsection

@section('content')
    <div class="row">
        {{-- <h3>Welcome to Payreq Sytem, {{ auth()->user()->name }} ... </h3> --}}
        @include('dashboard.run-text')
    </div>

    @hasanyrole('admin|superadmin|cashier|cashier_bo|cashier_017|cashier_021|cashier_022|cashier_025|cashier_023')
        <div class="row">
            @include('dashboard.pengumuman')
        </div>
    @endhasanyrole

    <div class="row">
        @can('akses_approvals')
            @include('dashboard.row1')
        @endcan
    </div>

    <div class="row">
        @include('dashboard.row2')
    </div>

    <div class="row">
        @include('dashboard.user-payreqs')
    </div>

    <div class="row">
        @include('dashboard.chart2')
    </div>

    <div class="row">
        @include('dashboard.team')
    </div>

    <div class="row">
        @include('dashboard.chart')
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    @include('dashboard.chart-script')
@endsection
