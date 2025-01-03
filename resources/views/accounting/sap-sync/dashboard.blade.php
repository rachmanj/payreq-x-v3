@extends('templates.main')

@section('title_page')
    SAP Sync
@endsection

@section('breadcrumb_title')
    accounting / sap-sync
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-sync-links page="dashboard" />

            @include('accounting.sap-sync._count-by-user')

            @include('accounting.sap-sync._count-by-project')

        </div>
    </div>
@endsection

@section('styles')
    <style>
        .card-header .active {
            font-weight: bold;
            color: black;
            text-transform: uppercase;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(function() {
            // 
        });
    </script>
@endsection
