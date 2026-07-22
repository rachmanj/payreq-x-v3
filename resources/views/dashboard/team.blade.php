@can('see_team')
    <x-dashboard.panel
        title="Your Team Ongoings"
        subtitle="Track your team members' progress"
        icon="fas fa-users"
        gradient="tw-bg-gradient-to-br tw-from-cyan-400 tw-to-indigo-900">
        @if (count($your_team) > 0)
            @foreach ($your_team as $member)
                <div class="tw-bg-gray-50 tw-rounded-xl tw-p-4 tw-mb-4 last:tw-mb-0 tw-transition-all tw-duration-300 hover:tw-bg-gray-100">
                    <div class="tw-flex tw-items-center tw-mb-3">
                        <div class="tw-w-11 tw-h-11 tw-rounded-full tw-bg-gradient-to-br tw-from-cyan-400 tw-to-indigo-900 tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-lg tw-mr-3 tw-shrink-0">
                            {{ strtoupper(substr($member['name'], 0, 1)) }}
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <h5 class="tw-text-base tw-font-semibold tw-text-gray-700 tw-mb-0.5 tw-truncate">{{ $member['name'] }}</h5>
                            <span class="tw-text-[13px] tw-text-gray-500">
                                {{ count($member['ongoings']) }}
                                {{ count($member['ongoings']) === 1 ? 'payreq' : 'payreqs' }} ongoing
                            </span>
                        </div>
                    </div>

                    @if (count($member['ongoings']) > 0)
                        <div class="tw-space-y-2 tw-pl-0 sm:tw-pl-14">
                            @foreach ($member['ongoings'] as $payreq)
                                @php
                                    $statusKey = strtolower($payreq['status']);
                                    $statusBadge = match ($statusKey) {
                                        'draft' => 'tw-bg-blue-50 tw-text-blue-700',
                                        'submitted' => 'tw-bg-yellow-100 tw-text-yellow-800',
                                        'approved' => 'tw-bg-green-100 tw-text-green-800',
                                        default => 'tw-bg-gray-100 tw-text-gray-700',
                                    };
                                @endphp
                                <div class="tw-bg-white tw-rounded-lg tw-px-3 tw-py-2.5 tw-flex tw-flex-col sm:tw-flex-row sm:tw-justify-between sm:tw-items-center tw-gap-2 tw-transition-all tw-duration-300 hover:tw-shadow-md">
                                    <div class="tw-text-sm tw-text-gray-600 tw-flex-1 tw-min-w-0">
                                        <i class="fas fa-file-alt tw-text-gray-400 tw-mr-2"></i>
                                        {{ $payreq['description'] }}
                                    </div>
                                    <div class="tw-flex tw-items-center tw-gap-3 tw-flex-wrap tw-shrink-0">
                                        <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-px-2.5 tw-py-1 tw-rounded {{ $statusBadge }}">
                                            {{ $payreq['status'] }}
                                        </span>
                                        <span class="tw-text-[13px] tw-font-semibold tw-text-gray-700">
                                            Rp {{ $payreq['amount'] }}
                                        </span>
                                        <span @class([
                                            'tw-text-xs',
                                            'tw-text-red-600' => $payreq['days'] > 7,
                                            'tw-text-gray-500' => $payreq['days'] <= 7,
                                        ])>
                                            <i class="fas fa-clock tw-mr-1"></i>{{ $payreq['days'] }} days
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <x-dashboard.empty-state
                icon="fas fa-user-friends"
                title="No team data available"
                subtitle="Team information will appear here"
                icon-class="tw-text-gray-400" />
        @endif
    </x-dashboard.panel>
@endcan
