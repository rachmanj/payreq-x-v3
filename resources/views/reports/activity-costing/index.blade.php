@extends('templates.main')

@section('title_page')
    Biaya per Kegiatan
@endsection

@section('breadcrumb_title')
    Laporan / Biaya per Kegiatan
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('content')
    <div class="vj-show">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="card-title mb-0"><i class="fas fa-chart-pie"></i> Laporan Biaya per Kegiatan</h3>
                <div class="d-flex flex-wrap gap-2">
                    <a href="#" id="btn-export" class="vj-btn vj-btn-success">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a href="{{ route('reports.index') }}" class="vj-action-item vj-action-back">
                        <i class="fas fa-arrow-left"></i> Kembali ke Laporan
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label for="filter_date_from">Tanggal Dari</label>
                        <input type="date" id="filter_date_from" class="form-control" value="{{ $filters['date_from'] }}">
                    </div>
                    <div class="col-md-2">
                        <label for="filter_date_to">Tanggal Sampai</label>
                        <input type="date" id="filter_date_to" class="form-control" value="{{ $filters['date_to'] }}">
                    </div>
                    <div class="col-md-2">
                        <label for="filter_project">Project</label>
                        <select id="filter_project" class="form-control">
                            <option value="">Semua</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->code }}" @selected($filters['project'] === $project->code)>{{ $project->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_department">Department</label>
                        <select id="filter_department" class="form-control">
                            <option value="">Semua</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) $filters['department_id'] === (string) $department->id)>{{ $department->akronim }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_activity">Kegiatan</label>
                        <select id="filter_activity" class="form-control">
                            <option value="">Semua</option>
                            @foreach ($activities as $item)
                                <option value="{{ $item->id }}" @selected((string) $filters['activity_id'] === (string) $item->id)>{{ $item->code }} — {{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_periode">Periode</label>
                        <input type="text" id="filter_periode" class="form-control" placeholder="2026-09" value="{{ $filters['periode'] }}">
                    </div>
                    <div class="col-md-2">
                        <label for="filter_status">Status Kegiatan</label>
                        <select id="filter_status" class="form-control">
                            <option value="">Semua</option>
                            <option value="open" @selected($filters['status'] === 'open')>Open</option>
                            <option value="closed" @selected($filters['status'] === 'closed')>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" id="btn_filter" class="btn btn-info mr-2"><i class="fas fa-search"></i> Filter</button>
                        <button type="button" id="btn_reset" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</button>
                    </div>
                </div>

                <table id="activity-costing-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kode</th>
                            <th>Nama Kegiatan</th>
                            <th>Periode</th>
                            <th>Project</th>
                            <th>Mode</th>
                            <th>Akun Kegiatan</th>
                            <th>Jumlah Realisasi</th>
                            <th>Jumlah Nota</th>
                            <th>Total Biaya</th>
                            <th>Status</th>
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
            function filterParams() {
                return {
                    date_from: $('#filter_date_from').val(),
                    date_to: $('#filter_date_to').val(),
                    project: $('#filter_project').val(),
                    department_id: $('#filter_department').val(),
                    activity_id: $('#filter_activity').val(),
                    periode: $('#filter_periode').val(),
                    status: $('#filter_status').val(),
                };
            }

            const table = $('#activity-costing-table').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route('reports.activity-costing.data') }}',
                    data: function (d) {
                        Object.assign(d, filterParams());
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
                    { data: 'realization_count', className: 'text-right' },
                    { data: 'note_count', className: 'text-right' },
                    { data: 'total_cost', className: 'text-right' },
                    { data: 'status_label' },
                    { data: 'action', orderable: false, searchable: false, className: 'text-center' },
                ]
            });

            $('#btn_filter').on('click', () => table.ajax.reload());
            $('#btn_reset').on('click', function () {
                $('#filter_date_from, #filter_date_to, #filter_periode').val('');
                $('#filter_project, #filter_department, #filter_activity, #filter_status').val('');
                table.ajax.reload();
            });

            $('#btn-export').on('click', function (e) {
                e.preventDefault();
                const params = new URLSearchParams(filterParams());
                window.location.href = '{{ route('reports.activity-costing.export') }}?' + params.toString();
            });
        });
    </script>
@endsection
