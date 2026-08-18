@extends('templates.main')

@section('title_page')
    Tambah ID Pelanggan
@endsection

@section('breadcrumb_title')
    utilities / id pelanggan / tambah
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tambah ID Pelanggan</h3>
                    <a href="{{ route('utilities.customers.index') }}" class="btn btn-sm btn-default float-right">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <form action="{{ route('utilities.customers.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @include('utilities.customers.form')
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
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
