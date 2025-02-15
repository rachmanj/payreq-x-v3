@extends('templates.main')

@section('title_page')
    CUSTOMERS
@endsection

@section('breadcrumb_title')
    customers
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Customers</h3>

                    @hasanyrole('superadmin|admin|cashier')
                        <button href="#" class="btn btn-sm btn-primary float-right" data-toggle="modal"
                            data-target="#modal-create"><i class="fas fa-plus"></i> Customer</button>
                    @endhasanyrole
                </div> <!-- /.card-header -->

                <div class="card-body">
                    <table id="customers" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Project</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div> <!-- /.col -->
    </div> <!-- /.row -->

    {{-- Modal create --}}
    <div class="modal fade" id="modal-create">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"> New Customer</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('accounting.customers.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="name">Customer Name</label>
                                    <input name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}">
                                    @error('name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="type">Type</label>
                                    <select name="type" id="type" class="form-control">
                                        <option value="customer">Customer</option>
                                        <option value="vendor">Vendor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="sap_code">SAP Code</label>
                                    <input name="sap_code" id="sap_code"
                                        class="form-control @error('sap_code') is-invalid @enderror">
                                    @error('sap_code')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="project">Project</label>
                                    <select name="project" id="project"
                                        class="form-control select2bs4 @error('project') is-invalid @enderror">
                                        <option value="">Select Project</option>
                                        @foreach (App\Models\Project::orderBy('code', 'asc')->get() as $project)
                                            <option value="{{ $project->code }}"
                                                {{ old('project') == $project->code ? 'selected' : '' }}>
                                                {{ $project->code }}</option>
                                        @endforeach
                                    </select>
                                    @error('project')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div> <!-- /.modal-body -->
                    <div class="modal-footer float-left">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>
            </div> <!-- /.modal-content -->
        </div> <!-- /.modal-dialog -->
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(function() {
            $("#customers").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('accounting.customers.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'code'
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'type'
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [0],
                    "className": "text-right"
                }]
            })

            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

        });
    </script>
@endsection
