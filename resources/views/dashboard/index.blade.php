@extends('templates.main')

@section('title_page')
    Your Dashboard
@endsection

@section('breadcrumb_title')
    dashboard
@endsection

@push('styles')
    @vite('resources/css/dashboard.css')
@endpush

@section('content')
    <div class="tw-dashboard-page">
        {{-- Welcome + compact USD ticker --}}
        <div class="tw-flex tw-flex-col md:tw-flex-row md:tw-items-center md:tw-justify-between tw-gap-3 tw-mb-5">
            <div>
                <h2 class="tw-text-xl tw-font-semibold tw-text-gray-800 tw-mb-1 tw-mt-0">
                    Welcome, {{ auth()->user()->name }}
                </h2>
                <p class="tw-text-sm tw-text-gray-500 tw-mb-0">Here's what needs your attention today.</p>
            </div>
            @include('dashboard.run-text')
        </div>

        {{-- Announcements --}}
        @include('dashboard.announcements')

        {{-- Action Center: only items needing THIS user's action --}}
        @include('dashboard.action-center')

        {{-- KPI tiles (informational) --}}
        @include('dashboard.kpi-tiles')

        {{-- Main bento grid --}}
        <div class="tw-grid tw-grid-cols-1 xl:tw-grid-cols-3 tw-gap-5">
            {{-- My Work: payreqs + realizations (2 cols) --}}
            <div class="xl:tw-col-span-2">
                @include('dashboard.user-payreqs')
            </div>

            {{-- Side rail --}}
            <div class="xl:tw-col-span-1 tw-space-y-0">
                @include('dashboard.chart2')
                @include('dashboard.team')
            </div>
        </div>

        {{-- Full-width monthly chart --}}
        <div class="tw-mt-0">
            @include('dashboard.chart')
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    @include('dashboard.chart-script')
@endsection
