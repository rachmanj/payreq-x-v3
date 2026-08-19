@extends('templates.main')

@section('title_page')
    Tagihan Utilitas
@endsection

@section('breadcrumb_title')
    utilities / tagihan
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Tagihan</h3>
                    <div class="float-right">
                        <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal"
                            data-target="#modal-copy-month">
                            <i class="fas fa-copy"></i> Copy Bulan Lalu
                        </button>
                        <a href="{{ route('utilities.bills.upload') }}" class="btn btn-sm btn-info">
                            <i class="fas fa-camera"></i> Upload Struk
                        </a>
                        <a href="{{ route('utilities.bills.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Tambah Tagihan
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="small text-muted">Periode</label>
                            <input type="month" id="filter_periode" class="form-control form-control-sm"
                                value="{{ $periode }}">
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted">Jenis</label>
                            <select id="filter_jenis" class="form-control form-control-sm">
                                <option value="">Semua</option>
                                @foreach ($jenisList as $key => $label)
                                    <option value="{{ $key }}" @selected($jenis === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted">Project</label>
                            <select id="filter_project" class="form-control form-control-sm">
                                <option value="">Semua</option>
                                @foreach ($projects as $proj)
                                    <option value="{{ $proj->code }}" @selected($project === $proj->code)>{{ $proj->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted">Status</label>
                            <select id="filter_status" class="form-control form-control-sm">
                                <option value="">Semua</option>
                                <option value="belum" @selected($status === 'belum')>Belum</option>
                                <option value="mendekati" @selected($status === 'mendekati')>Jatuh Tempo</option>
                                <option value="telat" @selected($status === 'telat')>Telat</option>
                                <option value="lunas" @selected($status === 'lunas')>Lunas</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" id="btn_filter" class="btn btn-sm btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>

                    <table id="bills-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID Pelanggan</th>
                                <th>Nama</th>
                                <th>Jenis</th>
                                <th>Tipe</th>
                                <th>Periode</th>
                                <th>Jumlah</th>
                                <th>Jatuh Tempo</th>
                                <th>Token</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-copy-month">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Copy Tagihan Bulan Lalu</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('utilities.bills.copy-last-month') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="periode_sumber">Periode Sumber</label>
                            <input type="month" name="periode_sumber" id="periode_sumber" class="form-control"
                                value="{{ $periode_sumber_default }}" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="periode_target">Periode Target</label>
                            <input type="month" name="periode_target" id="periode_target" class="form-control"
                                value="{{ $periode_target_default }}" required>
                        </div>
                        <p class="text-muted small mt-2 mb-0">
                            Hanya tagihan pascabayar yang disalin. Token/prabayar dilewati.
                            Tagihan yang sudah ada di periode target akan dilewati.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-copy"></i> Copy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script>
        $(function() {
            const table = $('#bills-table').DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [4, 'desc']
                ],
                ajax: {
                    url: '{{ route('utilities.bills.data') }}',
                    data: function(d) {
                        d.periode = $('#filter_periode').val();
                        d.jenis_utilitas = $('#filter_jenis').val();
                        d.project = $('#filter_project').val();
                        d.status = $('#filter_status').val();
                    }
                },
                columns: [{
                        data: 'id_pelanggan',
                        name: 'utility_customers.id_pelanggan'
                    },
                    {
                        data: 'nama_customer',
                        name: 'utility_customers.nama'
                    },
                    {
                        data: 'jenis_utilitas',
                        name: 'utility_customers.jenis_utilitas'
                    },
                    {
                        data: 'tipe_badge',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'periode',
                        name: 'utility_bills.periode'
                    },
                    {
                        data: 'jumlah_tagihan',
                        name: 'utility_bills.jumlah_tagihan',
                        className: 'text-right'
                    },
                    {
                        data: 'tanggal_jatuh_tempo',
                        name: 'utility_bills.tanggal_jatuh_tempo'
                    },
                    {
                        data: 'nomor_token_display',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status_badge',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
            });

            $('#btn_filter').on('click', function() {
                table.ajax.reload();
            });
        });
    </script>
@endsection
