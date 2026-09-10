@extends('templates.main')

@section('title_page')
    Edit Kegiatan
@endsection

@section('breadcrumb_title')
    Master / Kegiatan / Edit
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('content')
    <div class="vj-show">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-edit"></i> Edit Kegiatan — {{ $activity->code }}</h3>
            </div>
            <form action="{{ route('activities.update', $activity) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @include('activities._form')
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('activities.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function () {
            $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
        });
    </script>
@endsection
