@extends('templates.main')

@section('title_page')
    PPN — Daftar periksa
@endsection

@section('breadcrumb_title')
    accounting / tax / ppn / periksa
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @include('accounting.tax.ppn.partials.nav', ['masaPajak' => $masaPajak])

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="form-inline">
                        <label class="mr-2">Filter masa (opsional)</label>
                        <select name="masa_pajak" class="form-control form-control-sm mr-2">
                            <option value="">— Semua —</option>
                            @foreach ($masaOptions as $opt)
                                <option value="{{ $opt }}" @selected($masaPajak === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                    </form>
                    <p class="text-muted small mb-0 mt-2">
                        Baris dengan tarif/DPP tidak dapat ditentukan, nomor faktur duplikat dalam satu masa, atau tanpa tanggal faktur.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Butuh keputusan manusia</h3></div>
                <div class="card-body">
                    <table id="ppn-periksa-table" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Masa</th>
                                <th>Jenis</th>
                                <th>Vendor/Customer</th>
                                <th>No. faktur</th>
                                <th>Masalah</th>
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
            $('#ppn-periksa-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('accounting.tax.ppn.periksa.data') }}',
                    data: { masa_pajak: '{{ $masaPajak ?? '' }}' }
                },
                columns: [
                    { data: 'masa_pajak', name: 'masa_pajak' },
                    { data: 'type_label', name: 'type' },
                    { data: 'vendor', name: 'customer_id' },
                    { data: 'faktur_no', name: 'faktur_no' },
                    { data: 'issue', orderable: false, searchable: false },
                ],
            });
        });
    </script>
@endsection
