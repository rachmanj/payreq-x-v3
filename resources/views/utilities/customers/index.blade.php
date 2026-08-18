@extends('templates.main')

@section('title_page')
    ID Pelanggan Utilitas
@endsection

@section('breadcrumb_title')
    utilities / id pelanggan
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Master ID Pelanggan</h3>
                    <a href="{{ route('utilities.customers.create') }}" class="btn btn-sm btn-primary float-right">
                        <i class="fas fa-plus"></i> Tambah ID Pelanggan
                    </a>
                </div>
                <div class="card-body">
                    <table id="customers-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID Pelanggan</th>
                                <th>Nama</th>
                                <th>Jenis</th>
                                <th>Project</th>
                                <th>Akun</th>
                                <th>Status</th>
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
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script>
        $(function() {
            $('#customers-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('utilities.customers.data') }}',
                columns: [{
                        data: 'id_pelanggan',
                        name: 'id_pelanggan'
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'jenis_label',
                        name: 'jenis_utilitas'
                    },
                    {
                        data: 'project',
                        name: 'project'
                    },
                    {
                        data: 'account_info',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'is_active_badge',
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
        });
    </script>
@endsection
