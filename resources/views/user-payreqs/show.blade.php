@extends('templates.main')

@section('title_page')
    My Payreqs
@endsection

@section('breadcrumb_title')
    payreqs
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Payment Request Detail</h3>
                    <a href="{{ route('user-payreqs.index') }}" class="btn btn-sm btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <dt class="col-sm-4">Payreq No</dt>
                        <dd class="col-sm-8">: {{ $payreq->nomor }}</dd>
                        <dt class="col-sm-4">Type</dt>
                        <dd class="col-sm-8">: {{ ucfirst($payreq->type) }}</dd>
                        <dt class="col-sm-4">Amount</dt>
                        <dd class="col-sm-8">: IDR {{ number_format($payreq->amount, 2) }}</dd>
                        <dt class="col-sm-4">Purpose</dt>
                        <dd class="col-sm-8">: {{ $payreq->remarks }}</dd>
                        <dt class="col-sm-4">LOT No</dt>
                        <dd class="col-sm-8">: {{ $payreq->lot_no ?? ' - ' }}
                            @if ($payreq->lot_no)
                                <button type="button" class="btn btn-sm btn-info ml-2" id="view_lot_detail">
                                    <strong>LOT Detail</strong>
                                </button>
                            @endif
                        </dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">:
                            {{ $payreq->status == 'submitted' ? 'Wait approve' : ucfirst($payreq->status) }}
                            {{ $paid_date }}</dd>
                        <dt class="col-sm-4">Submitted at</dt>
                        <dd class="col-sm-8">: {{ $submit_at }}</dd>
                        <dt class="col-sm-4">Due date</dt>
                        <dd class="col-sm-8">: {{ $due_date }}</dd>
                        <dt class="col-sm-4">RAB</dt>
                        <dd class="col-sm-8">:
                            {{ $payreq->rab_id ? 'No. ' . $payreq->anggaran->nomor . ' | ' . $payreq->anggaran->rab_project . ' | ' . $payreq->anggaran->description : '' }}
                        </dd>
                        <dt class="col-sm-4">Created at</dt>
                        <dd class="col-sm-8">: {{ $payreq->created_at->addHours(8)->format('d-M-Y H:i:s') . ' wita' }}</dd>
                    </div>
                </div>

                <div class="card-header">
                    <h3 class="card-title">Approval Status</h3>
                    @if ($payreq->status === 'approved' && $payreq->type !== 'other')
                        <form action="{{ route('user-payreqs.cancel') }}" method="POST">
                            @csrf
                            <input type="hidden" name="payreq_id" value="{{ $payreq->id }}">
                            <button type="submit" class="btn btn-sm btn-danger d-inline float-right"
                                onclick="return confirm('Are You sure You want to CANCEL this Payment Request? This transaction cannot be undone')">CANCEL</button>
                        </form>
                    @endif
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Approver</th>
                                <th>Status</th>
                                <th>Comment</th>
                                <th>Response at</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($approval_plans->count() > 0)
                                @foreach ($approval_plans as $key => $item)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $item->approver->name }}</td>
                                        @foreach ($approval_plan_status as $key => $value)
                                            @if ($key == $item->status)
                                                <td>{{ $value }}</td>
                                            @endif
                                        @endforeach
                                        <td>{{ $item->remarks }}</td>
                                        <td>{{ $item->status === 0 ? ' - ' : $item->updated_at->addHours(8)->format('d-M-Y H:i:s') . ' wita' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="text-center">No Approval Plans Found</td>
                                </tr>
                            @endif
                    </table>
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
                                            <i class="fas fa-info-circle mr-1"></i>Travel Information
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

                                                <small class="text-muted d-block">Departure From</small>
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
                                            <i class="fas fa-car mr-1"></i>Transportation & Accommodation
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
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- daterangepicker -->
    <script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>
    <script>
        $(function() {
            // Handle LOT detail view
            $('#view_lot_detail').click(function() {
                const lotNo = '{{ $payreq->lot_no }}';
                const $button = $(this);

                // Disable button and add spinner
                $button.prop('disabled', true);
                $button.html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
                );

                // Fetch LOT data
                $.ajax({
                    url: '{{ route('user-payreqs.advance.search-lot') }}',
                    method: 'POST',
                    data: {
                        travel_number: lotNo
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Reset button state
                        $button.prop('disabled', false);
                        $button.html('<strong>LOT Detail</strong>');

                        if (response.success && response.data && response.data.length > 0) {
                            const lot = response.data[0];

                            // Set status badge (use lot.status)
                            const status = lot.status || 'N/A';
                            let badgeClass = 'badge-secondary';
                            const normalizedStatus = String(status).toLowerCase();
                            if (['approved', 'open', 'active'].includes(normalizedStatus))
                                badgeClass = 'badge-success';
                            if (['rejected', 'declined', 'closed'].includes(normalizedStatus))
                                badgeClass = 'badge-danger';
                            if (['pending', 'in review'].includes(normalizedStatus))
                                badgeClass = 'badge-warning';

                            $('#modal_status_badge')
                                .removeClass(
                                    'badge-secondary badge-success badge-danger badge-warning')
                                .addClass(badgeClass)
                                .text(String(status).toUpperCase());

                            // Travel Information
                            $('#modal_travel_number').text(lot.official_travel_number || 'N/A');
                            $('#modal_travel_date').text(lot.official_travel_date ? moment(lot
                                .official_travel_date).format('DD MMMM YYYY') : 'N/A');
                            $('#modal_purpose').text(lot.purpose || 'N/A');
                            $('#modal_destination').text(lot.destination || 'N/A');
                            $('#modal_duration').text(lot.duration || 'N/A');
                            $('#modal_departure_from').text(lot.departure_from ? moment(lot
                                .departure_from).format('DD MMMM YYYY') : 'N/A');

                            // Traveler Information
                            $('#modal_traveler_name').text(lot.traveler?.employee?.fullname ||
                                'N/A');
                            $('#modal_traveler_department').text(lot.traveler?.position
                                ?.department?.department_name || 'N/A');
                            $('#modal_traveler_position').text(lot.traveler?.position
                                ?.position_name || 'N/A');
                            $('#modal_traveler_project').text(lot.traveler?.project
                                ?.project_name || 'N/A');
                            $('#modal_traveler_nik').text(lot.traveler?.nik || 'N/A');
                            $('#modal_traveler_class').text(lot.traveler?.class || 'N/A');

                            // Approval Plans (Approver, Status)
                            const plans = Array.isArray(lot.approval_plans) ? lot
                                .approval_plans : [];
                            const plansHtml = plans.length ? plans.map(plan => {
                                    const approverName = plan.approver?.name || '-';
                                    const statusText = (plan.status === 1 ? 'APPROVED' :
                                        plan.status === 0 ? 'PENDING' : plan.status ===
                                        -1 ? 'REJECTED' : (plan.status ?? 'N/A'));
                                    return `
                                    <tr>
                                        <td>${approverName}</td>
                                        <td>${statusText}</td>
                                    </tr>
                                `;
                                }).join('') :
                                '<tr><td colspan="2" class="text-center">No approval plans</td></tr>';
                            $('#modal_approval_plans').html(plansHtml);

                            // Transportation & Accommodation
                            $('#modal_transportation').text(lot.transportation
                                ?.transportation_name || 'N/A');
                            $('#modal_accommodation').text(lot.accommodation
                                ?.accommodation_name || 'N/A');

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

                            // Show modal
                            $('#lotDetailModal').modal('show');
                        } else {
                            alert('Failed to fetch LOT data');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Reset button state
                        $button.prop('disabled', false);
                        $button.html('<i class="fas fa-eye"></i> View LOT Detail');
                        alert('Error fetching LOT data');
                    }
                });
            });
        });
    </script>
@endsection
