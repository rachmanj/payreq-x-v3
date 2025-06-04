@extends('templates.main')

@section('title_page')
    Announcements
@endsection

@section('breadcrumb_title')
    announcements
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Announcement Details</h3>
                    <div class="card-tools">
                        <div class="btn-group" role="group">
                            <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-secondary mr-2">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-sm btn-warning mr-2">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="{{ route('announcements.toggle_status', $announcement) }}" method="POST"
                                style="display: inline;" class="mr-2"
                                onsubmit="return confirm('Are you sure you want to change the status of this announcement?')">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-toggle-{{ $announcement->status === 'active' ? 'off' : 'on' }}"></i>
                                    {{ $announcement->status === 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form action="{{ route('announcements.destroy', $announcement) }}" method="POST"
                                style="display: inline;"
                                onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Content Section -->
                        <div class="col-md-8">
                            <h5><strong>Announcement Content</strong></h5>
                            <div class="border rounded p-3 mb-3" style="line-height: 1.6; min-height: 200px;">
                                {!! $announcement->content !!}
                            </div>
                        </div>

                        <!-- Info Section -->
                        <div class="col-md-4">
                            <h5><strong>Announcement Information</strong></h5>

                            <!-- Status -->
                            <div class="info-box mb-2">
                                <span class="info-box-icon">
                                    @if ($announcement->status === 'active')
                                        @if ($announcement->is_current)
                                            <i class="fas fa-check-circle text-success"></i>
                                        @elseif($announcement->is_expired)
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                        @else
                                            <i class="fas fa-clock text-primary"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-times-circle text-secondary"></i>
                                    @endif
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Status</span>
                                    <span class="info-box-number">
                                        @if ($announcement->status === 'active')
                                            @if ($announcement->is_current)
                                                <span class="badge badge-success">🟢 Active & Current</span>
                                            @elseif($announcement->is_expired)
                                                <span class="badge badge-warning">🟡 Active but Expired</span>
                                            @else
                                                <span class="badge badge-primary">🔵 Active (Future)</span>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">⚫ Inactive</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <!-- Period -->
                            <div class="info-box mb-2">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Period</span>
                                    <span class="info-box-number text-sm">
                                        {{ $announcement->start_date->format('d/m/Y') }} -
                                        {{ $announcement->end_date->format('d/m/Y') }}
                                        <br><small>({{ $announcement->duration_days }} days)</small>
                                    </span>
                                </div>
                            </div>

                            <!-- Target Roles -->
                            <div class="info-box mb-2">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-users"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Target Roles</span>
                                    <span class="info-box-number text-sm">
                                        @if ($announcement->target_roles)
                                            @foreach ($announcement->target_roles as $role)
                                                <span class="badge badge-info mr-1">{{ ucfirst($role) }}</span>
                                            @endforeach
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <!-- Creator -->
                            <div class="info-box mb-2">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-user"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Created By</span>
                                    <span class="info-box-number text-sm">
                                        {{ $announcement->creator->name }}
                                        <br><small>{{ $announcement->created_at->format('d/m/Y H:i') }}</small>
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            @if ($announcement->status === 'active')
                                <div class="mt-3">
                                    <h6><strong>Time Progress</strong></h6>
                                    @php
                                        $now = now();
                                        $startDate = $announcement->start_date;
                                        $endDate = $announcement->end_date;
                                        $totalDays = $announcement->duration_days;

                                        // Calculate days passed since start date
                                        if ($now->lt($startDate)) {
                                            // Not started yet
                                            $daysPassed = 0;
                                            $daysUntilStart = $startDate->diffInDays($now);
                                        } else {
                                            // Already started
                                            $daysPassed = $startDate->diffInDays($now);
                                            $daysUntilStart = 0;
                                        }

                                        // Calculate days remaining until end date
                                        if ($now->gt($endDate)) {
                                            // Already expired
                                            $daysRemaining = 0;
                                            $daysAfterExpiry = $now->diffInDays($endDate);
                                        } else {
                                            // Still active or future
                                            $daysRemaining = $now->diffInDays($endDate);
                                            $daysAfterExpiry = 0;
                                        }

                                        // Calculate progress percentage
                                        if ($totalDays > 0) {
                                            $progressPercentage = min(100, max(0, ($daysPassed / $totalDays) * 100));
                                        } else {
                                            $progressPercentage = 0;
                                        }
                                    @endphp

                                    <div class="progress mb-2">
                                        <div class="progress-bar
                                        @if ($announcement->is_expired) bg-danger
                                        @elseif($progressPercentage > 80) bg-warning
                                        @else bg-success @endif"
                                            role="progressbar" style="width: {{ $progressPercentage }}%"
                                            aria-valuenow="{{ $progressPercentage }}" aria-valuemin="0"
                                            aria-valuemax="100">
                                            {{ round($progressPercentage) }}%
                                        </div>
                                    </div>

                                    <small class="text-muted">
                                        @if ($announcement->is_current)
                                            {{ $daysRemaining }} {{ $daysRemaining == 1 ? 'day' : 'days' }} remaining
                                        @elseif($announcement->is_expired)
                                            Expired {{ $daysAfterExpiry }} {{ $daysAfterExpiry == 1 ? 'day' : 'days' }}
                                            ago
                                        @else
                                            Starts in {{ $daysUntilStart }} {{ $daysUntilStart == 1 ? 'day' : 'days' }}
                                        @endif
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
