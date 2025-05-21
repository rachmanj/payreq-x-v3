@extends('templates.main')

@section('title_page')
    Letter of Official Travel
@endsection

@section('breadcrumb_title')
    LOT Claims
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Letter of Official Travel</h3>
                    <a href="{{ route('user-payreqs.lotclaims.index') }}" class="btn btn-sm btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <div id="lot_search_form">
                        <div class="accordion" id="lotSearchAccordion">
                            <div class="card card-outline card-primary">
                                <div class="card-header" id="lotSearchHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left" type="button"
                                            data-toggle="collapse" data-target="#lotSearchCollapse" aria-expanded="true"
                                            aria-controls="lotSearchCollapse">
                                            <i class="fas fa-search mr-2"></i>Search Official Travel
                                        </button>
                                    </h2>
                                </div>
                                <div id="lotSearchCollapse" class="collapse" aria-labelledby="lotSearchHeading"
                                    data-parent="#lotSearchAccordion">
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
                                                    <input type="text" class="form-control" id="traveler"
                                                        name="traveler" value="{{ old('traveler') }}">
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
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('user-payreqs.lotclaims.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
                        <input type="hidden" name="accommodation_total" id="accommodation_total_input">
                        <input type="hidden" name="travel_total" id="travel_total_input">
                        <input type="hidden" name="meal_total" id="meal_total_input">
                        <input type="hidden" name="total_claim" id="total_claim_input">
                        <input type="hidden" name="difference" id="difference_input">
                        <input type="hidden" name="is_claimed" id="is_claimed_input" value="no">

                        <!-- Payment Request Alert -->
                        <div id="payment-request-alert" class="alert alert-warning alert-dismissible fade" role="alert"
                            style="display: none;">
                            <strong><i class="fas fa-exclamation-triangle"></i> Warning!</strong>
                            <span id="payment-request-message"></span>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="lot_no">LOT Number</label>
                                    <input type="text" id="selected_lot_no" name="lot_no"
                                        value="{{ old('lot_no') }}"
                                        class="form-control @error('lot_no') is-invalid @enderror" required readonly>
                                    @error('lot_no')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="claim_date" value="{{ old('claim_date') }}"
                                        class="form-control" required>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="project">Project</label>
                                    <input type="text" name="project" value="{{ auth()->user()->project }}"
                                        class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><strong>A. Advance</strong></h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-0">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="text" name="advance_amount" id="advance_amount"
                                            class="form-control text-right" value="{{ old('advance_amount') }}"
                                            onkeyup="formatNumber(this)" readonly>
                                    </div>
                                    @error('advance_amount')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><strong>B. Realization</strong></h3>
                            </div>
                            <div class="card-body">
                                <!-- Accommodations -->
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h3 class="card-title"><strong>B.1. Accommodations</strong></h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-sm btn-info"
                                                onclick="addAccommodationRow()">
                                                <i class="fas fa-plus"></i> Add Row
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm" id="accommodations_table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th class="align-middle">Description</th>
                                                        <th class="align-middle">Amount</th>
                                                        <th class="align-middle">Notes</th>
                                                        <th class="align-middle text-center" width="50">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><input type="text" name="accommodations[0][description]"
                                                                class="form-control" placeholder="Hotel, Laundry, etc.">
                                                        </td>
                                                        <td>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input type="text"
                                                                    name="accommodations[0][accommodation_amount]"
                                                                    class="form-control accommodation-amount text-right"
                                                                    onkeyup="formatNumber(this)">
                                                            </div>
                                                        </td>
                                                        <td><input type="text" name="accommodations[0][notes]"
                                                                class="form-control"></td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                onclick="removeRow(this)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot class="thead-light">
                                                    <tr>
                                                        <th colspan="1" class="text-right">Total:</th>
                                                        <th colspan="3">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input type="text" id="accommodation-total"
                                                                    class="form-control text-right" readonly>
                                                            </div>
                                                        </th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Travels -->
                                <div class="card card-outline card-success">
                                    <div class="card-header">
                                        <h3 class="card-title"><strong>B.2. Travels</strong></h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-sm btn-success"
                                                onclick="addTravelRow()">
                                                <i class="fas fa-plus"></i> Add Row
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm" id="travels_table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th class="align-middle">Description</th>
                                                        <th class="align-middle">Amount</th>
                                                        <th class="align-middle">Notes</th>
                                                        <th class="align-middle text-center" width="50">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><input type="text" name="travels[0][description]"
                                                                class="form-control"
                                                                placeholder="Airline Ticket, Airport Tax, Taxi, etc."></td>
                                                        <td>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input type="text" name="travels[0][travel_amount]"
                                                                    class="form-control travel-amount text-right"
                                                                    onkeyup="formatNumber(this)">
                                                            </div>
                                                        </td>
                                                        <td><input type="text" name="travels[0][notes]"
                                                                class="form-control"></td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                onclick="removeRow(this)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot class="thead-light">
                                                    <tr>
                                                        <th colspan="1" class="text-right">Total:</th>
                                                        <th colspan="3">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input type="text" id="travel-total"
                                                                    class="form-control text-right" readonly>
                                                            </div>
                                                        </th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Meals -->
                                <div class="card card-outline card-warning">
                                    <div class="card-header">
                                        <h3 class="card-title"><strong>B.3. Meals</strong></h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-sm btn-warning" onclick="addMealRow()">
                                                <i class="fas fa-plus"></i> Add Row
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm" id="meals_table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th class="align-middle">Meal Type</th>
                                                        <th class="align-middle">People Count</th>
                                                        <th class="align-middle">Limit</th>
                                                        <th class="align-middle">Times</th>
                                                        <th class="align-middle">Total</th>
                                                        <th class="align-middle">Notes</th>
                                                        <th class="align-middle text-center" width="50">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="meal-row">
                                                        <td>
                                                            <select name="meals[0][meal_type]" class="form-control">
                                                                <option value="breakfast">Breakfast</option>
                                                                <option value="lunch">Lunch</option>
                                                                <option value="dinner">Dinner</option>
                                                                <option value="other">Other</option>
                                                            </select>
                                                        </td>
                                                        <td><input type="number" name="meals[0][people_count]"
                                                                class="form-control people-count text-right"
                                                                min="1"></td>
                                                        <td>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input type="text" name="meals[0][per_person_limit]"
                                                                    class="form-control per-person-limit text-right"
                                                                    onkeyup="formatNumber(this)">
                                                            </div>
                                                        </td>
                                                        <td><input type="number" name="meals[0][frequency]"
                                                                class="form-control frequency text-right" min="1">
                                                        </td>
                                                        <td>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input type="text"
                                                                    class="form-control meal-total text-right" readonly>
                                                            </div>
                                                        </td>
                                                        <td><input type="text" name="meals[0][notes]"
                                                                class="form-control"></td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                onclick="removeRow(this)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot class="thead-light">
                                                    <tr>
                                                        <th colspan="4" class="text-right">Total:</th>
                                                        <th colspan="3">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input type="text" id="meal-total"
                                                                    class="form-control text-right" readonly>
                                                            </div>
                                                        </th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Section -->
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><strong>C. Summary</strong></h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>C. Total Claim (B1 + B2 + B3)</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="text" id="total-claim" class="form-control text-right"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>D. Difference (A - C)</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="text" id="difference" class="form-control text-right"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="claim_remarks">Remarks</label>
                            <textarea name="claim_remarks" id="claim_remarks" cols="30" rows="2"
                                class="form-control @error('claim_remarks') is-invalid @enderror">{{ old('claim_remarks') }}</textarea>
                            @error('claim_remarks')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-6">

                                </div>
                                <div class="col-6">
                                    <button type="submit" class="btn btn-warning btn-block" id="btn-submit"><i
                                            class="fas fa-save"></i> Submit</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- LOT Detail Modal -->
    <div class="modal fade" id="lotDetailModal" tabindex="-1" role="dialog" aria-labelledby="lotDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary">
                    <h5 class="modal-title" id="lotDetailModalLabel">
                        <i class="fas fa-plane-departure mr-2"></i>LOT Detail
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
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
                                <span class="badge badge-lg px-3 py-2" id="modal_status_badge"></span>
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
                                                <span id="modal_purpose" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">Destination</small>
                                                <span id="modal_destination" class="d-block mb-2"></span>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Duration</small>
                                                <span id="modal_duration" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">Departure
                                                    From</small>
                                                <span id="modal_departure_from" class="d-block mb-2"></span>
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
                                                <span id="modal_traveler_name" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">Department</small>
                                                <span id="modal_traveler_department" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">Position</small>
                                                <span id="modal_traveler_position" class="d-block mb-2"></span>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Project</small>
                                                <span id="modal_traveler_project" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">NIK</small>
                                                <span id="modal_traveler_nik" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">Class</small>
                                                <span id="modal_traveler_class" class="d-block mb-2"></span>
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
                                            <i class="fas fa-check-circle mr-1"></i>Approval Status
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Recommendation</small>
                                                <span id="modal_recommender_name" class="d-block"></span>
                                                <small class="text-muted d-block"
                                                    id="modal_recommendation_remark"></small>
                                                <small class="text-muted d-block mb-2"
                                                    id="modal_recommendation_date"></small>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Approval</small>
                                                <span id="modal_approver_name" class="d-block"></span>
                                                <small class="text-muted d-block" id="modal_approval_remark"></small>
                                                <small class="text-muted d-block mb-2" id="modal_approval_date"></small>
                                            </div>
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
                                                <small class="text-muted d-block">Transportation</small>
                                                <span id="modal_transportation" class="d-block mb-2"></span>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Accommodation</small>
                                                <span id="modal_accommodation" class="d-block mb-2"></span>
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
                                    <table class="table table-sm table-bordered table-striped mb-0">
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

        #lot_search_form {
            position: relative;
        }

        #lot_search_form .overlay {
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
            const hasLotNo = {{ old('lot_no') ? 'true' : 'false' }};

            // Show selected LOT if there was one
            if (hasLotNo) {
                const lotNo = '{{ old('lot_no') }}';
                if (lotNo) {
                    showSelectedLot(lotNo);
                }
            }

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
                $('#lot_search_form .overlay').remove();

                // Add loading overlay
                const $lotSearchForm = $('#lot_search_form');
                const $overlay = $(
                    '<div class="overlay"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>'
                );
                $lotSearchForm.append($overlay);

                $.ajax({
                    url: '{{ route('user-payreqs.lotclaims.search-lot') }}',
                    method: 'POST',
                    data: searchData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log(response);
                        // Remove loading overlay first
                        $('#lot_search_form .overlay').remove();

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
                        $('#lot_search_form .overlay').remove();

                        let errorMessage = 'Error searching LOT. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        showError(errorMessage);
                    },
                    complete: function() {
                        // Ensure overlay is removed even if there's an error
                        $('#lot_search_form .overlay').remove();
                    }
                });
            });

            function showError(message) {
                // Remove any existing overlay first
                $('#lot_search_form .overlay').remove();

                $('#lot_search_error .error-message').html(message);
                $('#lot_search_error').show();
                $('#lot_search_results').hide();
            }

            function displayLotResults(data) {
                // Remove any existing overlay first
                $('#lot_search_form .overlay').remove();

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

                // Set status badge
                const status = lot.official_travel_status || 'N/A';
                let badgeClass = 'badge-secondary';
                if (status === 'open') badgeClass = 'badge-success';
                if (status === 'closed') badgeClass = 'badge-danger';
                if (status === 'pending') badgeClass = 'badge-warning';

                $('#modal_status_badge')
                    .removeClass('badge-secondary badge-success badge-danger badge-warning')
                    .addClass(badgeClass)
                    .text(status.toUpperCase());

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

                // Approval Information
                $('#modal_recommender_name').text(lot.recommender.name || 'N/A');
                $('#modal_recommendation_remark').text(lot.recommendation_remark || '');
                $('#modal_recommendation_date').text(lot.recommendation_date || '');
                $('#modal_approver_name').text(lot.approver.name || 'N/A');
                $('#modal_approval_remark').text(lot.approval_remark || '');
                $('#modal_approval_date').text(lot.approval_date || '');

                // Transportation & Accommodation
                $('#modal_transportation').text(lot.transportation?.transportation_name || 'N/A');
                $('#modal_accommodation').text(lot.accommodation?.accommodation_name || 'N/A');

                // Payment Request
                $('#modal_payment_request_amount').text(lot.payment_request?.amount || 'N/A');

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
                $('#modal_pick_lot').data('lot', lot);

                // Show modal
                $('#lotDetailModal').modal('show');
            });

            // Handle LOT selection from modal
            $('#modal_pick_lot').click(function() {
                const lotNo = $(this).data('lot-no');
                const lot = $(this).data('lot');
                $('#selected_lot_no').val(lotNo);
                $('#lotDetailModal').modal('hide');

                // Check payment request status
                if (lot.payment_request && lot.payment_request.status ===
                    'No payment request found for this LOT number') {
                    $('#payment-request-message').text(
                        ' No payment request found for this LOT number. Please create payment request first.'
                    );
                    $('#payment-request-alert').addClass('show').show();
                    $('#advance_amount').val('').prop('readonly', true);
                    // Disable form submission
                    $('#btn-submit').prop('disabled', true);
                } else if (lot.payment_request && lot.payment_request.amount) {
                    $('#payment-request-alert').removeClass('show').hide();
                    $('#advance_amount').val(formatNumberInput(lot.payment_request.amount)).prop('readonly',
                        true);
                    // Enable form submission
                    $('#btn-submit').prop('disabled', false);
                }

                // Collapse the accordion
                $('#lotSearchCollapse').collapse('hide');

                showSelectedLot(lotNo);
                updateTotals();
            });

            // Function to show selected LOT
            function showSelectedLot(lotNo) {
                // Show selected LOT number
                if (!$('#selected_lot_display').length) {
                    $('#lot_search_form').after(`
                        <div class="alert alert-info alert-dismissible fade show" id="selected_lot_display">
                            Selected LOT Number: <strong>${lotNo}</strong>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `);
                } else {
                    $('#selected_lot_display').html(`Selected LOT Number: <strong>${lotNo}</strong>`);
                }
            }
        });
    </script>

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

    <script>
        // Function to format number input
        function formatNumberInput(number) {
            return number.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Function to parse number from formatted string
        function parseNumber(value) {
            if (!value) return 0;
            return parseFloat(value.toString().replace(/,/g, '')) || 0;
        }

        // Function to update all totals
        function updateTotals() {
            // Calculate accommodation total
            let accommodationTotal = 0;
            $('.accommodation-amount').each(function() {
                accommodationTotal += parseNumber($(this).val());
            });
            $('#accommodation-total').val(formatNumberInput(accommodationTotal));
            $('#accommodation_total_input').val(accommodationTotal);

            // Calculate travel total
            let travelTotal = 0;
            $('.travel-amount').each(function() {
                travelTotal += parseNumber($(this).val());
            });
            $('#travel-total').val(formatNumberInput(travelTotal));
            $('#travel_total_input').val(travelTotal);

            // Calculate meal totals
            let mealTotal = 0;
            $('.meal-row').each(function() {
                const peopleCount = parseInt($(this).find('.people-count').val()) || 0;
                const perPersonLimit = parseNumber($(this).find('.per-person-limit').val());
                const frequency = parseInt($(this).find('.frequency').val()) || 0;
                const rowTotal = peopleCount * perPersonLimit * frequency;
                $(this).find('.meal-total').val(formatNumberInput(rowTotal));
                mealTotal += rowTotal;
            });
            $('#meal-total').val(formatNumberInput(mealTotal));
            $('#meal_total_input').val(mealTotal);

            // Calculate total claim
            const totalClaim = accommodationTotal + travelTotal + mealTotal;
            $('#total-claim').val(formatNumberInput(totalClaim));
            $('#total_claim_input').val(totalClaim);

            // Calculate difference
            const advanceAmount = parseNumber($('#advance_amount').val());
            const difference = advanceAmount - totalClaim;
            $('#difference').val(formatNumberInput(difference));
            $('#difference_input').val(difference);

            // Highlight difference
            const differenceElement = $('#difference');
            if (difference < 0) {
                differenceElement.addClass('is-invalid').removeClass('is-valid');
            } else {
                differenceElement.addClass('is-valid').removeClass('is-invalid');
            }
        }

        // Add event listeners for dynamic calculations
        $(document).on('input',
            '.accommodation-amount, .travel-amount, .people-count, .per-person-limit, .frequency, #advance_amount',
            function() {
                updateTotals();
            });

        // Initialize totals on page load
        $(document).ready(function() {
            updateTotals();
        });
    </script>

    <script>
        // Function to add accommodation row
        function addAccommodationRow() {
            const tbody = $('#accommodations_table tbody');
            const rowCount = tbody.find('tr').length;

            const newRow = `
                <tr>
                    <td><input type="text" name="accommodations[${rowCount}][description]" class="form-control" placeholder="Hotel, Laundry, etc."></td>
                    <td>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" name="accommodations[${rowCount}][accommodation_amount]" class="form-control accommodation-amount text-right" onkeyup="formatNumber(this)">
                        </div>
                    </td>
                    <td><input type="text" name="accommodations[${rowCount}][notes]" class="form-control"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(newRow);
        }

        // Function to add travel row
        function addTravelRow() {
            const tbody = $('#travels_table tbody');
            const rowCount = tbody.find('tr').length;

            const newRow = `
                <tr>
                    <td><input type="text" name="travels[${rowCount}][description]" class="form-control" placeholder="Airline Ticket, Airport Tax, Taxi, etc."></td>
                    <td>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" name="travels[${rowCount}][travel_amount]" class="form-control travel-amount text-right" onkeyup="formatNumber(this)">
                        </div>
                    </td>
                    <td><input type="text" name="travels[${rowCount}][notes]" class="form-control"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(newRow);
        }

        // Function to add meal row
        function addMealRow() {
            const tbody = $('#meals_table tbody');
            const rowCount = tbody.find('tr').length;

            const newRow = `
                <tr class="meal-row">
                    <td>
                        <select name="meals[${rowCount}][meal_type]" class="form-control">
                            <option value="breakfast">Breakfast</option>
                            <option value="lunch">Lunch</option>
                            <option value="dinner">Dinner</option>
                            <option value="other">Other</option>
                        </select>
                    </td>
                    <td><input type="number" name="meals[${rowCount}][people_count]" class="form-control people-count text-right" min="1"></td>
                    <td>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" name="meals[${rowCount}][per_person_limit]" class="form-control per-person-limit text-right" onkeyup="formatNumber(this)">
                        </div>
                    </td>
                    <td><input type="number" name="meals[${rowCount}][frequency]" class="form-control frequency text-right" min="1"></td>
                    <td>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" class="form-control meal-total text-right" readonly>
                        </div>
                    </td>
                    <td><input type="text" name="meals[${rowCount}][notes]" class="form-control"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(newRow);
        }

        // Function to remove row
        function removeRow(button) {
            $(button).closest('tr').remove();
            updateTotals();
        }
    </script>
@endsection
