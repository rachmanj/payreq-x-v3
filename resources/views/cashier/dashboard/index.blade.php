@extends('templates.main')

@section('title_page')
    Cashier's Dashboard
@endsection

@section('breadcrumb_title')
    cashier / dashboard
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="row">
                    @include('cashier.dashboard.info')
                    @include('cashier.dashboard.tx')
                </div>
                <div class="row">
                    @include('cashier.dashboard.pc-balance')
                </div>
                <div class="row">
                    @include('cashier.dashboard.ongoing-by-user')
                </div>
                @include('partials.clearing-accounts-section', [
                    'clearing_cards' => $clearing_cards,
                    'clearing_transactions_route' => 'cashier.dashboard.clearing.transactions',
                ])
            </div>
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
@endsection
