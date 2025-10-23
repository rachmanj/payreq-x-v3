@can('see_activities_chart')
    <div class="col-lg-6 col-12">
        <div class="modern-chart-card">
            <div class="chart-card-header">
                <div class="chart-header-content">
                    <div class="chart-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="chart-header-text">
                        <h4 class="chart-title mb-0">VJ Sync Activities</h4>
                        <small>User contributions this year</small>
                    </div>
                </div>
                <div class="chart-header-stats">
                    <span class="chart-stat-value">{{ $chart_activites['activities_count'] }}</span>
                    <span class="chart-stat-label">Total Activities</span>
                </div>
            </div>
            <div class="modern-chart-body">
                <div class="chart-container">
                    <canvas id="activities-chart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
@endcan

<style>
    .modern-chart-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .modern-chart-card:hover {
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }

    .chart-card-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .chart-header-content {
        display: flex;
        align-items: center;
        flex: 1;
    }

    .chart-icon {
        background: rgba(255, 255, 255, 0.2);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    .chart-icon i {
        font-size: 24px;
        color: #fff;
    }

    .chart-header-text {
        color: #fff;
    }

    .chart-header-text h4 {
        color: #fff;
        font-size: 18px;
        font-weight: 600;
    }

    .chart-header-text small {
        color: rgba(255, 255, 255, 0.8);
        font-size: 12px;
    }

    .chart-header-stats {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        background: rgba(255, 255, 255, 0.2);
        padding: 10px 20px;
        border-radius: 8px;
    }

    .chart-stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #fff;
        line-height: 1;
    }

    .chart-stat-label {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.9);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 5px;
    }

    .modern-chart-body {
        padding: 20px;
    }

    .chart-container {
        position: relative;
    }
</style>
