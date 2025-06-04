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
    <div class="row mb-0">
        <div class="col-12">
            @foreach ($activeAnnouncements as $announcement)
                <div class="alert alert-secondary alert-dismissible fade show rounded" role="alert">
                    <h5><strong>📢 Announcement</strong></h5>
                    <div style="line-height: 1.6;">{!! $announcement->content !!}</div>
                    <hr>
                    <small class="text-white">
                        <i class="fas fa-calendar-alt"></i>
                        <strong>Period:</strong> {{ $announcement->start_date->format('d/m/Y') }} -
                        {{ $announcement->end_date->format('d/m/Y') }}
                        ({{ $announcement->duration_days }} days)
                        <i class="fas fa-users ml-3"></i>
                        <strong>Target:</strong> {{ $announcement->target_roles_string }}

                        @if (auth()->user()->hasRole('superadmin'))
                            <br><i class="fas fa-user"></i> <strong>Created by:</strong>
                            {{ $announcement->creator->name }}
                            | <a href="{{ route('announcements.show', $announcement) }}" class="text-white">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        @endif
                    </small>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
@endif
