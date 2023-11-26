@extends('templates.main')

@section('title_page')
    Cashier's Dashboard
@endsection

@section('breadcrumb_title')
    cashier / dashboard
@endsection

@section('content')
    {{-- <h3>Dashboard</h3> --}}
    <div class="row">
        <div class="col-12">
            <div class="row">
                @include('cashier.dashboard.info')
                @include('cashier.dashboard.tx')
            </div>
        </div>
    </div>
@endsection