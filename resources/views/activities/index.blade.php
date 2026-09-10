@extends('templates.main')

@section('title_page')
    Master Kegiatan
@endsection

@section('breadcrumb_title')
    Master / Kegiatan
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('content')
    <div class="vj-show">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-tasks"></i> Master Kegiatan</h3>
                <a href="{{ route('activities.create') }}" class="vj-btn vj-btn-success">
                    <i class="fas fa-plus"></i> Tambah Kegiatan
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="filter_status">Status</label>
                        <select id="filter_status" class="form-control">
                            <option value="">Semua</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter_search">Cari</label>
                        <input type="text" id="filter_search" class="form-control" placeholder="Kode, nama, periode...">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="btn_filter" class="btn btn-info mr-2"><i class="fas fa-search"></i> Filter</button>
                        <button type="button" id="btn_reset" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</button>
                    </div>
                </div>

                <table id="activities-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Periode</th>
                            <th>Project</th>
                            <th>Mode</th>
                            <th>Akun</th>
                            <th>Status</th>
                            <th>Dibuat Oleh</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script>
        $(function () {
            const table = $('#activities-table').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route('activities.data') }}',
                    data: function (d) {
                        d.status = $('#filter_status').val();
                        d.search = $('#filter_search').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'code' },
                    { data: 'name' },
                    { data: 'periode' },
                    { data: 'project', defaultContent: 'Semua' },
                    { data: 'mode_label' },
                    { data: 'account_label' },
                    { data: 'status_label' },
                    { data: 'creator_name' },
                    { data: 'action', orderable: false, searchable: false, className: 'text-center' },
                ]
            });

            $('#btn_filter').on('click', () => table.ajax.reload());
            $('#btn_reset').on('click', function () {
                $('#filter_status').val('');
                $('#filter_search').val('');
                table.ajax.reload();
            });
        });
    </script>
@endsection
