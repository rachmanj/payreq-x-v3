@extends('templates.main')

@section('title_page')
    Detail Biaya Kegiatan — {{ $activity->code }}
@endsection

@section('breadcrumb_title')
    Laporan / Biaya per Kegiatan / {{ $activity->code }}
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('content')
    <div class="vj-show">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="card-title mb-0"><i class="fas fa-tasks"></i> {{ $activity->name }}</h3>
                <div class="d-flex flex-wrap gap-2">
                    @if ($activity->status === 'open')
                        <button type="button" id="btn-close-activity" class="vj-btn vj-btn-warning">
                            <i class="fas fa-lock"></i> Tutup Kegiatan
                        </button>
                    @endif
                    <a href="{{ route('reports.activity-costing.index', array_filter($filters)) }}" class="vj-action-item vj-action-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>Kode:</strong> {{ $activity->code }}</div>
                    <div class="col-md-3"><strong>Periode:</strong> {{ $activity->periode }}</div>
                    <div class="col-md-3"><strong>Project:</strong> {{ $activity->project ?? 'Semua' }}</div>
                    <div class="col-md-3"><strong>Status:</strong>
                        @if ($activity->status === 'open')
                            <span class="badge badge-success">Open</span>
                        @else
                            <span class="badge badge-dark">Closed</span>
                        @endif
                    </div>
                    <div class="col-md-6 mt-2"><strong>Mode:</strong>
                        {{ $activity->mode === 'reklasifikasi' ? 'Reklasifikasi' : 'Tanpa Reklasifikasi' }}
                    </div>
                    <div class="col-md-6 mt-2"><strong>Akun Kegiatan:</strong>
                        @if ($activity->account)
                            {{ $activity->account->account_number }} — {{ $activity->account->account_name }}
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-secondary mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-receipt"></i> Daftar Nota</h3>
            </div>
            <div class="card-body">
                <table id="nota-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Nomor Realisasi</th>
                            <th>Deskripsi</th>
                            <th>Akun Asli</th>
                            <th>Cost Center</th>
                            <th>Jumlah</th>
                            <th>Reklasifikasi?</th>
                            <th>Status VJ / SAP</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-book"></i> Daftar Baris Jurnal (VJ)</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Baris jurnal berdasarkan <code>verification_journal_details.activity_id</code>. VJ lama tanpa mapping kegiatan tidak ditampilkan di sini.</p>
                <table id="journal-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Akun (Agregasi)</th>
                            <th>Cost Center</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Memo</th>
                            <th>Nomor VJ</th>
                            <th>Status Posting SAP</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @if ($activity->status === 'open')
        <form id="close-activity-form" action="{{ route('reports.activity-costing.close', $activity->id) }}" method="POST" class="d-none">
            @csrf
        </form>
    @endif
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(function () {
            const filterParams = @json(array_filter($filters));

            function ajaxData(extra) {
                return Object.assign({}, filterParams, extra || {});
            }

            $('#nota-table').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route('reports.activity-costing.show-data', $activity->id) }}',
                    data: function (d) {
                        Object.assign(d, ajaxData({ type: 'notas' }));
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'expense_date' },
                    { data: 'realization_link' },
                    { data: 'description' },
                    { data: 'account_label' },
                    { data: 'cost_center' },
                    { data: 'amount', className: 'text-right' },
                    { data: 'reklasifikasi' },
                    { data: 'vj_status' },
                ]
            });

            $('#journal-table').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route('reports.activity-costing.show-data', $activity->id) }}',
                    data: function (d) {
                        Object.assign(d, ajaxData({ type: 'journals' }));
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'account_label' },
                    { data: 'cost_center' },
                    { data: 'debit_amount', className: 'text-right' },
                    { data: 'credit_amount', className: 'text-right' },
                    { data: 'description' },
                    { data: 'vj_nomor' },
                    { data: 'sap_status' },
                ]
            });

            $('#btn-close-activity').on('click', function () {
                Swal.fire({
                    title: 'Tutup Kegiatan?',
                    text: 'Kegiatan yang sudah ditutup tidak dapat ditag lagi pada realisasi baru.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f0ad4e',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tutup',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#close-activity-form').submit();
                    }
                });
            });
        });
    </script>
@endsection
