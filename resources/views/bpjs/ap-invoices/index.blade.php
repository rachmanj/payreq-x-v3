@extends('templates.main')

@section('title_page')
    AP Invoice BPJS
@endsection

@section('breadcrumb_title')
    accounting / ap-invoice-bpjs
@endsection

@section('content')
    <div class="vj-show">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="card-title mb-0">
                    <i class="fas fa-file-invoice"></i> AP Invoice BPJS
                </h3>
                @if ($canSubmit)
                    <button type="button" class="vj-btn vj-btn-primary" data-toggle="modal" data-target="#createBpjsModal">
                        <i class="fas fa-plus"></i>
                        <span>Buat AP Invoice</span>
                    </button>
                @endif
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Jenis</label>
                        <select id="filter_jenis" class="form-control form-control-sm">
                            <option value="">Semua</option>
                            @foreach ($jenisLabels as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Unit</label>
                        <select id="filter_unit" class="form-control form-control-sm">
                            <option value="">Semua</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->code }}">{{ $project->code }} — {{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Periode</label>
                        <input type="month" id="filter_periode" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted mb-1">Status</label>
                        <select id="filter_status" class="form-control form-control-sm">
                            <option value="">Semua</option>
                            <option value="pending">Pending</option>
                            <option value="posted">Posted</option>
                            <option value="failed">Failed</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="vj-btn vj-btn-primary mr-2" id="btnApplyFilter">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <button type="button" class="vj-btn vj-btn-warning" id="btnResetFilter">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped table-sm mb-0" id="bpjsInvoicesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Unit</th>
                                <th>Nominal</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>No. SAP</th>
                                <th>Dikirim</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($canSubmit)
        @include('bpjs.ap-invoices.partials.create-modal')
    @endif
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    @include('partials.vj-soft-ui-swal')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            const table = $('#bpjsInvoicesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('bpjs-ap-invoices.data') }}',
                    data: function(d) {
                        d.jenis = $('#filter_jenis').val();
                        d.unit = $('#filter_unit').val();
                        d.periode = $('#filter_periode').val();
                        d.status = $('#filter_status').val();
                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'periode', name: 'periode' },
                    { data: 'jenis_badge', name: 'jenis', orderable: false, searchable: false },
                    { data: 'unit_label', name: 'unit', orderable: false },
                    { data: 'amount', name: 'amount', className: 'text-right' },
                    { data: 'dates', name: 'doc_date', orderable: false, searchable: false },
                    { data: 'status_chip', name: 'status', orderable: false, searchable: false },
                    { data: 'sap_doc', name: 'sap_doc_num', orderable: false, searchable: false },
                    { data: 'submitted_info', name: 'submitted_at', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ baris',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    zeroRecords: 'Tidak ada data',
                    processing: 'Memuat...'
                }
            });

            $('#btnApplyFilter').on('click', function() {
                table.ajax.reload();
            });

            $('#btnResetFilter').on('click', function() {
                $('#filter_jenis, #filter_unit, #filter_periode, #filter_status').val('');
                table.ajax.reload();
            });

            @if (session('success'))
                Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')) });
            @endif
            @if (session('error'))
                Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')) });
            @endif

            $(document).on('click', '.bpjs-retry-btn', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                Swal.fire({
                    title: 'Retry submit ke SAP?',
                    text: 'AP Invoice akan dikirim ulang ke SAP B1.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, submit',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
