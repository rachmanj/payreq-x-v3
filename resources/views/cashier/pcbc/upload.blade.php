@extends('templates.main')

@section('title_page')
    PCBC — Upload
@endsection

@section('breadcrumb_title')
    cashier / pcbc / upload
@endsection

@section('content')
    @can('see_pcbc_warning')
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card h-100 shadow-sm"
                style="border-left: 4px solid {{ ($pcbcCompliance['exempt'] ?? false) ? '#17a2b8' : (($pcbcCompliance['sanctioned'] ?? false) ? '#dc3545' : (($pcbcCompliance['variant'] ?? '') === 'warning' ? '#ffc107' : '#28a745')) }};">
                <div class="card-body">
                    <h5 class="card-title text-uppercase text-muted small mb-2">Compliance</h5>
                    @if (empty($pcbcCompliance))
                        <p class="mb-0 text-muted">Set your user project to see PCBC status.</p>
                    @elseif ($pcbcCompliance['exempt'] ?? false)
                        <p class="mb-1 font-weight-bold text-info">Exempt project</p>
                        <p class="small mb-0">{{ $pcbcCompliance['message'] ?? '' }}</p>
                    @else
                        <p class="mb-1 font-weight-bold">
                            @if ($pcbcCompliance['sanctioned'] ?? false)
                                <span class="text-danger">Action required</span>
                            @elseif (($pcbcCompliance['variant'] ?? '') === 'warning')
                                <span class="text-warning">Follow up</span>
                            @else
                                <span class="text-success">On track</span>
                            @endif
                        </p>
                        <p class="small text-muted mb-2">{{ $pcbcCompliance['message'] ?? '' }}</p>
                        @if (!empty($pcbcCompliance['weeks']))
                            <ul class="list-group list-group-flush small">
                                @foreach (['current', 'w1', 'w2'] as $wkey)
                                    @if (!empty($pcbcCompliance['weeks'][$wkey]))
                                        <li
                                            class="list-group-item px-0 py-1 border-0 d-flex justify-content-between align-items-center">
                                            <span>{{ $pcbcCompliance['weeks'][$wkey]['label'] }}</span>
                                            @if ($pcbcCompliance['weeks'][$wkey]['has_upload'])
                                                <span class="badge badge-success">OK</span>
                                            @else
                                                <span class="badge badge-secondary">—</span>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3 font-weight-bold d-block w-100">Weekly report rules</h5>
                    <ul class="mb-0 pl-3">
                        <li>Each <strong>calendar week</strong>, upload at least <strong>one</strong> PCBC PDF
                            (Mon 00:00 – Sun
                            23:59, <strong>{{ config('pcbc_compliance.timezone') }}</strong>).</li>
                        <li>The <strong>document date</strong> (not upload time) must fall in the week you are
                            reporting.</li>
                        <li>Missing <strong>two consecutive</strong> full weeks locks <strong>Ready to Pay</strong> and
                            <strong>Incoming List</strong> until compliance is met.</li>
                        <li>Exception projects: {{ implode(', ', config('pcbc_compliance.exception_project_codes', [])) }} — upload not
                            required.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <div class="row">
        <div class="col-12">
            <x-pcbc-links page="upload" />
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex flex-wrap align-items-center">
                    <h3 class="card-title mb-0">Uploaded PCBC documents</h3>
                    <div class="ml-auto">
                        @can('upload_pcbc')
                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
                                data-target="#modal-upload">
                                <i class="fas fa-cloud-upload-alt"></i> Upload PCBC PDF
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="pcbc" class="table table-hover table-striped table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width:3rem">#</th>
                                    <th>Project</th>
                                    <th>PCBC date (document)</th>
                                    <th>Uploaded by</th>
                                    <th style="width:8rem" class="text-right">Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-upload" tabindex="-1" role="dialog" aria-labelledby="modal-upload-title"
        aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h4 class="modal-title" id="modal-upload-title">Upload PCBC</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('cashier.pcbc.upload') }}" method="POST" enctype="multipart/form-data"
                    id="form-pcbc-upload">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small">PDF only, max 1 MB (route validation). Set the <strong>document
                                date</strong> to a day inside the week you are reporting.</p>
                        <div class="form-group">
                            <label for="dokumen_date">PCBC document date <span class="text-danger">*</span></label>
                            <input type="date" name="dokumen_date" id="dokumen_date" required
                                class="form-control @error('dokumen_date') is-invalid @enderror"
                                value="{{ old('dokumen_date', \Carbon\Carbon::now(config('pcbc_compliance.timezone'))->toDateString()) }}">
                            @error('dokumen_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @hasanyrole('superadmin|cashier|admin')
                            <div class="form-group">
                                <label for="project">Project <span class="text-danger">*</span></label>
                                <select name="project" id="project" class="form-control select2bs4" required>
                                    @foreach (App\Models\Project::where('is_active', true)->orderBy('code', 'asc')->get() as $project)
                                        <option value="{{ $project->code }}"
                                            {{ $project->code === auth()->user()->project ? 'selected' : '' }}>
                                            {{ $project->code }} — {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="project" value="{{ auth()->user()->project }}">
                        @endhasanyrole

                        <div class="form-group">
                            <label for="attachment">PDF file <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="attachment" id="attachment"
                                    class="custom-file-input @error('attachment') is-invalid @enderror"
                                    accept="application/pdf" required>
                                <label class="custom-file-label" for="attachment">Choose file…</label>
                            </div>
                            @error('attachment')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="remarks">Remarks (optional)</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="2"
                                placeholder="Notes for administrators"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .card-header .active {
            color: #000;
            text-transform: uppercase;
            font-weight: 600;
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(function() {
            $("#pcbc").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('cashier.pcbc.data') }}',
                pageLength: 15,
                order: [
                    [2, 'desc']
                ],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'project',
                        name: 'project',
                    },
                    {
                        data: 'dokumen_date',
                        name: 'dokumen_date',
                    },
                    {
                        data: 'created_by',
                        name: 'created_by',
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-right'
                    },
                ],
                fixedHeader: true,
            });

            $('.select2').select2();
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

            $('#attachment').on('change', function() {
                var f = this.files[0];
                var label = f ? f.name : 'Choose file…';
                $(this).next('.custom-file-label').text(label);
            });
        });
    </script>
@endsection
