@extends('templates.main')

@section('title_page')
    Edit ID Pelanggan
@endsection

@section('breadcrumb_title')
    utilities / id pelanggan / edit
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-edit"></i> Edit ID Pelanggan
                        </h3>
                        <a href="{{ route('utilities.customers.index') }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                <form action="{{ route('utilities.customers.update', $customer->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="card-body">
                        @include('utilities.customers.form', ['customer' => $customer])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="vj-btn vj-btn-primary"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });
        });
    </script>
@endsection
