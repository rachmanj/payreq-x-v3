<div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-5">
    {{-- PAYREQS --}}
    <x-dashboard.panel
        title="Your Ongoing Payreqs"
        subtitle="Payment requests in progress"
        icon="fas fa-file-invoice-dollar"
        gradient="tw-bg-gradient-to-br tw-from-brand-500 tw-to-brand-700">
        <x-slot:action>
            <a href="{{ route('user-payreqs.index') }}"
                class="tw-inline-flex tw-items-center tw-gap-1 tw-bg-white tw-text-gray-700 tw-text-[13px] tw-font-semibold tw-rounded-full tw-px-3 tw-py-1.5 tw-no-underline hover:tw-bg-gray-100">
                <i class="fas fa-list"></i> View All
            </a>
        </x-slot:action>

        @php
            $hasPayreqItems = false;
            foreach ($user_ongoing_payreqs['payreq_status'] as $item) {
                if ($item['count'] > 0) {
                    $hasPayreqItems = true;
                    break;
                }
            }
            if ($user_ongoing_payreqs['over_due_payreq']['count'] > 0) {
                $hasPayreqItems = true;
            }
        @endphp

        @if ($hasPayreqItems)
            @foreach ($user_ongoing_payreqs['payreq_status'] as $item)
                @if ($item['count'] > 0)
                    <x-dashboard.status-row
                        :status="$item['status']"
                        :count="$item['count']"
                        unit="payreq"
                        :amount="$item['amount']" />
                @endif
            @endforeach

            @if ($user_ongoing_payreqs['over_due_payreq']['count'] > 0)
                <x-dashboard.status-row
                    status="OVERDUE"
                    :count="$user_ongoing_payreqs['over_due_payreq']['count']"
                    unit="payreq"
                    :amount="$user_ongoing_payreqs['over_due_payreq']['amount']"
                    :overdue="true" />
            @endif
        @else
            <x-dashboard.empty-state title="No ongoing payreqs" subtitle="All caught up!" />
        @endif
    </x-dashboard.panel>

    {{-- REALIZATIONS --}}
    <x-dashboard.panel
        title="Your Ongoing Realizations"
        subtitle="Expense realizations in progress"
        icon="fas fa-receipt"
        gradient="tw-bg-gradient-to-br tw-from-pink-400 tw-to-yellow-300">
        <x-slot:action>
            <a href="{{ route('user-payreqs.realizations.index') }}"
                class="tw-inline-flex tw-items-center tw-gap-1 tw-bg-white tw-text-gray-700 tw-text-[13px] tw-font-semibold tw-rounded-full tw-px-3 tw-py-1.5 tw-no-underline hover:tw-bg-gray-100">
                <i class="fas fa-list"></i> View All
            </a>
        </x-slot:action>

        @php
            $hasRealizationItems = false;
            foreach ($user_ongoing_realizations['realization_status'] as $item) {
                if ($item['count'] > 0) {
                    $hasRealizationItems = true;
                    break;
                }
            }
            if ($user_ongoing_realizations['overdue_realization']['count'] > 0) {
                $hasRealizationItems = true;
            }
        @endphp

        @if ($hasRealizationItems)
            @foreach ($user_ongoing_realizations['realization_status'] as $item)
                @if ($item['count'] > 0)
                    <x-dashboard.status-row
                        :status="$item['status']"
                        :count="$item['count']"
                        unit="realization"
                        :amount="$item['amount']" />
                @endif
            @endforeach

            @if ($user_ongoing_realizations['overdue_realization']['count'] > 0)
                <x-dashboard.status-row
                    status="OVERDUE"
                    :count="$user_ongoing_realizations['overdue_realization']['count']"
                    unit="realization"
                    :amount="$user_ongoing_realizations['overdue_realization']['amount']"
                    :overdue="true" />
            @endif
        @else
            <x-dashboard.empty-state title="No ongoing realizations" subtitle="All caught up!" />
        @endif
    </x-dashboard.panel>
</div>
