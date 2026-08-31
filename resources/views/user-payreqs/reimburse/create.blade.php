@extends('templates.main')

@section('title_page')
    Payment Request
@endsection

@section('breadcrumb_title')
    approved
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-receipt"></i> New Payment Request — Reimburse
                        </h3>
                        <a href="{{ route('user-payreqs.index') }}" class="vj-action-item vj-action-print">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back</span>
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('user-payreqs.reimburse.store') }}" method="POST">
                            @csrf

                            <input type="hidden" name="employee_id" value="{{ auth()->user()->id }}">
                            <input type="hidden" name="payreq_type" value="reimburse">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="payreq_no">Payreq No <small class="text-muted">(auto generated)</small></label>
                                        <input type="text" name="payreq_no" value="{{ $payreq_no }}" class="form-control"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="project">Project</label>
                                        <input type="text" name="project" value="{{ auth()->user()->project }}"
                                            class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="department">Department</label>
                                        <input type="hidden" name="department_id"
                                            value="{{ auth()->user()->department_id }}">
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

                            @include('user-payreqs.partials.payment-method', ['paymentEditable' => true])

                            <div class="vj-form-panel">
                                <div class="form-group mb-0">
                                    <label for="rab_id">RAB No</label>
                                    <select name="rab_id" id="rab_id"
                                        class="form-control select2bs4 @error('rab_id') is-invalid @enderror"
                                        style="width: 100%;">
                                        <option value="">-- Select RAB --</option>
                                        @foreach ($rabs as $rab)
                                            <option value="{{ $rab->id }}"
                                                {{ old('rab_id') == $rab->id ? 'selected' : '' }}>
                                                {{ $rab->rab_no ? $rab->rab_no : $rab->nomor }} |
                                                {{ $rab->rab_project }} | {{ $rab->description }}</option>
                                        @endforeach
                                    </select>
                                    @error('rab_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="vj-actions vj-form-actions">
                                <div class="vj-actions-primary">
                                    <button type="submit" class="vj-btn vj-btn-primary" id="btn-draft">
                                        <i class="fas fa-plus-circle"></i> Add Details
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
        })
    </script>
    @include('user-payreqs.partials.payment-method-scripts')
@endsection
