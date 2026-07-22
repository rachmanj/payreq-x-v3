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
    <div class="tw-mb-5 tw-space-y-4">
        @foreach ($activeAnnouncements as $announcement)
            <div class="tw-bg-white tw-rounded-xl tw-shadow-card tw-overflow-hidden tw-transition-all tw-duration-300 hover:tw-shadow-card-hover alert-dismissible fade show"
                role="alert">
                <div class="tw-bg-gradient-to-br tw-from-pink-400 tw-to-rose-500 tw-px-5 tw-py-4 tw-flex tw-items-center">
                    <div class="tw-bg-white/20 tw-w-10 tw-h-10 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mr-4 tw-shrink-0">
                        <i class="fas fa-bullhorn tw-text-white"></i>
                    </div>
                    <h5 class="tw-text-white tw-text-lg tw-font-semibold tw-mb-0 tw-flex-1">Announcement</h5>
                    <button type="button" class="close tw-text-white tw-opacity-80 hover:tw-opacity-100 tw-text-[28px] tw-font-light tw-leading-none tw-bg-transparent tw-border-0"
                        data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="tw-p-5">
                    <div class="tw-leading-relaxed tw-text-gray-600">{!! $announcement->content !!}</div>
                </div>
                <div class="tw-bg-gray-50 tw-px-5 tw-py-4 tw-border-t tw-border-gray-200">
                    <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-x-4 tw-gap-y-2 tw-text-[13px] tw-text-gray-500">
                        <span class="tw-inline-flex tw-items-center tw-gap-1">
                            <i class="fas fa-calendar-alt"></i>
                            <strong>Period:</strong> {{ $announcement->start_date->format('d/m/Y') }} -
                            {{ $announcement->end_date->format('d/m/Y') }}
                            <span class="badge badge-light ml-1">{{ $announcement->duration_days }} days</span>
                        </span>
                        <span class="tw-inline-flex tw-items-center tw-gap-1">
                            <i class="fas fa-users"></i>
                            <strong>Target:</strong>
                            @foreach (explode(', ', $announcement->target_roles_string) as $role)
                                <span class="badge badge-info ml-1">{{ $role }}</span>
                            @endforeach
                        </span>
                        @if (auth()->user()->hasRole('superadmin'))
                            <span class="tw-inline-flex tw-items-center tw-gap-1">
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
@endif
