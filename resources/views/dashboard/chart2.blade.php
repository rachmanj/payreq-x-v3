@can('see_activities_chart')
    <x-dashboard.panel
        title="VJ Sync Activities"
        subtitle="User contributions this year"
        icon="fas fa-chart-pie"
        gradient="tw-bg-gradient-to-br tw-from-sky-400 tw-to-cyan-300">
        <x-slot:headerExtra>
            <div class="tw-flex tw-flex-col tw-items-end tw-bg-white/20 tw-px-5 tw-py-2.5 tw-rounded-lg">
                <span class="tw-text-[28px] tw-font-bold tw-text-white tw-leading-none">{{ $chart_activites['activities_count'] }}</span>
                <span class="tw-text-[11px] tw-text-white/90 tw-uppercase tw-tracking-wide tw-mt-1">Total Activities</span>
            </div>
        </x-slot:headerExtra>

        <div class="tw-relative">
            <canvas id="activities-chart" height="200"></canvas>
        </div>
    </x-dashboard.panel>
@endcan
