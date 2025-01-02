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

            <div class="card">
                <div class="card-header">
                    <h3>Dashboard</h3>
                </div>
                <div class="card-body">
                    {{--  --}}
                </div>
            </div>

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
