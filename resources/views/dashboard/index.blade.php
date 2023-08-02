@extends('templates.main')

@section('title_page')
    Dashboard
@endsection

@section('breadcrumb_title')
    dashboard
@endsection

@section('content')

    <div class="row">
        @can('akses_approvals')
            @include('dashboard.row1')
        @endcan
    </div>

    <div class="row">
      <h3>Welcome to the jungle, {{ auth()->user()->name }} ... </h3>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
@endsection