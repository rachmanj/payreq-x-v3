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
            <!-- Header Section -->
            <div class="card bg-white shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">{{ $lotclaim->lot_no }}</h4>
                            <p class="text-muted mb-0">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                {{ date('d F Y', strtotime($lotclaim->claim_date)) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="d-flex flex-row flex-wrap justify-content-end gap-2 action-btn-group">
                                <a href="{{ route('user-payreqs.lotclaims.index') }}"
                                    class="btn btn-outline-primary flex-fill text-nowrap mb-2 mb-md-0">
                                    <i class="fas fa-arrow-left mr-1"></i> Back
                                </a>
                                @if ($lotclaim->is_claimed == 'no')
                                    <a href="{{ route('user-payreqs.lotclaims.edit', ['lotclaim' => $lotclaim->id]) }}"
                                        class="btn btn-outline-warning flex-fill text-nowrap mb-2 mb-md-0">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </a>
                                    <form
                                        action="{{ route('user-payreqs.lotclaims.destroy', ['lotclaim' => $lotclaim->id]) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this lot claim?');"
                                        class="d-flex flex-fill text-nowrap mb-2 mb-md-0" style="min-width:0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger flex-fill w-100 text-nowrap">
                                            <i class="fas fa-trash mr-1"></i> Delete
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('user-payreqs.lotclaims.print', ['lotclaim' => $lotclaim->id]) }}"
                                    target="_blank" class="btn btn-outline-info flex-fill text-nowrap mb-2 mb-md-0">
                                    <i class="fas fa-print mr-1"></i> Print
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-4">
                    <!-- LOT Detail Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-file-invoice text-primary mr-2"></i>
                                LOT Detail
                            </h5>
                        </div>
                        <div class="card-body">
                            @if (isset($lotDetail['data'][0]))
                                <div class="info-item mb-3">
                                    <label class="text-muted small">LOT Origin</label>
                                    <p class="mb-0 font-weight-bold">
                                        {{ $lotDetail['data'][0]['project']['project_code'] ?? '-' }}
                                    </p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="text-muted small">LOT Date</label>
                                    <p class="mb-0 font-weight-bold">
                                        {{ date('d F Y', strtotime($lotDetail['data'][0]['official_travel_date'])) }}</p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="text-muted small">Purpose</label>
                                    <p class="mb-0 font-weight-bold">{{ $lotDetail['data'][0]['purpose'] }}</p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="text-muted small">Destination</label>
                                    <p class="mb-0 font-weight-bold">{{ $lotDetail['data'][0]['destination'] }}</p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="text-muted small">Duration</label>
                                    <p class="mb-0 font-weight-bold">{{ $lotDetail['data'][0]['duration'] }}</p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="text-muted small">Departure Date</label>
                                    <p class="mb-0 font-weight-bold">
                                        {{ date('d F Y', strtotime($lotDetail['data'][0]['departure_from'])) }}</p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="text-muted small">Traveler</label>
                                    <p class="mb-0 font-weight-bold">
                                        {{ $lotDetail['data'][0]['traveler']['employee']['fullname'] ?? '-' }}
                                    </p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="text-muted small">Followers</label>
                                    @foreach ($lotDetail['data'][0]['details'] as $detail)
                                        <p class="mb-0 font-weight-bold">
                                            {{ $detail['follower']['employee']['fullname'] ?? '-' }}
                                        </p>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-8">
                    <!-- Details Card -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <ul class="nav nav-pills nav-fill mb-2" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#accommodations">
                                        <i class="fas fa-hotel mr-1"></i>Accommodations
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#travels">
                                        <i class="fas fa-plane mr-1"></i>Travels
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#meals">
                                        <i class="fas fa-utensils mr-1"></i>Meals
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- Accommodations Tab -->
                                <div class="tab-pane fade show active" id="accommodations">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th class="text-right">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($lotclaim->accommodations as $accommodation)
                                                    <tr>
                                                        <td>{{ $accommodation->description }}
                                                            <small class="text-muted">
                                                                {{ $accommodation->notes ? 'Note: ' . $accommodation->notes : '' }}
                                                            </small>
                                                        </td>
                                                        <td class="text-right">Rp
                                                            {{ number_format($accommodation->accommodation_amount, 2, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted py-4">
                                                            <i class="fas fa-hotel fa-2x mb-2"></i>
                                                            <p class="mb-0">No accommodations recorded</p>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                            <tfoot class="bg-light">
                                                <tr>
                                                    <th>Total</th>
                                                    <th class="text-right">Rp
                                                        {{ number_format($lotclaim->accommodation_total, 2, ',', '.') }}
                                                    </th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <!-- Travels Tab -->
                                <div class="tab-pane fade" id="travels">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th class="text-right">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($lotclaim->travels as $travel)
                                                    <tr>
                                                        <td>{{ $travel->description }}
                                                            <small class="text-muted">
                                                                {{ $travel->notes ? 'Note: ' . $travel->notes : '' }}
                                                            </small>
                                                        </td>
                                                        <td class="text-right">Rp
                                                            {{ number_format($travel->travel_amount, 2, ',', '.') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted py-4">
                                                            <i class="fas fa-plane fa-2x mb-2"></i>
                                                            <p class="mb-0">No travels recorded</p>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                            <tfoot class="bg-light">
                                                <tr>
                                                    <th>Total</th>
                                                    <th class="text-right">Rp
                                                        {{ number_format($lotclaim->travel_total, 2, ',', '.') }}</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <!-- Meals Tab -->
                                <div class="tab-pane fade" id="meals">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th class="text-right">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($lotclaim->meals as $meal)
                                                    <tr>
                                                        <td>{{ ucfirst($meal->meal_type) }}
                                                            <small class="text-muted">
                                                                {{ $meal->notes ? 'Note: ' . $meal->notes : '' }}
                                                            </small>
                                                        </td>
                                                        <td class="text-right">Rp
                                                            {{ number_format($meal->meal_amount, 2, ',', '.') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted py-4">
                                                            <i class="fas fa-utensils fa-2x mb-2"></i>
                                                            <p class="mb-0">No meals recorded</p>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                            <tfoot class="bg-light">
                                                <tr>
                                                    <th>Total</th>
                                                    <th class="text-right">Rp
                                                        {{ number_format($lotclaim->meal_total, 2, ',', '.') }}</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Budget Status Card -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-chart-pie text-success mr-2"></i>
                                Budget Status
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="budget-status mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Advance Amount</span>
                                    <span class="font-weight-bold text-success">Rp
                                        {{ number_format($lotclaim->advance_amount, 2, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Total Claim</span>
                                    <span class="font-weight-bold text-primary">Rp
                                        {{ number_format($lotclaim->total_claim, 2, ',', '.') }}</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Difference</span>
                                    <span
                                        class="font-weight-bold {{ $lotclaim->difference < 0 ? 'text-danger' : 'text-success' }}">
                                        Rp {{ number_format($lotclaim->difference, 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks Card -->
                    @if ($lotclaim->claim_remarks)
                        <div class="card shadow-sm mt-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-comment-alt text-info mr-2"></i>
                                    Remarks
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">{{ $lotclaim->claim_remarks }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .card {
            border: none;
            border-radius: 10px;
        }

        .shadow-sm {
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
        }

        .nav-pills .nav-link {
            color: #6c757d;
            border-radius: 5px;
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
        }

        .nav-pills .nav-link.active {
            background-color: #007bff;
            color: white;
        }

        .info-item label {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .info-item p {
            font-size: 1rem;
        }

        .budget-status {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
        }

        .table th {
            font-weight: 600;
            border-top: none;
            color: #6c757d;
        }

        .table tfoot th {
            border-bottom: none;
        }

        .badge {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .text-muted {
            color: #6c757d !important;
        }

        @media (min-width: 768px) {
            .action-btn-group>* {
                margin-left: 0.5rem;
                margin-bottom: 0 !important;
            }

            .action-btn-group>*:first-child {
                margin-left: 0 !important;
            }
        }

        @media (max-width: 767.98px) {
            .action-btn-group>* {
                width: 75%;
                margin-bottom: 0.5rem !important;
            }
        }
    </style>
@endsection
