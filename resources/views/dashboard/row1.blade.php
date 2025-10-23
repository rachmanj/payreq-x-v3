<div class="col-lg-4 col-md-6 col-12">
    <div class="modern-info-box">
        <div class="info-box-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="info-box-content">
            <div class="info-box-label">Waiting for Your Approval</div>
            <div class="info-box-number">{{ $wait_approve }}</div>
            <div class="info-box-text">{{ $wait_approve === 1 ? 'document' : 'documents' }} pending</div>
        </div>
        <a href="{{ route('approvals.request.payreqs.index') }}" class="info-box-footer">
            View Approvals <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>
</div>

<style>
    .modern-info-box {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
        margin-bottom: 20px;
        position: relative;
    }

    .modern-info-box:hover {
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.15);
        transform: translateY(-5px);
    }

    .modern-info-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
    }

    .info-box-icon {
        background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        padding: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .info-box-icon i {
        font-size: 48px;
        color: #fff;
    }

    .info-box-content {
        padding: 20px;
    }

    .info-box-label {
        font-size: 14px;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-box-number {
        font-size: 36px;
        font-weight: bold;
        color: #fda085;
        margin-bottom: 5px;
    }

    .info-box-text {
        font-size: 14px;
        color: #6c757d;
    }

    .info-box-footer {
        display: block;
        background: #f8f9fa;
        padding: 12px 20px;
        text-align: center;
        color: #495057;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border-top: 1px solid #e9ecef;
    }

    .info-box-footer:hover {
        background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        color: #fff;
        text-decoration: none;
    }
</style>
