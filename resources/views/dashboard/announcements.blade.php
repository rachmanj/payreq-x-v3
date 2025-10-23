@php
    $activeAnnouncements = \App\Models\Announcement::visibleToUser(auth()->user())
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($announcement) {
            $announcement->target_roles = collect($announcement->target_roles)->sort()->values()->all();
            return $announcement;
        });
@endphp

@if ($activeAnnouncements->count() > 0)
    <div class="row mb-3">
        <div class="col-12">
            @foreach ($activeAnnouncements as $announcement)
                <div class="modern-announcement-card alert-dismissible fade show" role="alert">
                    <div class="announcement-header">
                        <div class="announcement-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <h5 class="announcement-title mb-0">
                            <strong>Announcement</strong>
                        </h5>
                        <button type="button" class="close announcement-close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="announcement-body">
                        <div class="announcement-content">{!! $announcement->content !!}</div>
                    </div>
                    <div class="announcement-footer">
                        <div class="announcement-meta">
                            <span class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <strong>Period:</strong> {{ $announcement->start_date->format('d/m/Y') }} -
                                {{ $announcement->end_date->format('d/m/Y') }}
                                <span class="badge badge-light ml-1">{{ $announcement->duration_days }} days</span>
                            </span>
                            <span class="meta-item ml-3">
                                <i class="fas fa-users"></i>
                                <strong>Target:</strong>
                                @foreach (explode(', ', $announcement->target_roles_string) as $role)
                                    <span class="badge badge-info ml-1">{{ $role }}</span>
                                @endforeach
                            </span>
                            @if (auth()->user()->hasRole('superadmin'))
                                <span class="meta-item ml-3">
                                    <i class="fas fa-user"></i>
                                    <strong>Created by:</strong> {{ $announcement->creator->name }}
                                    <a href="{{ route('announcements.show', $announcement) }}"
                                        class="btn btn-sm btn-outline-primary ml-2">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .modern-announcement-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .modern-announcement-card:hover {
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .announcement-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 15px 20px;
            display: flex;
            align-items: center;
        }

        .announcement-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .announcement-icon i {
            color: #fff;
            font-size: 18px;
        }

        .announcement-title {
            color: #fff;
            font-size: 18px;
            flex: 1;
        }

        .announcement-close {
            color: #fff;
            opacity: 0.8;
            font-size: 28px;
            font-weight: 300;
            text-shadow: none;
        }

        .announcement-close:hover {
            opacity: 1;
        }

        .announcement-body {
            padding: 20px;
        }

        .announcement-content {
            line-height: 1.6;
            color: #495057;
        }

        .announcement-footer {
            background: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid #e9ecef;
        }

        .announcement-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            font-size: 13px;
            color: #6c757d;
        }

        .meta-item {
            display: inline-flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .meta-item i {
            margin-right: 5px;
        }

        .meta-item .badge {
            font-size: 11px;
        }
    </style>
@endif
