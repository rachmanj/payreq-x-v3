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
                    <h3 class="card-title">New Payment Request - Advance</h3>
                    <a href="{{ route('user-payreqs.index') }}" class="btn btn-sm btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('user-payreqs.advance.proses') }}" method="POST">
                        @csrf

                        <input type="hidden" name="employee_id" value="{{ auth()->user()->id }}">
                        <input type="hidden" name="payreq_type" value="advance">
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

                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="text" name="amount" id="amount" class="form-control"
                                value="{{ old('amount') }}" onkeyup="formatNumber(this)">
                            @error('amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <script>
                            function formatNumber(input) {
                                // Remove any non-digit characters except dots
                                let value = input.value.replace(/[^\d.]/g, '');

                                // Ensure only one decimal point
                                let parts = value.split('.');
                                if (parts.length > 2) {
                                    parts = [parts[0], parts.slice(1).join('')];
                                }

                                // Add thousand separators
                                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

                                // Join with decimal part if exists
                                input.value = parts.join('.');
                            }
                        </script>

                        @can('rab_select')
                            <div class="form-group">
                                <label for="rab_id">RAB No</label>
                                <select name="rab_id" class="form-control select2bs4 @error('rab_id') is-invalid @enderror">
                                    <option value="">-- Select RAB --</option>
                                    @foreach ($rabs as $rab)
                                        <option value="{{ $rab->id }}">{{ $rab->rab_no ? $rab->rab_no : $rab->nomor }} |
                                            {{ $rab->project }} | {{ $rab->description }}</option>
                                    @endforeach
                                </select>
                                @error('rab_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        @endcan

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-6">
                                    <button type="submit" class="btn btn-primary btn-block" id="btn-draft"><i
                                            class="fas fa-save"></i> Save as Draft</button>
                                </div>
                                <div class="col-6">
                                    <button type="submit" class="btn btn-warning btn-block" id="btn-submit"><i
                                            class="fas fa-paper-plane"></i> Save and Submit</button>
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

        // btn-save as draft
        $('#btn-draft').click(function() {
            // add attribute name="draft" to form
            $('form').append('<input type="hidden" name="button_type" value="create">');
        });

        // btn-save and submit
        $('#btn-submit').click(function() {
            // add attribute name="draft" to form
            $('form').append('<input type="hidden" name="button_type" value="create_submit">');
        });
    </script>
@endsection
