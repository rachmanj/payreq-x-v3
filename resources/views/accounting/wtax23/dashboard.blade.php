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

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">DASHBOARD</h4>
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
