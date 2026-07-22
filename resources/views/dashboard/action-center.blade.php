@php
    $hasApprovals = auth()->user()->can('akses_approvals') && $wait_approve > 0;
    $hasPcbc = auth()->user()->can('validate_pcbc_report') && ($pcbc_pending_validation_count ?? 0) > 0;
    $hasBankRecon = auth()->user()->can('validate_bank_reconciliation') && ($bank_reconciliation_pending_validation_count ?? 0) > 0;
    $hasOverdueExt = auth()->user()->can('approve_overdue_extension') && ($pending_overdue_extension_count ?? 0) > 0;
    $hasActions = $hasApprovals || $hasPcbc || $hasBankRecon || $hasOverdueExt;
@endphp

@if ($hasActions)
    <div class="tw-mb-5">
        <div class="tw-flex tw-items-center tw-gap-2 tw-mb-3">
            <i class="fas fa-bolt tw-text-amber-500"></i>
            <h3 class="tw-text-base tw-font-semibold tw-text-gray-800 tw-mb-0">Action Center</h3>
            <span class="tw-text-xs tw-text-gray-500">Items waiting for you</span>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 xl:tw-grid-cols-4 tw-gap-4">
            @if ($hasApprovals)
                <x-dashboard.kpi-card
                    icon="fas fa-clock"
                    :value="$wait_approve"
                    label="Waiting for Your Approval"
                    :info="$wait_approve === 1 ? 'document pending' : 'documents pending'"
                    info-icon="fas fa-exclamation-circle"
                    tone="approval"
                    :href="route('approvals.request.payreqs.index')"
                    title="Open approvals" />
            @endif

            @if ($hasPcbc)
                <x-dashboard.kpi-card
                    icon="fas fa-file-signature"
                    :value="$pcbc_pending_validation_count"
                    label="PCBC pending validation"
                    info="Awaiting review on PCBC upload"
                    info-icon="fas fa-exclamation-circle"
                    tone="warning"
                    :href="route('cashier.pcbc.index', ['page' => 'upload'])"
                    title="Open PCBC upload list" />
            @endif

            @if ($hasBankRecon)
                <x-dashboard.kpi-card
                    icon="fas fa-balance-scale"
                    :value="$bank_reconciliation_pending_validation_count"
                    label="Bank reconciliation pending"
                    info="Awaiting your review"
                    info-icon="fas fa-exclamation-circle"
                    tone="warning"
                    :href="route('cashier.bank-reconciliation.index', ['view' => 'pending_validation'])"
                    title="Open bank reconciliations pending validation"
                    data-dashboard-pending-bank-reconciliation="{{ $bank_reconciliation_pending_validation_count }}" />
            @endif

            @if ($hasOverdueExt)
                <x-dashboard.kpi-card
                    icon="fas fa-calendar-plus"
                    :value="$pending_overdue_extension_count"
                    label="Overdue extension requests"
                    info="Awaiting your review"
                    info-icon="fas fa-exclamation-circle"
                    tone="warning"
                    :href="route('document-overdue.extensions.index')"
                    title="Open overdue extension approvals"
                    data-dashboard-pending-extension-requests="{{ $pending_overdue_extension_count }}" />
            @endif
        </div>
    </div>
@endif
