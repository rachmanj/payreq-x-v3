<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
        <div class="vj-inline-actions flex-wrap">
            <a href="{{ route('accounting.loans.dashboard') }}"
                class="vj-action-item {{ $page == 'dashboard' || $page == '' ? 'vj-action-export' : 'vj-action-print' }}">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="{{ route('accounting.loans.index') }}"
                class="vj-action-item {{ $page == 'index' ? 'vj-action-export' : 'vj-action-print' }}">
                <i class="fas fa-list"></i> Installment
            </a>
            <a href="{{ route('accounting.loans.audit.index') }}"
                class="vj-action-item {{ $page == 'audit' ? 'vj-action-export' : 'vj-action-print' }}">
                <i class="fas fa-clipboard-list"></i> Audit Trail
            </a>
            <a href="{{ route('reports.loan.dashboard') }}"
                class="vj-action-item {{ $page == 'reports' ? 'vj-action-export' : 'vj-action-print' }}">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </div>
    </div>
</div>
