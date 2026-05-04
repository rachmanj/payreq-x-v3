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

                        @php
                            $advanceCanSelectRab = auth()->user()->can('rab_select');
                        @endphp

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
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_official_travel"
                                    name="is_official_travel" {{ old('is_official_travel') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_official_travel">For Official
                                    Travel?</label>
                                <small class="text-muted">If checked, the LOT will be searched based on the travel number,
                                    traveler name, department, and project.</small>
                            </div>
                        </div>

                        <div id="lot_search_form" style="display: none;">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Search Official Travel</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="travel_number">LOT Number</label>
                                                <input type="text" class="form-control" id="travel_number"
                                                    name="travel_number" value="{{ old('travel_number') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="traveler">Traveler Name</label>
                                                <input type="text" class="form-control" id="traveler" name="traveler"
                                                    value="{{ old('traveler') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="department">Department</label>
                                                {{-- <input type="text" class="form-control" id="department" name="department"
                                                    value="{{ old('department') }}"> --}}
                                                <input type="text" id="department" name="department"
                                                    value="{{ auth()->user()->department->department_name }}"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="project">Project</label>
                                                {{-- <input type="text" class="form-control" id="project" name="project"
                                                    value="{{ old('project') }}"> --}}
                                                <input type="text" id="project" name="project"
                                                    value="{{ auth()->user()->project }}" class="form-control" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button type="button" class="btn btn-primary" id="search_lot">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                    <div id="lot_search_error" class="alert alert-danger alert-dismissible fade show"
                                        style="display: none;">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        <span class="error-message"></span>
                                    </div>

                                    <div id="lot_search_results" style="display: none;">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>LOT Number</th>
                                                        <th>Traveler</th>
                                                        <th>Department</th>
                                                        <th>Project</th>
                                                        <th class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="lot_results_body">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- LOT Detail Modal -->
                            <div class="modal fade" id="lotDetailModal" tabindex="-1" role="dialog"
                                aria-labelledby="lotDetailModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-gradient-primary">
                                            <h5 class="modal-title" id="lotDetailModalLabel">
                                                <i class="fas fa-plane-departure mr-2"></i>LOT Detail
                                            </h5>
                                            <button type="button" class="close text-white" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body p-0">
                                            <!-- Header Info -->
                                            <div class="bg-light p-3 border-bottom">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1" id="modal_travel_number"></h5>
                                                        <p class="text-muted mb-0 small" id="modal_travel_date"></p>
                                                    </div>
                                                    <div>
                                                        <span class="badge badge-lg px-3 py-2"
                                                            id="modal_status_badge"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="p-3">
                                                <div class="row">
                                                    <!-- Travel Info -->
                                                    <div class="col-md-6">
                                                        <div class="card card-outline card-primary mb-3">
                                                            <div class="card-header py-2">
                                                                <h6 class="card-title mb-0">
                                                                    <i class="fas fa-info-circle mr-1"></i>Travel
                                                                    Information
                                                                </h6>
                                                            </div>
                                                            <div class="card-body py-2">
                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <small class="text-muted d-block">Purpose</small>
                                                                        <span id="modal_purpose"
                                                                            class="d-block mb-2"></span>

                                                                        <small
                                                                            class="text-muted d-block">Destination</small>
                                                                        <span id="modal_destination"
                                                                            class="d-block mb-2"></span>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <small class="text-muted d-block">Duration</small>
                                                                        <span id="modal_duration"
                                                                            class="d-block mb-2"></span>

                                                                        <small class="text-muted d-block">Departure
                                                                            From</small>
                                                                        <span id="modal_departure_from"
                                                                            class="d-block mb-2"></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Traveler Info -->
                                                    <div class="col-md-6">
                                                        <div class="card card-outline card-info mb-3">
                                                            <div class="card-header py-2">
                                                                <h6 class="card-title mb-0">
                                                                    <i class="fas fa-user mr-1"></i>Traveler Information
                                                                </h6>
                                                            </div>
                                                            <div class="card-body py-2">
                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <small class="text-muted d-block">Name</small>
                                                                        <span id="modal_traveler_name"
                                                                            class="d-block mb-2"></span>

                                                                        <small
                                                                            class="text-muted d-block">Department</small>
                                                                        <span id="modal_traveler_department"
                                                                            class="d-block mb-2"></span>

                                                                        <small class="text-muted d-block">Position</small>
                                                                        <span id="modal_traveler_position"
                                                                            class="d-block mb-2"></span>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <small class="text-muted d-block">Project</small>
                                                                        <span id="modal_traveler_project"
                                                                            class="d-block mb-2"></span>

                                                                        <small class="text-muted d-block">NIK</small>
                                                                        <span id="modal_traveler_nik"
                                                                            class="d-block mb-2"></span>

                                                                        <small class="text-muted d-block">Class</small>
                                                                        <span id="modal_traveler_class"
                                                                            class="d-block mb-2"></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Approval & Transport -->
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="card card-outline card-success mb-3">
                                                            <div class="card-header py-2">
                                                                <h6 class="card-title mb-0">
                                                                    <i class="fas fa-check-circle mr-1"></i>Approval Plans
                                                                </h6>
                                                            </div>
                                                            <div class="card-body py-2">
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm table-bordered mb-0">
                                                                        <thead class="bg-light">
                                                                            <tr>
                                                                                <th>Approver</th>
                                                                                <th>Status</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody id="modal_approval_plans"></tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card card-outline card-warning mb-3">
                                                            <div class="card-header py-2">
                                                                <h6 class="card-title mb-0">
                                                                    <i class="fas fa-car mr-1"></i>Transportation &
                                                                    Accommodation
                                                                </h6>
                                                            </div>
                                                            <div class="card-body py-2">
                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <small
                                                                            class="text-muted d-block">Transportation</small>
                                                                        <span id="modal_transportation"
                                                                            class="d-block mb-2"></span>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <small
                                                                            class="text-muted d-block">Accommodation</small>
                                                                        <span id="modal_accommodation"
                                                                            class="d-block mb-2"></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Followers -->
                                                <div class="card card-outline card-secondary mb-0">
                                                    <div class="card-header py-2">
                                                        <h6 class="card-title mb-0">
                                                            <i class="fas fa-users mr-1"></i>Travel Followers
                                                        </h6>
                                                    </div>
                                                    <div class="card-body p-0">
                                                        <div class="table-responsive">
                                                            <table
                                                                class="table table-sm table-bordered table-striped mb-0">
                                                                <thead class="bg-light">
                                                                    <tr>
                                                                        <th>Name</th>
                                                                        <th>Department</th>
                                                                        <th>Position</th>
                                                                        <th>Project</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="modal_followers">
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                <i class="fas fa-times mr-1"></i> Close
                                            </button>
                                            <button type="button" class="btn btn-primary" id="modal_pick_lot">
                                                <i class="fas fa-check mr-1"></i> Pick LOT
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="lot_no" id="selected_lot_no">
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

                        @php
                            $allocationRowsOld = old('allocations');
                            if (! is_array($allocationRowsOld)) {
                                $allocationRowsOld = [['anggaran_id' => '', 'amount' => '', 'remarks' => '']];
                            }
                            if (count($allocationRowsOld) < 1) {
                                $allocationRowsOld = [['anggaran_id' => '', 'amount' => '', 'remarks' => '']];
                            }
                        @endphp

                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="text" name="amount" id="amount" class="form-control"
                                value="{{ old('amount') }}" onkeyup="formatNumber(this)">
                            <small id="amount-help-legacy" class="form-text text-muted">Enter the total advance
                                amount.</small>
                            <small id="amount-help-multi" class="form-text text-muted" style="display:none;">Total is
                                the sum of allocation rows (read-only).</small>
                            @error('amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <script>
                            function formatNumber(input) {
                                let value = input.value.replace(/[^\d.]/g, '');
                                let parts = value.split('.');
                                if (parts.length > 2) {
                                    parts = [parts[0], parts.slice(1).join('')];
                                }
                                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                input.value = parts.join('.');
                            }
                        </script>

                        @if ($advanceCanSelectRab)
                            <div class="form-group">
                                <label>Budget form</label>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="budget_mode_legacy" name="budget_link_mode" value="legacy"
                                        class="custom-control-input"
                                        {{ old('budget_link_mode', 'legacy') === 'legacy' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="budget_mode_legacy">Single anggaran
                                        (existing form)</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="budget_mode_multi" name="budget_link_mode"
                                        value="multi_allocation" class="custom-control-input"
                                        {{ old('budget_link_mode') === 'multi_allocation' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="budget_mode_multi">Multi-row — one payreq,
                                        many anggaran</label>
                                </div>
                                @error('budget_link_mode')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="advance-budget-legacy" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                                <div class="form-group mb-0">
                                    <label for="rab_id_legacy">RAB No</label>
                                    <select name="rab_id" id="rab_id_legacy"
                                        class="form-control select2bs4 @error('rab_id') is-invalid @enderror"
                                        style="width: 100%;">
                                        <option value="">-- Select RAB --</option>
                                        @foreach ($rabs as $rab)
                                            <option value="{{ $rab->id }}"
                                                {{ old('rab_id') == $rab->id ? 'selected' : '' }}>
                                                {{ $rab->rab_no ? $rab->rab_no : $rab->nomor }} |
                                                {{ $rab->project }} | {{ $rab->description }}</option>
                                        @endforeach
                                    </select>
                                    @error('rab_id')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div id="advance-budget-multi" class="border rounded p-3 mb-3 bg-light"
                                style="display:none;">
                                <div class="form-group mb-2">
                                    <label>Anggaran allocation rows</label>
                                    <small class="text-muted d-block mb-2">Each row maps part of this payreq to an
                                        anggaran (same anggaran may appear twice). Row amounts must match the total
                                        amount.</small>
                                    @error('allocations')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Anggaran</th>
                                                    <th style="width:22%">Amount</th>
                                                    <th>Remarks</th>
                                                    <th style="width:52px"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="advance-allocation-body">
                                                @foreach ($allocationRowsOld as $i => $row)
                                                    <tr class="allocation-row">
                                                        <td>
                                                            <select
                                                                name="allocations[{{ $i }}][anggaran_id]"
                                                                class="form-control allocation-rab-select"
                                                                style="width:100%"
                                                                data-placeholder="-- Select RAB --">
                                                                <option value=""></option>
                                                                @foreach ($rabs as $rab)
                                                                    <option value="{{ $rab->id }}"
                                                                        {{ (string) old("allocations.$i.anggaran_id", $row['anggaran_id'] ?? '') === (string) $rab->id ? 'selected' : '' }}>
                                                                        {{ $rab->rab_no ? $rab->rab_no : $rab->nomor }}
                                                                        | {{ $rab->project }} |
                                                                        {{ $rab->description }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                name="allocations[{{ $i }}][amount]"
                                                                class="form-control allocation-amount"
                                                                value="{{ old("allocations.$i.amount", $row['amount'] ?? '') }}"
                                                                onkeyup="formatNumber(this)">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                name="allocations[{{ $i }}][remarks]"
                                                                class="form-control"
                                                                value="{{ old("allocations.$i.remarks", $row['remarks'] ?? '') }}">
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger btn-remove-allocation-row"
                                                                title="Remove row">&times;</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                        id="btn-add-allocation-row">Add row</button>
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="budget_link_mode" value="legacy">
                        @endif

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
    <style>
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 50;
            border-radius: 3px;
        }

        .card {
            position: relative;
        }
    </style>
@endsection

@section('scripts')
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- daterangepicker -->
    <script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>
    <script>
        $(function() {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

            // Check initial state of checkbox based on old input
            const isOfficialTravel = {{ old('is_official_travel') ? 'true' : 'false' }};
            const hasLotNo = {{ old('lot_no') ? 'true' : 'false' }};

            // Show form if checkbox was checked before
            if (isOfficialTravel) {
                $('#lot_search_form').show();
            }

            // Show selected LOT if there was one
            if (hasLotNo) {
                const lotNo = '{{ old('lot_no') }}';
                if (lotNo) {
                    showSelectedLot(lotNo);
                }
            }

            // Show/hide LOT search form based on checkbox
            $('#is_official_travel').change(function() {
                if ($(this).is(':checked')) {
                    $('#lot_search_form').show();
                } else {
                    $('#lot_search_form').hide();
                    $('#lot_search_results').hide();
                    $('#selected_lot_no').val('');
                    $('#lot_search_error').hide();
                    // $('#travel_number').val('');
                    // $('#traveler').val('');
                    // $('#department').val('');
                    // $('#project').val('');

                    // Remove selected LOT display if exists
                    $('#selected_lot_display').remove();
                }
            });

            // Handle LOT search
            $('#search_lot').click(function() {
                const searchData = {
                    travel_number: $('#travel_number').val(),
                    traveler: $('#traveler').val(),
                    department: $('#department').val(),
                    project: $('#project').val()
                };

                // Hide previous error and results
                $('#lot_search_error').hide();
                $('#lot_search_results').hide();

                // Remove any existing overlay first
                $('.overlay').remove();

                // Add loading overlay
                const $cardBody = $('.card-outline.card-primary .card-body');
                const $overlay = $(
                    '<div class="overlay"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>'
                );
                $cardBody.append($overlay);

                $.ajax({
                    url: '{{ route('user-payreqs.advance.search-lot') }}',
                    method: 'POST',
                    data: searchData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Remove loading overlay first
                        $('.overlay').remove();

                        if (response.success) {
                            if (response.data && response.data.length > 0) {
                                displayLotResults(response.data);
                            } else {
                                showError('No LOT data found');
                            }
                        } else {
                            showError(response.message || 'Failed to fetch LOT data');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Remove loading overlay first
                        $('.overlay').remove();

                        let errorMessage = 'Error searching LOT. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        showError(errorMessage);
                    },
                    complete: function() {
                        // Ensure overlay is removed even if there's an error
                        $('.overlay').remove();
                    }
                });
            });

            function showError(message) {
                // Remove any existing overlay first
                $('.overlay').remove();

                $('#lot_search_error .error-message').html(message);
                $('#lot_search_error').show();
                $('#lot_search_results').hide();
            }

            function displayLotResults(data) {
                // Remove any existing overlay first
                $('.overlay').remove();

                const tbody = $('#lot_results_body');
                tbody.empty();

                data.forEach(function(lot) {
                    const row = `
                        <tr>
                            <td>${lot.official_travel_number || 'N/A'}</td>
                            <td>${lot.traveler?.employee?.fullname || 'N/A'}</td>
                            <td>${lot.traveler?.position?.department?.department_name || 'N/A'}</td>
                            <td>${lot.project?.project_code || 'N/A'}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-info view-lot-detail"
                                    data-lot='${JSON.stringify(lot)}'>
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });

                $('#lot_search_results').show();
            }

            // Handle LOT detail view
            $(document).on('click', '.view-lot-detail', function() {
                const lot = $(this).data('lot');
                console.log(lot);

                // Set status badge (use lot.status from payload)
                const status = lot.status || 'N/A';
                let badgeClass = 'badge-secondary';
                const normalizedStatus = String(status).toLowerCase();
                if (['approved', 'open', 'active'].includes(normalizedStatus)) badgeClass = 'badge-success';
                if (['rejected', 'declined', 'closed'].includes(normalizedStatus)) badgeClass =
                    'badge-danger';
                if (['pending', 'in review'].includes(normalizedStatus)) badgeClass = 'badge-warning';

                $('#modal_status_badge')
                    .removeClass('badge-secondary badge-success badge-danger badge-warning')
                    .addClass(badgeClass)
                    .text(String(status).toUpperCase());

                // Travel Information
                $('#modal_travel_number').text(lot.official_travel_number || 'N/A');
                $('#modal_travel_date').text(lot.official_travel_date ? moment(lot.official_travel_date)
                    .format('DD MMMM YYYY') : 'N/A');
                $('#modal_purpose').text(lot.purpose || 'N/A');
                $('#modal_destination').text(lot.destination || 'N/A');
                $('#modal_duration').text(lot.duration || 'N/A');
                $('#modal_departure_from').text(lot.departure_from ? moment(lot.departure_from).format(
                    'DD MMMM YYYY') : 'N/A');

                // Traveler Information
                $('#modal_traveler_name').text(lot.traveler?.employee?.fullname || 'N/A');
                $('#modal_traveler_department').text(lot.traveler?.position?.department?.department_name ||
                    'N/A');
                $('#modal_traveler_position').text(lot.traveler?.position?.position_name || 'N/A');
                $('#modal_traveler_project').text(lot.traveler?.project?.project_name || 'N/A');
                $('#modal_traveler_nik').text(lot.traveler?.nik || 'N/A');
                $('#modal_traveler_class').text(lot.traveler?.class || 'N/A');

                // Approval Plans Table (replaces old recommendation/approval fields)
                const plans = Array.isArray(lot.approval_plans) ? lot.approval_plans : [];
                const plansHtml = plans.length ? plans.map(plan => {
                    const approverName = plan.approver?.name || '-';
                    const statusText = (plan.status === 1 ? 'APPROVED' : plan.status === 0 ?
                        'PENDING' : plan.status === -1 ? 'REJECTED' : (plan.status ?? 'N/A'));
                    return `
                        <tr>
                            <td>${approverName}</td>
                            <td>${statusText}</td>
                        </tr>
                    `;
                }).join('') : '<tr><td colspan="2" class="text-center">No approval plans</td></tr>';
                $('#modal_approval_plans').html(plansHtml);

                // Transportation & Accommodation
                $('#modal_transportation').text(lot.transportation?.transportation_name || 'N/A');
                $('#modal_accommodation').text(lot.accommodation?.accommodation_name || 'N/A');

                // Travel Followers
                const followersHtml = lot.details?.map(detail => `
                    <tr>
                        <td>${detail.follower?.employee?.fullname || 'N/A'}</td>
                        <td>${detail.follower?.position?.department?.department_name || 'N/A'}</td>
                        <td>${detail.follower?.position?.position_name || 'N/A'}</td>
                        <td>${detail.follower?.project?.project_name || 'N/A'}</td>
                    </tr>
                `).join('') || '<tr><td colspan="4" class="text-center">No followers</td></tr>';

                $('#modal_followers').html(followersHtml);

                // Store LOT number for pick button
                $('#modal_pick_lot').data('lot-no', lot.official_travel_number);

                // Show modal
                $('#lotDetailModal').modal('show');
            });

            // Handle LOT selection from modal
            $('#modal_pick_lot').click(function() {
                const lotNo = $(this).data('lot-no');
                $('#selected_lot_no').val(lotNo);
                $('#lotDetailModal').modal('hide');

                showSelectedLot(lotNo);
            });

            // Function to show selected LOT
            function showSelectedLot(lotNo) {
                // Show selected LOT number
                if (!$('#selected_lot_display').length) {
                    $('#lot_search_form').after(`
                        <div class="alert alert-info" id="selected_lot_display">
                            Selected LOT Number: <strong>${lotNo}</strong>
                        </div>
                    `);
                } else {
                    $('#selected_lot_display').html(`Selected LOT Number: <strong>${lotNo}</strong>`);
                }
            }

            @if ($advanceCanSelectRab)
                function recalcAdvanceAllocationTotal() {
                    let sum = 0;
                    $('#advance-allocation-body tr.allocation-row .allocation-amount').each(function() {
                        const raw = ($(this).val() || '').replace(/,/g, '');
                        const v = parseFloat(raw);
                        if (!isNaN(v)) {
                            sum += v;
                        }
                    });
                    const formatted = sum.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    $('#amount').val(formatted);
                }

                function applyAdvanceBudgetMode() {
                    const multi = $('#budget_mode_multi').is(':checked');
                    if (multi) {
                        $('#advance-budget-multi').show();
                        $('#advance-budget-legacy').hide();
                        $('#rab_id_legacy').prop('disabled', true);
                        $('#amount-help-multi').show();
                        $('#amount-help-legacy').hide();
                        $('#amount').prop('readonly', true).off('keyup.advanceAmount');
                        recalcAdvanceAllocationTotal();
                    } else {
                        $('#advance-budget-multi').hide();
                        $('#advance-budget-legacy').show();
                        $('#rab_id_legacy').prop('disabled', false);
                        $('#amount-help-multi').hide();
                        $('#amount-help-legacy').show();
                        $('#amount').prop('readonly', false).off('keyup.advanceAmount').on(
                            'keyup.advanceAmount',
                            function() {
                                formatNumber(this);
                            });
                    }
                }

                $('input[name="budget_link_mode"]').on('change', applyAdvanceBudgetMode);
                applyAdvanceBudgetMode();

                $('#advance-allocation-body').on('keyup change', '.allocation-amount',
                    recalcAdvanceAllocationTotal);

                $('#btn-add-allocation-row').click(function() {
                    const $body = $('#advance-allocation-body');
                    const $first = $body.find('tr.allocation-row').first();
                    const idx = $body.find('tr.allocation-row').length;
                    const $clone = $first.clone();
                    $clone.find('input, select').each(function() {
                        const $el = $(this);
                        const name = $el.attr('name');
                        if (name && name.indexOf('allocations[') === 0) {
                            $el.attr('name', name.replace(/allocations\[\d+\]/, 'allocations[' +
                                idx + ']'));
                        }
                        if ($el.hasClass('allocation-amount')) {
                            $el.val('');
                        }
                        if ($el.hasClass('allocation-rab-select')) {
                            $el.val('');
                        }
                        if ($el.is('input[type=text]:not(.allocation-amount)') && !$el.hasClass(
                                'allocation-rab-select')) {
                            const nm = $el.attr('name') || '';
                            if (nm.indexOf('[remarks]') !== -1) {
                                $el.val('');
                            }
                        }
                    });
                    $body.append($clone);
                });

                $('#advance-allocation-body').on('click', '.btn-remove-allocation-row', function() {
                    const $body = $('#advance-allocation-body');
                    if ($body.find('tr.allocation-row').length <= 1) {
                        return;
                    }
                    $(this).closest('tr.allocation-row').remove();
                    recalcAdvanceAllocationTotal();
                });
            @endif

            // btn-save as draft
            $('#btn-draft').click(function() {
                $('form').append('<input type="hidden" name="button_type" value="create">');
            });

            // btn-save and submit
            $('#btn-submit').click(function() {
                $('form').append('<input type="hidden" name="button_type" value="create_submit">');
            });
        });
    </script>
@endsection
