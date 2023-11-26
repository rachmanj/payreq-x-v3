{{-- PAYREQS --}}
<div class="col-6">
    <div class="card card-info">

        <div class="card-header border-1">
            <h4 class="card-title">Your ongoing Payreqs</h4>
        </div>

        <div class="card-body">

            @foreach ($user_ongoing_payreqs['payreq_status'] as $item)
                @if ($item['count'] > 0)
                <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                    <p class="d-flex flex-column">
                        <span class="font-weight-bold">
                        {{ ucfirst($item['status']) }}
                        </span>
                    </p>
                    <p class="d-flex flex-column text-right">
                    <span>
                       Rp. {{ number_format($item['amount'], 0) }} | {{ $item['count'] }} payreqs
                    </span>
                    </p>
                </div>
                @endif
            @endforeach
            
            @if ($user_ongoing_payreqs['over_due_payreq']['count'] > 0)
            <div class="d-flex justify-content-between align-items-center border-bottom  mb-1">
                <p class="d-flex flex-column">
                    <span class="font-weight-bold text-red">
                      OVERDUE
                    </span>
                  </p>
                <p class="d-flex flex-column text-right">
                  <span class="font-weight-bold text-red">
                    {{ $user_ongoing_payreqs['over_due_payreq']['amount'] }} | {{ $user_ongoing_payreqs['over_due_payreq']['count'] }}
                  </span>
                </p>
            </div>
            @endif
        </div>

    </div>
</div>

{{-- REALIZATIONS --}}
<div class="col-6">
    <div class="card card-info">

        <div class="card-header border-1">
            <h4 class="card-title">Your ongoing Realizations</h4>
        </div>

        <div class="card-body">
            @foreach ($user_ongoing_realizations['realization_status'] as $item)

                @if ($item['count'] > 0)
                <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                    <p class="d-flex flex-column">
                        <span class="font-weight-bold">
                        {{ ucfirst($item['status']) }}
                        </span>
                    </p>
                    <p class="d-flex flex-column text-right">
                    <span>
                       Rp. {{ number_format($item['amount'], 0) }} | {{ $item['count'] }} realizations
                    </span>
                    </p>
                </div>
                @endif

            @endforeach
        </div>

    </div>
</div>