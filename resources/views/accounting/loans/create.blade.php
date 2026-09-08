@extends('templates.main')

@section('title_page')
    Loans
@endsection

@section('breadcrumb_title')
    accounting / loans / create
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-plus-circle"></i> New Loan
                        </h3>
                        <a href="{{ route('accounting.loans.index') }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                    <form action="{{ route('accounting.loans.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="vj-form-panel">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="loan_code">Code</label>
                                            <input type="text" name="loan_code" class="form-control"
                                                value="{{ old('loan_code') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="creditor_id">Creditor Name</label>
                                            <select name="creditor_id" id="creditor_id"
                                                class="form-control select2bs4 @error('creditor_id') is-invalid @enderror">
                                                <option value="">-- select creditor name --</option>
                                                @foreach ($creditors as $creditor)
                                                    <option value="{{ $creditor->id }}"
                                                        data-sap-code="{{ $creditor->sapBusinessPartner?->code ?? '' }}"
                                                        data-sap-name="{{ $creditor->sapBusinessPartner?->name ?? '' }}"
                                                        data-has-sap="{{ $creditor->hasSapPartner() ? '1' : '0' }}"
                                                        {{ $creditor->id == old('creditor_id') ? 'selected' : '' }}>
                                                        {{ $creditor->name }}
                                                        @if ($creditor->sapBusinessPartner)
                                                            ({{ $creditor->sapBusinessPartner->code }})
                                                        @else
                                                            (No SAP Link)
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('creditor_id')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                            <div id="creditor-sap-info" class="mt-2" style="display: none;">
                                                <small class="text-muted">
                                                    <strong>SAP Code:</strong> <span id="sap-code-display">-</span><br>
                                                    <strong>SAP Name:</strong> <span id="sap-name-display">-</span>
                                                </small>
                                            </div>
                                            @if (old('creditor_id'))
                                                @php
                                                    $selectedCreditor = $creditors->firstWhere('id', old('creditor_id'));
                                                @endphp
                                                @if ($selectedCreditor && $selectedCreditor->sapBusinessPartner)
                                                    <div class="mt-2">
                                                        <small class="text-info">
                                                            <strong>SAP Code:</strong> {{ $selectedCreditor->sapBusinessPartner->code }}<br>
                                                            <strong>SAP Name:</strong> {{ $selectedCreditor->sapBusinessPartner->name }}
                                                        </small>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="start_date">Start Date</label>
                                            <input type="date" name="start_date" value="{{ old('start_date') }}"
                                                class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label for="description">Description</label>
                                    <textarea name="description" id="description" cols="30" rows="2"
                                        class="form-control">{{ old('description') }}</textarea>
                                </div>
                            </div>

                            <div class="vj-form-panel mb-0">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tenor">Tenor<small> (bulan)</small></label>
                                            <input type="text" name="tenor" class="form-control" value="{{ old('tenor') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="principal">Principal</label>
                                            <input type="text" name="principal" value="{{ old('principal') }}"
                                                class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label for="status">Status</label>
                                            <input type="text" name="status" value="{{ old('status') }}" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="vj-btn vj-btn-success">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </form>
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
    <script src="{{ asset('adminlte/axios/axios.min.js') }}"></script>
    <script>
        $(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

            $('#creditor_id').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var sapCode = selectedOption.data('sap-code');
                var sapName = selectedOption.data('sap-name');
                var hasSap = selectedOption.data('has-sap');

                if (hasSap && sapCode) {
                    $('#sap-code-display').text(sapCode);
                    $('#sap-name-display').text(sapName);
                    $('#creditor-sap-info').show();
                } else {
                    $('#creditor-sap-info').hide();
                    if ($(this).val()) {
                        $('#sap-code-display').text('Not Linked');
                        $('#sap-name-display').text('Creditor not linked to SAP Business Partner');
                        $('#creditor-sap-info').show().find('small').removeClass('text-muted').addClass('text-warning');
                    }
                }
            });

            if ($('#creditor_id').val()) {
                $('#creditor_id').trigger('change');
            }
        })
    </script>
@endsection
