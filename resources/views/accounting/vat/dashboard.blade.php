@extends('templates.main')

@section('title_page')
    VAT
@endsection

@section('breadcrumb_title')
    accounting / vat
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-vat-links page="dashboard" status="incomplete" />

            <div class="row">
                <div class="col-12">
                    @include('accounting.vat.dashboard.count')
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @include('accounting.vat.dashboard.amount')
                </div>
            </div>

        </div>
    </div>
@endsection

@section('styles')
    <style>
        .card-header .active {
            /* font-weight: bold; */
            color: black;
            text-transform: uppercase;
        }
    </style>
@endsection
