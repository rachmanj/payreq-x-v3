@extends('templates.main')

@section('title_page')
    My Payreq
@endsection

@section('breadcrumb_title')
    realization
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-invoice"></i> New Realization Payreq
                        </h3>
                        <a href="{{ route('user-payreqs.realizations.index') }}" class="vj-action-item vj-action-print">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back</span>
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('user-payreqs.realizations.store') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-4">
                                    <div class="form-group">
                                        <label for="realization_no">Realization No <small>(auto generated)</small></label>
                                        <input type="text" name="realization_no" value="{{ $realization_no }}"
                                            class="form-control" readonly>
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
                                        <input type="hidden" name="department_id"
                                            value="{{ auth()->user()->department_id }}">
                                        <input type="text" name="department"
                                            value="{{ auth()->user()->department->department_name }}" class="form-control"
                                            readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="payreq_no">Payment Request No</label>
                                        <select name="payreq_no" class="form-control">
                                            @foreach ($user_payreqs as $payreq)
                                                <option value="{{ $payreq->id }}">Payreq No.{{ $payreq->payreq_no }} |
                                                    Amount: IDR {{ $payreq->amount }} </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="form-group">
                                        <label for="realization_amount">Realization Amount</label>
                                        <input type="text" name="realization_amount" value="" class="form-control"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="form-group">
                                        <label for="variant">Variance</label>
                                        <input type="text" name="variant" value="" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title mb-0">
                                        <i class="fas fa-list"></i> Realization Details
                                    </h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-bordered table-striped table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Description</th>
                                                <th>Position</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>

                            <div class="vj-actions vj-form-actions mt-3">
                                <div class="vj-actions-primary">
                                    <button type="submit" class="vj-btn vj-btn-primary" id="btn-draft">
                                        <i class="fas fa-save"></i> Save as Draft
                                    </button>
                                    <button type="submit" class="vj-btn vj-btn-warning" id="btn-submit">
                                        <i class="fas fa-paper-plane"></i> Save and Submit
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

        $('#btn-draft').click(function() {
            $('form').append('<input type="hidden" name="draft" value="1">');
        });

        $('#btn-submit').click(function() {
            $('form').append('<input type="hidden" name="draft" value="0">');
        });
    </script>
@endsection
