@extends('templates.main')

@section('title_page')
    PPN Keluaran — Register
@endsection

@section('breadcrumb_title')
    accounting / tax / ppn / keluaran
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @include('accounting.tax.ppn.partials.nav', ['masaPajak' => $masaPajak])

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
                <div class="card-header"><h3 class="card-title">Register PPN Keluaran — {{ $masaPajak }}</h3></div>
                <div class="card-body">
                    <table id="ppn-keluaran-table" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Faktur pajak</th>
                                <th>DPP / PPN</th>
                                <th>Kirim SAP</th>
                                <th>Coretax</th>
                                <th>Umur (hari)</th>
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
            $('#ppn-keluaran-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('accounting.tax.ppn.keluaran.data') }}',
                    data: { masa_pajak: '{{ $masaPajak }}' }
                },
                columns: [
                    { data: 'customer_name', name: 'customer_id', orderable: false },
                    { data: 'faktur', name: 'faktur_no', orderable: false },
                    { data: 'amount', name: 'ppn', orderable: false },
                    { data: 'sap_status', orderable: false, searchable: false },
                    { data: 'coretax_label', name: 'coretax_status', orderable: false },
                    { data: 'umur_hari', name: 'faktur_date' },
                ],
                order: [[5, 'desc']],
            });
        });
    </script>
@endsection
