@extends('templates.main')

@section('title_page')
    Loans
@endsection

@section('breadcrumb_title')
    accounting / loans / edit
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-edit"></i> Edit Loan
                        </h3>
                        <a href="{{ route('accounting.loans.index') }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                    <form action="{{ route('accounting.loans.update', $loan->id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="card-body">
                            <div class="vj-form-panel">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="loan_code">Code</label>
                                            <input type="text" name="loan_code" class="form-control"
                                                value="{{ old('loan_code', $loan->loan_code) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="creditor_id">Creditor Name</label>
                                            <select name="creditor_id" id="creditor_id"
                                                class="form-control select2bs4 @error('creditor_id') is-invalid @enderror">
                                                @foreach ($creditors as $creditor)
                                                    <option value="{{ $creditor->id }}"
                                                        data-sap-code="{{ $creditor->sapBusinessPartner?->code ?? '' }}"
                                                        data-sap-name="{{ $creditor->sapBusinessPartner?->name ?? '' }}"
                                                        data-has-sap="{{ $creditor->hasSapPartner() ? '1' : '0' }}"
                                                        {{ old('creditor_id', $loan->creditor_id) == $creditor->id ? 'selected' : null }}>
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
                                            @php
                                                $currentCreditor = $creditors->firstWhere('id', old('creditor_id', $loan->creditor_id));
                                            @endphp
                                            @if ($currentCreditor && $currentCreditor->sapBusinessPartner)
                                                <div class="mt-2">
                                                    <small class="text-info">
                                                        <strong>SAP Code:</strong> {{ $currentCreditor->sapBusinessPartner->code }}<br>
                                                        <strong>SAP Name:</strong> {{ $currentCreditor->sapBusinessPartner->name }}
                                                    </small>
                                                </div>
                                            @elseif($currentCreditor)
                                                <div class="mt-2">
                                                    <small class="text-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> Creditor not linked to SAP Business Partner.
                                                        AP Invoice creation will not be available.
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="start_date">Start Date</label>
                                            <input type="date" name="start_date"
                                                value="{{ old('start_date', $loan->start_date) }}" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label for="description">Description</label>
                                    <textarea name="description" id="description" cols="30" rows="2"
                                        class="form-control">{{ old('description', $loan->description) }}</textarea>
                                </div>
                            </div>

                            <div class="vj-form-panel mb-0">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tenor">Tenor<small> (bulan)</small></label>
                                            <input type="text" name="tenor" class="form-control"
                                                value="{{ old('tenor', $loan->tenor) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="principal">Principal</label>
                                            <input type="text" name="principal" value="{{ old('principal', $loan->principal) }}"
                                                class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label for="status">Status</label>
                                            <input type="text" name="status" value="{{ old('status', $loan->status) }}"
                                                class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="vj-btn vj-btn-success">
                                <i class="fas fa-save"></i> SAVE
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
                    $('#creditor-sap-info').show().find('small').removeClass('text-warning').addClass('text-muted');
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
