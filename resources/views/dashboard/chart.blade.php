<div class="col-lg-12">
    <div class="modern-monthly-chart-card">
        <div class="monthly-chart-header">
            <div class="monthly-header-content">
                <div class="monthly-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="monthly-header-text">
                    <h4 class="monthly-title mb-0">Your Monthly Spending</h4>
                    <small>Track your monthly payment requests (in thousands)</small>
                </div>
            </div>
        </div>
        <div class="monthly-chart-body">
            <div class="chart-wrapper">
                <canvas id="monthly-chart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<style>
    .modern-monthly-chart-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .modern-monthly-chart-card:hover {
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }

    .monthly-chart-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px;
    }

    .monthly-header-content {
        display: flex;
        align-items: center;
    }

    .monthly-icon {
        background: rgba(255, 255, 255, 0.2);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    .monthly-icon i {
        font-size: 24px;
        color: #fff;
    }

    .monthly-header-text h4 {
        color: #fff;
        font-size: 18px;
        font-weight: 600;
    }

    .monthly-header-text small {
        color: rgba(255, 255, 255, 0.8);
        font-size: 12px;
    }

    .monthly-chart-body {
        padding: 30px 20px;
    }

    .chart-wrapper {
        position: relative;
    }
</style>
