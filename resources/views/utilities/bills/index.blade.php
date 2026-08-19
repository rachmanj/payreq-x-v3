@extends('templates.main')

@section('title_page')
    Tagihan Utilitas
@endsection

@section('breadcrumb_title')
    utilities / tagihan
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-invoice-dollar"></i> Daftar Tagihan
                        </h3>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="vj-btn vj-btn-warning" data-toggle="modal"
                                data-target="#modal-copy-month">
                                <i class="fas fa-copy"></i> Copy Bulan Lalu
                            </button>
                            <a href="{{ route('utilities.bills.upload') }}" class="vj-btn vj-btn-primary">
                                <i class="fas fa-camera"></i> Upload Struk
                            </a>
                            <a href="{{ route('utilities.bills.create') }}" class="vj-btn vj-btn-success">
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
                            <div class="col-md-2">
                                <label class="small text-muted">Klaim</label>
                                <select id="filter_claimed" class="form-control form-control-sm">
                                    <option value="">Semua</option>
                                    <option value="0">Belum diklaim</option>
                                    <option value="1">Sudah diklaim</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" id="btn_filter" class="vj-btn vj-btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>

                        <table id="bills-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>ID Pelanggan</th>
                                    <th>Nama</th>
                                    <th>Jenis</th>
                                    <th>Tipe</th>
                                    <th>Periode</th>
                                    <th>Jumlah</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Token</th>
                                    <th>Status</th>
                                    <th>Payreq</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3" id="bulk-bar">
                            <div>
                                <span class="vj-chip vj-chip-neutral">Terpilih: <strong id="selected-count">0</strong> token</span>
                                <span class="vj-chip vj-chip-info">Total: <strong id="selected-total">0</strong></span>
                            </div>
                            <form action="{{ route('utilities.bills.create-payreq') }}" method="POST" id="bulk-form">
                                @csrf
                                <input type="hidden" id="bill_ids" value="">
                                <div id="bill-ids-inputs"></div>
                                <button type="submit" class="vj-btn vj-btn-success" id="btn-create-payreq" disabled>
                                    <i class="fas fa-file-invoice-dollar"></i> Buat Payreq Reimburse
                                </button>
                            </form>
                        </div>
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
                            <button type="button" class="vj-action-item" data-dismiss="modal">Batal</button>
                            <button type="submit" class="vj-btn vj-btn-primary"><i class="fas fa-copy"></i> Copy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    @include('partials.vj-soft-ui-styles')
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
                    [5, 'desc']
                ],
                ajax: {
                    url: '{{ route('utilities.bills.data') }}',
                    data: function(d) {
                        d.periode = $('#filter_periode').val();
                        d.jenis_utilitas = $('#filter_jenis').val();
                        d.project = $('#filter_project').val();
                        d.status = $('#filter_status').val();
                        d.claimed = $('#filter_claimed').val();
                    }
                },
                columns: [{
                        data: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
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
                        data: 'payreq_badge',
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

            function formatAmount(value) {
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(value);
            }

            function updateBulkBar() {
                const checked = $('.bill-checkbox:checked');
                let total = 0;
                const ids = [];

                checked.each(function() {
                    ids.push($(this).val());
                    total += parseFloat($(this).data('amount')) || 0;
                });

                $('#selected-count').text(ids.length);
                $('#selected-total').text(formatAmount(total));
                $('#bill_ids').val(ids.join(','));
                $('#bill-ids-inputs').empty();
                ids.forEach(function(id) {
                    $('#bill-ids-inputs').append(
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'bill_ids[]',
                            value: id
                        })
                    );
                });
                $('#btn-create-payreq').prop('disabled', ids.length === 0);
            }

            $('#bills-table').on('change', '.bill-checkbox', updateBulkBar);
            table.on('draw', updateBulkBar);

            $('#bulk-form').on('submit', function(e) {
                if ($('.bill-checkbox:checked').length === 0) {
                    e.preventDefault();
                }
            });

            $('#btn_filter').on('click', function() {
                table.ajax.reload();
            });
        });
    </script>
@endsection
