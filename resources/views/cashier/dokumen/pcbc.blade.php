@extends('templates.main')

@section('title_page')
    PCBC Administation
@endsection

@section('breadcrumb_title')
    cashier / pcbc
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <a href="{{ route('cashier.dokumen.index') }}">Rekening Koran</a> | <a href="#"
                        class="text-dark">PCBC</a>
                    @can('upload_dokumen')
                        <button href="#" class="btn btn-xs btn-success float-right mr-2" data-toggle="modal"
                            data-target="#modal-upload"><i class="fas fa-upload"></i> Upload PCBC</button>
                    @endcan
                </div> <!-- /.card-header -->

                <div class="card-body">
                    <table id="koran" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Project</th>
                                <th>PCBC Date</th>
                                <th>Uploaded by</th>
                                <th>Verified by</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div> <!-- /.col -->
    </div> <!-- /.row -->

    {{-- Modal create --}}
    <div class="modal fade" id="modal-upload">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"> Upload PCBC</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('cashier.dokumen.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="periode">Periode</label>
                                    <input type="hidden" name="project" value="{{ auth()->user()->project }}">
                                    <input type="hidden" name="type" value="pcbc">
                                    <input type="date" name="dokumen_date" id="dokumen_date"
                                        class="form-control @error('dokumen_date') is-invalid @enderror">
                                    @error('dokumen_date')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        @hasanyrole('superadmin|cashier|admin')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="project">Project</label>
                                        <select name="project" id="project" class="form-control select2bs4">
                                            @foreach (App\Models\Project::orderBy('code', 'asc')->get() as $project)
                                                <option value="{{ $project->code }}">{{ $project->code }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endhasanyrole

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="file_upload">Dokumen File</label>
                                    <input type="file" name="file_upload" id="file_upload" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <textarea name="remarks" id="remarks" class="form-control"></textarea>
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
            $("#koran").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('cashier.dokumen.data') . '?type=pcbc' }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'dokumen_date'
                    },
                    {
                        data: 'created_by'
                    },
                    {
                        data: 'verified_by'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                // columnDefs: [
                //         {
                //           "targets": [2],
                //           "className": "text-center"
                //         }
                //       ]
            })

            //Initialize Select2 Elements
            $('.select2').select2()

            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })

        });
    </script>
@endsection
