<div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 xl:tw-grid-cols-3 tw-gap-4 tw-mb-5">
    <x-dashboard.kpi-card
        icon="fas fa-business-time"
        :value="number_format($avg_completion_days, 1)"
        label="Average Completion Days"
        :info="$avg_completion_days > 7 ? 'Needs attention' : 'On track'"
        :info-icon="$avg_completion_days > 7 ? 'fas fa-exclamation-circle' : 'fas fa-check-circle'"
        :tone="$avg_completion_days > 7 ? 'danger' : 'success'" />

    @can('see_vj_not_posted')
        <x-dashboard.kpi-card
            icon="fas fa-sync-alt"
            :value="$vj_not_posted"
            label="VJ to be Synced"
            info="Pending synchronization"
            info-icon="fas fa-info-circle"
            tone="primary"
            :href="route('accounting.sap-sync.index', ['page' => 'dashboard'])"
            title="Open SAP sync dashboard" />
    @endcan

    @can('validate_pcbc_report')
        @if (($pcbc_pending_validation_count ?? 0) === 0)
            <x-dashboard.kpi-card
                icon="fas fa-file-signature"
                :value="$pcbc_pending_validation_count"
                label="PCBC pending validation"
                info="No uploads waiting for validation"
                info-icon="fas fa-check-circle"
                tone="success"
                :href="route('cashier.pcbc.index', ['page' => 'upload'])"
                title="Open PCBC upload list" />
        @endif
    @endcan

    @can('validate_bank_reconciliation')
        @if (($bank_reconciliation_pending_validation_count ?? 0) === 0)
            <x-dashboard.kpi-card
                icon="fas fa-balance-scale"
                :value="$bank_reconciliation_pending_validation_count"
                label="Bank reconciliation pending"
                info="Nothing pending"
                info-icon="fas fa-check-circle"
                tone="success"
                :href="route('cashier.bank-reconciliation.index', ['view' => 'pending_validation'])"
                title="Open bank reconciliations"
                data-dashboard-pending-bank-reconciliation="0" />
        @endif
    @endcan

    @can('approve_overdue_extension')
        @if (($pending_overdue_extension_count ?? 0) === 0)
            <x-dashboard.kpi-card
                icon="fas fa-calendar-plus"
                :value="$pending_overdue_extension_count"
                label="Pending overdue extension requests"
                info="Nothing pending"
                info-icon="fas fa-check-circle"
                tone="success"
                :href="route('document-overdue.extensions.index')"
                title="Open overdue extension approvals"
                data-dashboard-pending-extension-requests="0" />
        @endif
    @endcan

    @can('akses_approvals')
        @if ($wait_approve === 0)
            <x-dashboard.kpi-card
                icon="fas fa-clock"
                :value="$wait_approve"
                label="Waiting for Your Approval"
                info="No documents pending"
                info-icon="fas fa-check-circle"
                tone="success"
                :href="route('approvals.request.payreqs.index')"
                title="Open approvals" />
        @endif
    @endcan
</div>
