<div class="col-6"></div>

@can('see_activities_chart')
<div class="col-6">
    <div class="card card-info">
        <div class="card-header border-0">
            <div class="d-flex justify-content-between">
                <h3 class="card-title">VJ Sync Activities</h3>
                {{-- <a href="javascript:void(0);">View Report</a> --}}
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex">
                <p class="d-flex flex-column">
                <span class="text-bold text-lg">Total: {{ $chart_activites['activities_count'] }}</span>
                <span>This Year Activities</span>
                </p>
                <p class="ml-auto d-flex flex-column text-right">
                <span class="text-success">
                    {{-- <i class="fas fa-arrow-up"></i> 33.1% --}}
                </span>
                {{-- <span class="text-muted">Since last month</span> --}}
                </p>
            </div>

            <div class="position-relative mb-4">
                <canvas id="activities-chart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>
@endcan
