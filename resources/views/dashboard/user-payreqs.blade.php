{{-- PAYREQS --}}
<div class="col-4">
    <div class="card card-info">

        <div class="card-header border-1">
            <h4 class="card-title">Your ongoing Payreqs</h4>
        </div>

        <div class="card-body">

            @foreach ($user_ongoing_payreqs['payreq_status'] as $item)
                <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                    <p class="d-flex flex-column">
                        <span class="font-weight-bold">
                        {{ ucfirst($item['status']) }}
                        </span>
                    </p>
                    <p class="d-flex flex-column text-right">
                    <span class="font-weight-bold">
                        {{ $item['count'] }}
                    </span>
                    </p>
                </div>
            @endforeach
            
            @if ($user_ongoing_payreqs['over_due_payreq'] > 0)
            <div class="d-flex justify-content-between align-items-center border-bottom  mb-1">
                <p class="d-flex flex-column">
                    <span class="font-weight-bold text-red">
                      OVERDUE
                    </span>
                  </p>
                <p class="d-flex flex-column text-right">
                  <span class="font-weight-bold text-red">
                    {{ $user_ongoing_payreqs['over_due_payreq'] }}
                  </span>
                </p>
            </div>
            @endif
        </div>

    </div>
</div>

{{-- REALIZATIONS --}}
<div class="col-4">
    <div class="card card-info">

        <div class="card-header border-1">
            <h4 class="card-title">Your ongoing Realizations</h4>
        </div>

        <div class="card-body">

            @foreach ($user_ongoing_realizations['realization_status'] as $item)
                <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                    <p class="d-flex flex-column">
                        <span class="font-weight-bold">
                        {{ ucfirst($item['status']) }}
                        </span>
                    </p>
                    <p class="d-flex flex-column text-right">
                    <span class="font-weight-bold">
                        {{ $item['count'] }}
                    </span>
                    </p>
                </div>
            @endforeach
            
        </div>

    </div>
</div>