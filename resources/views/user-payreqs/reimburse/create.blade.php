@extends('templates.main')

@section('title_page')
    Payment Request
@endsection

@section('breadcrumb_title')
    approved
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Payment Request - Reimburse</h3>
                    <a href="{{ route('user-payreqs.index') }}" class="btn btn-sm btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('user-payreqs.reimburse.store') }}" method="POST">
                        @csrf

                        <input type="hidden" name="employee_id" value="{{ auth()->user()->id }}">
                        <input type="hidden" name="payreq_type" value="reimburse">
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="payreq_no">Payreq No <small>(auto generated)</small></label>
                                    <input type="text" name="payreq_no" value="{{ $payreq_no }}" class="form-control"
                                        readonly>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="project">Project</label>
                                    <input type="text" name="project" value="{{ auth()->user()->project }}"
                                        class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                                    <input type="text" name="department"
                                        value="{{ auth()->user()->department->department_name }}" class="form-control"
                                        readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="remarks">Purpose</label>
                            <textarea name="remarks" id="remarks" cols="30" rows="2"
                                class="form-control @error('remarks') is-invalid @enderror" autofocus>{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        @can('rab_select')
                            <div class="form-group">
                                <label for="rab_id">RAB No</label>
                                <select name="rab_id" class="form-control select2bs4">
                                    <option value="">-- Select RAB --</option>
                                    @foreach ($rabs as $rab)
                                        <option value="{{ $rab->id }}">{{ $rab->rab_no ? $rab->rab_no : $rab->nomor }} |
                                            {{ $rab->rab_project }} | {{ $rab->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endcan

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-block" id="btn-draft"><i
                                            class="fas fa-save"></i> Add Details</button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function() {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
        })
    </script>
@endsection
