@extends('templates.main')

@section('title_page')
    Notulen AI — Documents
@endsection

@section('breadcrumb_title')
    notulen / documents
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title mb-0">Notulen Rapat</h3>
                    @can('upload_notulen')
                        <button type="button" class="btn btn-primary btn-sm ml-auto" data-toggle="modal"
                            data-target="#uploadModal">
                            <i class="fas fa-upload mr-1"></i> Upload PDF
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    <table id="meetings_table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Meeting Date</th>
                                <th>Status</th>
                                <th>Uploaded By</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('upload_notulen')
        <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('notulen.meetings.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Upload Notulen PDF</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="title">Judul Rapat</label>
                                <input type="text" name="title" id="title" class="form-control" required maxlength="255"
                                    value="{{ old('title') }}">
                                @error('title')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="meeting_date">Tanggal Rapat</label>
                                <input type="date" name="meeting_date" id="meeting_date" class="form-control"
                                    value="{{ old('meeting_date') }}">
                                @error('meeting_date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="file">File PDF</label>
                                <input type="file" name="file" id="file" class="form-control-file" accept="application/pdf"
                                    required>
                                @error('file')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
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
            $('#meetings_table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: @json(route('notulen.meetings.data')),
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'title',
                        name: 'title'
                    },
                    {
                        data: 'meeting_date_fmt',
                        name: 'meeting_date',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'uploader_name',
                        name: 'uploader.name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [2, 'desc']
                ],
            });

            @if ($errors->any())
                $('#uploadModal').modal('show');
            @endif
        });
    </script>
@endsection
