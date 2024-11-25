<dl class="row">
    <dt class="col-sm-4">Requestor</dt>
    <dd class="col-sm-8">: {{ $payreq->requestor->name }}</dd>
    <dt class="col-sm-4">Nomor</dt>
    <dd class="col-sm-8">: {{ $payreq->nomor }}</dd>
    <dt class="col-sm-4">Type</dt>
    <dd class="col-sm-8">: {{ ucfirst($payreq->type) }}</dd>
    <dt class="col-sm-4">Remarks</dt>
    <dd class="col-sm-8">: {{ $payreq->remarks }}</dd>
    <dt class="col-sm-4">Amount</dt>
    <dd class="col-sm-8">: IDR {{ number_format($payreq->amount, 2) }}</dd>
    <dt class="col-sm-4">Project</dt>
    <dd class="col-sm-8">: {{ $payreq->project }}</dd>
    <dt class="col-sm-4">Department</dt>
    <dd class="col-sm-8">: {{ $payreq->department->department_name }}</dd>
    <dt class="col-sm-4">Status</dt>
    <dd class="col-sm-8">: {{ ucfirst($payreq->status) }}</dd>
    <dt class="col-sm-4">Created At</dt>
    <dd class="col-sm-8">: {{ $payreq->created_at->addHours(8)->format('d M Y - H:i:s') }} wita</dd>
    <dt class="col-sm-4">Submitted At</dt>
    @if ($payreq->submit_at)
        <dd class="col-sm-8">: {{ \Carbon\Carbon::parse($payreq->submit_at)->addHours(8)->format('d M Y - H:i:s') }}
            wita</dd>
    @else
        <dd class="col-sm-8">: -</dd>
    @endif
    <dt class="col-sm-4">Approved At</dt>
    @if ($payreq->approved_at)
        <dd class="col-sm-8">: {{ \Carbon\Carbon::parse($payreq->approved_at)->addHours(8)->format('d M Y - H:i:s') }}
            wita</dd>
    @else
        <dd class="col-sm-8">: -</dd>
    @endif
    <dt class="col-sm-4">Due Date</dt>
    @if ($payreq->due_date)
        <dd class="col-sm-8">: {{ \Carbon\Carbon::parse($payreq->due_date)->addHours(8)->format('d M Y - H:i:s') }}
            wita</dd>
    @else
        <dd class="col-sm-8">: -</dd>
    @endif
    @if ($payreq->rab_id)
        <dt class="col-sm-4">RAB No</dt>
        <dd class="col-sm-8">:
            {{ $payreq->anggaran->nomor . ' | ' . $payreq->anggaran->rab_project . ' | ' . $payreq->anggaran->description }}
        </dd>
    @endif
</dl>
