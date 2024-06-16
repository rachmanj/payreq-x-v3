<dl class="row">
    <dt class="col-sm-4">Nomor</dt>
    <dd class="col-sm-8">: {{ $payreq->realization->nomor }}</dd>
    <dt class="col-sm-4">Remarks</dt>
    <dd class="col-sm-8">: {{ $payreq->realization->remarks }}</dd>
    <dt class="col-sm-4">Amount</dt>
    <dd class="col-sm-8">: IDR {{ number_format($payreq->realization->realizationDetails->sum('amount'), 2) }}</dd>
    <dt class="col-sm-4">Status</dt>
    <dd class="col-sm-8">: {{ ucfirst($payreq->realization->status) }}</dd>
    <dt class="col-sm-4">Created At</dt>
    <dd class="col-sm-8">: {{ $payreq->realization->created_at->addHours(8)->format('d M Y - H:i:s') }} wita</dd>
    <dt class="col-sm-4">Submitted at</dt>
    @if ($payreq->realization->submit_at)
    <dd class="col-sm-8">: {{ \Carbon\Carbon::parse($payreq->realization->submit_at)->addHours(8)->format('d M Y - H:i:s') }} wita</dd>
    @else
        <dd class="col-sm-8">: -</dd>
    @endif
    <dt class="col-sm-4">Approved at</dt>
    @if ($payreq->realization->approved_at)
    <dd class="col-sm-8">: {{ \Carbon\Carbon::parse($payreq->realization->approved_at)->addHours(8)->format('d M Y - H:i:s') }} wita</dd>
    @else
        <dd class="col-sm-8">: -</dd>
    @endif
    <dt class="col-sm-4">Verification Journal No</dt>
    <dd class="col-sm-8">: {{ $payreq->realization->verificationJournal->nomor }}</dd>
    <dt class="col-sm-4">SAP Journal No</dt>
    <dd class="col-sm-8">: {{ $payreq->realization->verificationJournal->sap_journal_no }}</dd>
</dl>