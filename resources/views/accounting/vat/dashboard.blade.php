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

            <x-vat-links page="purchase" status="outstanding" />

            <div class="row">
                <div class="col-12">
                    @include('accounting.vat.count')
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @include('accounting.vat.amount')
                </div>
            </div>

        </div>
    </div>
@endsection

<style>
    .card-header .active {
        /* font-weight: bold; */
        color: black;
        text-transform: uppercase;
    }
</style>
