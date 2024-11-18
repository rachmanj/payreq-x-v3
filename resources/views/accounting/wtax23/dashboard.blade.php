@extends('templates.main')

@section('title_page')
    PPh 23
@endsection

@section('breadcrumb_title')
    accounting / wtax23
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-dashboard-links page="purchase" status="outstanding" />

            <div class="row">
                <div class="col-12">
                    @include('accounting.wtax23.count')
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @include('accounting.wtax23.amount')
                </div>
            </div>

        </div>
    </div>
@endsection

<style>
    .card-header .active {
        font-weight: bold;
        color: black;
        text-transform: uppercase;
    }
</style>
