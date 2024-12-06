@extends('templates.main')

@section('title_page')
    Anggaran
@endsection

@section('breadcrumb_title')
    reports / anggaran
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <b>ACTIVE</b> | <a href="{{ route('reports.anggaran.index', ['status' => 'inactive']) }}">In-active</a>
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back to Index</a>
                    @can('recalculate_release')
                        <a href="{{ route('reports.anggaran.recalculate') }}" class="btn btn-xs btn-warning float-right mx-2"
                            onclick="return confirm('Are you sure you want to recalculate anggaran release?')">Recalc
                            Release</a>
                        <button id="inactivate-many" class="btn btn-warning btn-xs float-right">Inactivate Many</button>
                    @endcan
                </div>
                <div class="card-body">
                    <form id="form-inactivate-many" action="{{ route('reports.anggaran.update_many') }}" method="POST">
                        @csrf
                        <table id="anggarans" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>No</th>
                                    <th>Nomor</th>
                                    <th>Creator</th>
                                    <th>RAB Project</th>
                                    <th>Description</th>
                                    <th>Periode</th>
                                    <th>Budget</th>
                                    <th>Progres</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will populate this -->
                            </tbody>
                        </table>
                    </form>
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
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            var table = $("#anggarans").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('reports.anggaran.data', ['status' => 'active']) }}',
                columns: [{
                        data: 'checkbox',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, full, meta) {
                            return '<input type="checkbox" name="id[]" value="' + full.id + '">';
                        }
                    },
                    {
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor'
                    },
                    {
                        data: 'creator'
                    },
                    {
                        data: 'rab_project'
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: 'periode'
                    },
                    {
                        data: 'budget'
                    },
                    {
                        data: 'progres'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                order: [
                    [1, 'asc']
                ]
            });

            // Handle click on "Select all" control
            $('#select-all').on('click', function() {
                // Check/uncheck all checkboxes in the table
                var rows = table.rows({
                    'search': 'applied'
                }).nodes();
                $('input[type="checkbox"]', rows).prop('checked', this.checked);
            });

            // Handle click on checkbox to set state of "Select all" control
            $('#anggarans tbody').on('change', 'input[type="checkbox"]', function() {
                // If checkbox is not checked
                if (!this.checked) {
                    var el = $('#select-all').get(0);
                    // If "Select all" control is checked and has 'indeterminate' property
                    if (el && el.checked && ('indeterminate' in el)) {
                        // Set visual state of "Select all" control
                        // as 'indeterminate'
                        el.indeterminate = true;
                    }
                }
            });

            // Handle click on "Inactivate Many" button
            $('#inactivate-many').on('click', function() {
                if (!confirm('Apakah yakin akan merubah status anggaran terpilih?')) {
                    return false;
                }

                var form = $('#form-inactivate-many');

                // Submit form
                form.submit();
            });
        });
    </script>
@endsection
