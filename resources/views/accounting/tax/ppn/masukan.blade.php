@extends('templates.main')

@section('title_page')
    PPN Masukan — Register
@endsection

@section('breadcrumb_title')
    accounting / tax / ppn / masukan
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @include('accounting.tax.ppn.partials.nav', ['masaPajak' => $masaPajak])

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="form-inline">
                        <label class="mr-2">Masa pajak</label>
                        <select name="masa_pajak" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            @foreach ($masaOptions as $opt)
                                <option value="{{ $opt }}" @selected($opt === $masaPajak)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Register PPN Masukan — {{ $masaPajak }}</h3></div>
                <div class="card-body">
                    <table id="ppn-masukan-table" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th>Faktur</th>
                                <th>DPP / PPN</th>
                                <th>Tarif</th>
                                <th>Validasi</th>
                                <th>Coretax</th>
                                <th>SAP</th>
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
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script>
        $(function () {
            $('#ppn-masukan-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('accounting.tax.ppn.masukan.data') }}',
                    data: { masa_pajak: '{{ $masaPajak }}' }
                },
                columns: [
                    { data: 'vendor', name: 'customer_id', orderable: false },
                    { data: 'faktur', name: 'faktur_no', orderable: false },
                    { data: 'amount', name: 'ppn', orderable: false },
                    { data: 'ppn_rate_display', name: 'ppn_rate' },
                    { data: 'validation_label', name: 'validation_status', orderable: false },
                    { data: 'coretax_label', name: 'coretax_status', orderable: false },
                    { data: 'sap_match', orderable: false, searchable: false },
                    { data: 'actions', orderable: false, searchable: false },
                ],
                order: [[1, 'asc']],
            });
        });
    </script>
@endsection
