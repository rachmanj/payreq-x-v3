@extends('templates.main')

@section('title_page')
    Cash Journals
@endsection

@section('breadcrumb_title')
    journals
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">{{ $cash_journal->type == 'cash-out' ? "Cash-Out Journal" : "Cash-In Journal" }}</h3>
                <a href="{{ route('cash-journals.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <div class="row">
                            <dt class="col-sm-4">Journal No</dt>
                            <dd class="col-sm-8">: {{ $cash_journal->journal_no }} </dd>
                            <dt class="col-sm-4">SAP Journal No</dt>
                            <dd class="col-sm-8">: {{ $cash_journal->sap_journal_no }} </dd>
                            <dt class="col-sm-4">Type</dt>
                            <dd class="col-sm-8">: {{ $cash_journal->type }}</dd>
                            <dt class="col-sm-4">Project</dt>
                            <dd class="col-sm-8">: {{ $cash_journal->project }}</dd>
                            <dt class="col-sm-4">Date</dt>
                            <dd class="col-sm-8">: {{  date('d-M-Y', strtotime($cash_journal->date)) }}</dd>
                            <dt class="col-sm-4">Description</dt>
                            <dd class="col-sm-8">: {{ $cash_journal->description }}</dd>
                            <dt class="col-sm-4">Amount</dt>
                            <dd class="col-sm-8">: Rp.{{ number_format($cash_journal->amount, 2) }}</dd>
                            <dt class="col-sm-4">Created by</dt>
                            <dd class="col-sm-8">: {{ $cash_journal->createdBy->name }} on {{  date('d-M-Y', strtotime($cash_journal->created_at)) }}</dd>
                        </div>
                    </div>
                    <div class="col-4">
                        @if ($cash_journal->sap_journal_no)
                        <button class="btn btn-outline-danger btn-lg" style="pointer-events: none;"><b>POSTED</b></button>
                        @endif
                    </div>
                </div>
                
            </div>
            <div class="card-header">
                <h3 class="card-title">Detail</h3>
                <form action="{{ route('cash-journals.cancel_sap_info') }}" method="POST">
                    @csrf
                    <input type="hidden" name="cash_journal_id" value="{{ $cash_journal->id }}">
                    <button class="btn btn-sm btn-danger float-right" {{ $cash_journal->sap_journal_no ? '' : 'disabled' }} onclick="return confirm('Are You sure You want to cancel this UPDATE? This action cannot be undone')">Cancel SAP Info</button>
                </form>
                <button class="btn btn-sm btn-warning float-right mr-2" data-toggle="modal" data-target="#update-sap" style="color: black; font-weight: bold" {{ $cash_journal->sap_journal_no ? 'disabled' : '' }}>Update SAP Info</button>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                {{ $advance_account->account_number }} - {{ $advance_account->account_name }}
                            </td>
                            <td class="text-right">{{ number_format($outgoings->sum('amount'), 2) }}</td>
                            <td class="text-right">{{ number_format(0, 2) }}</td>
                        </tr>
                            <tr>
                                <td>
                                    <ol>
                                        @foreach ($outgoings as $item)
                                            <li>Payreq No.{{ $item->payreq->nomor }}, {{ $item->payreq->requestor->name }}, {{ $item->payreq->remarks }}, {{ number_format($item->amount, 2) }} {{-- <ahref="route('cash-journals.delete_detail',$item->id)" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></a> --}}</li>
                                        @endforeach
                                    </ol>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    {{ $pc_account->account_number }} - {{ $pc_account->account_name }}
                                </td>
                                <td class="text-right">{{ number_format(0, 2) }}</td>
                                <td class="text-right">{{ number_format($outgoings->sum('amount'), 2) }}</td>
                            </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL UPDATE - SAP --}}
<div class="modal fade" id="update-sap">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update SAP Data</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('cash-journals.update_sap') }}" method="POST">
                @csrf
                <input type="hidden" name="cash_journal_id" value="{{ $cash_journal->id }}">
            <div class="modal-body">
                <div class="form-group">
                    <label for="sap_posting_date">Posting Date (SAP)</label>
                    <input type="date" name="sap_posting_date" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label for="sap_journal_no">SAP Outgoing No</label>
                    <input type="text" name="sap_journal_no" class="form-control">
                </div>
            </div>
            {{-- button --}}
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
            </form>
        </div>
    </div>
</div>
@endsection
