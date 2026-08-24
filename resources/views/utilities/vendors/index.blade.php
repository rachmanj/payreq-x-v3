@extends('templates.main')

@section('title_page')
    Mapping Vendor Utilities
@endsection

@section('breadcrumb_title')
    utilities / vendor mapping
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-link"></i> Mapping Vendor SAP per Jenis Utilitas
                        </h3>
                    </div>
                    <form action="{{ route('utilities.vendors.update') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <p class="text-muted small">
                                Satu jenis utilitas terhubung ke satu SAP Business Partner (supplier). Mapping ini dipakai
                                sebagai CardCode saat membuat AP Invoice.
                            </p>
                            @foreach ($vendors as $vendor)
                                @php
                                    $jenis = $vendor->jenis_utilitas;
                                    $label = $jenisList[$jenis] ?? strtoupper($jenis);
                                @endphp
                                <div class="form-group">
                                    <label for="vendors_{{ $jenis }}">{{ $label }}</label>
                                    <select name="vendors[{{ $jenis }}]" id="vendors_{{ $jenis }}"
                                        class="form-control select2bs4">
                                        <option value="">Belum di-mapping</option>
                                        @foreach ($partners as $partner)
                                            <option value="{{ $partner->id }}"
                                                {{ (string) old("vendors.$jenis", $vendor->sap_business_partner_id) === (string) $partner->id ? 'selected' : '' }}>
                                                {{ $partner->code }} — {{ $partner->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="vj-btn vj-btn-primary">
                                <i class="fas fa-save"></i> Simpan Mapping
                            </button>
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
