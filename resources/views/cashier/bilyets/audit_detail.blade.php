@extends('templates.main')

@section('title_page', 'Audit Detail')

@section('content')
    <x-bilyet-links page="audit" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-search"></i> Audit Detail
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('cashier.bilyets.audit.index') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-arrow-left"></i> Back to Audit Trail
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Audit Info -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-calendar"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Date & Time</span>
                                        <span class="info-box-number">{{ $audit->created_at->format('d M Y H:i:s') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span
                                        class="info-box-icon bg-{{ $audit->action == 'created' ? 'success' : ($audit->action == 'voided' ? 'danger' : 'warning') }}">
                                        <i
                                            class="fas fa-{{ $audit->action == 'created' ? 'plus' : ($audit->action == 'voided' ? 'times' : 'edit') }}"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Action</span>
                                        <span class="info-box-number">{{ $audit->action_label }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Info -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-primary">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">User</span>
                                        <span class="info-box-number">{{ $audit->user->name ?? 'Unknown' }}</span>
                                        <small>{{ $audit->user->email ?? 'N/A' }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-secondary">
                                        <i class="fas fa-globe"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">IP Address</span>
                                        <span class="info-box-number">{{ $audit->ip_address ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bilyet Info -->
                        @if ($audit->bilyet)
                            <div class="alert alert-info">
                                <h5><i class="fas fa-file-invoice"></i> Bilyet Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Number:</strong> {{ $audit->bilyet->full_nomor }}</p>
                                        <p><strong>Bank:</strong> {{ $audit->bilyet->giro->bank->name ?? 'N/A' }}</p>
                                        <p><strong>Amount:</strong> {{ number_format($audit->bilyet->amount, 2) }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Type:</strong> {{ $audit->bilyet->type_label }}</p>
                                        <p><strong>Status:</strong>
                                            <span
                                                class="badge badge-{{ $audit->bilyet->status == 'onhand' ? 'primary' : ($audit->bilyet->status == 'release' ? 'warning' : ($audit->bilyet->status == 'cair' ? 'success' : 'danger')) }}">
                                                {{ $audit->bilyet->status_label }}
                                            </span>
                                        </p>
                                        <p><strong>Project:</strong> {{ $audit->bilyet->project ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <a href="{{ route('cashier.bilyets.history', $audit->bilyet_id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-history"></i> View Full History
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> This bilyet has been deleted.
                            </div>
                        @endif

                        <!-- Changes Details -->
                        <div class="row">
                            <!-- Old Values -->
                            @if ($audit->old_values)
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-danger text-white">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-arrow-left"></i> Previous Values
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            @foreach ($audit->old_values as $field => $value)
                                                <div class="form-group">
                                                    <label
                                                        class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $field)) }}:</label>
                                                    <p class="form-control-plaintext">{{ $value ?? 'N/A' }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- New Values -->
                            @if ($audit->new_values)
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-arrow-right"></i> New Values
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            @foreach ($audit->new_values as $field => $value)
                                                <div class="form-group">
                                                    <label
                                                        class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $field)) }}:</label>
                                                    <p class="form-control-plaintext">{{ $value ?? 'N/A' }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Notes -->
                        @if ($audit->notes)
                            <div class="alert alert-light mt-4">
                                <h5><i class="fas fa-sticky-note"></i> Notes</h5>
                                <p class="mb-0">{{ $audit->notes }}</p>
                            </div>
                        @endif

                        <!-- User Agent -->
                        @if ($audit->user_agent)
                            <div class="alert alert-light">
                                <h5><i class="fas fa-desktop"></i> User Agent</h5>
                                <p class="mb-0"><small>{{ $audit->user_agent }}</small></p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .card-header .active {
            color: black;
            text-transform: uppercase;
        }
    </style>
@endsection
