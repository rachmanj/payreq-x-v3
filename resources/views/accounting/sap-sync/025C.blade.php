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

            <x-sync-links page="025C" />

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div class="mb-2 mb-md-0">
                        <label class="mb-0 font-weight-bold">
                            Selected:
                            <span class="badge badge-primary" id="bulk-selected-count">0</span>
                        </label>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <input type="checkbox" id="bulk-select-all">
                            <label for="bulk-select-all" class="mb-0">Select All (visible)</label>
                        </div>
                        <form id="bulk-submit-form" action="{{ route('accounting.sap-sync.bulk_submit') }}" method="POST"
                            class="mb-0">
                            @csrf
                            <div id="bulk-selected-inputs"></div>
                            <button type="submit" class="btn btn-success" id="bulk-submit-btn" disabled>
                                <i class="fas fa-paper-plane"></i> Submit Selected to SAP B1
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <table id="verifications" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="bulk-select-all-header">
                                </th>
                                <th>#</th>
                                <th>VerificationJ No</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>IDR</th>
                                <th>SAPJ No</th>
                                <th>Sync at</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />

    <style>
        .card-header .active {
            font-weight: bold;
            color: black;
            text-transform: uppercase;
        }
    </style>
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>
    @include('accounting.sap-sync.partials.bulk-table-script', [
        'dataRoute' => route('accounting.sap-sync.data', ['project' => '025C']),
    ])
@endsection
