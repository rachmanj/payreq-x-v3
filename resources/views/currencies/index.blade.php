@extends('templates.main')

@section('title_page')
    Currencies Management
@endsection

@section('breadcrumb_title')
    Currencies
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Master Data Currencies</h3>
                    <a href="{{ route('currencies.create') }}" class="btn btn-sm btn-primary float-right">
                        <i class="fas fa-plus"></i> Add Currency
                    </a>
                </div>

                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="filter_status">Status</label>
                            <select id="filter_status" class="form-control">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_search">Search</label>
                            <input type="text" id="filter_search" class="form-control"
                                placeholder="Search code, name, or symbol...">
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <div>
                                <button type="button" id="btn_filter" class="btn btn-info">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <button type="button" id="btn_reset" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <table id="currencies-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Symbol</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Updated By</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            var table = $('#currencies-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('currencies.data') }}',
                    data: function(d) {
                        d.is_active = $('#filter_status').val();
                        d.search = $('#filter_search').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'currency_code',
                        name: 'currency_code'
                    },
                    {
                        data: 'currency_name',
                        name: 'currency_name'
                    },
                    {
                        data: 'symbol',
                        name: 'symbol'
                    },
                    {
                        data: 'status',
                        name: 'is_active',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'creator_name',
                        name: 'creator.name'
                    },
                    {
                        data: 'updater_name',
                        name: 'updater.name'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                responsive: true,
                lengthChange: false,
                autoWidth: false,
            });

            // Filter functionality
            $('#btn_filter').click(function() {
                table.draw();
            });

            $('#btn_reset').click(function() {
                $('#filter_status').val('');
                $('#filter_search').val('');
                table.draw();
            });

            // Enter key for search
            $('#filter_search').keypress(function(e) {
                if (e.which === 13) {
                    table.draw();
                }
            });
        });

        function deleteItem(id) {
            if (confirm('Are you sure you want to delete this currency?')) {
                $.ajax({
                    url: '{{ route('currencies.index') }}/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#currencies-table').DataTable().ajax.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        let message = 'Something went wrong!';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        toastr.error(message);
                    }
                });
            }
        }
    </script>
@endsection
