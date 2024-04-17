@extends('templates.main')

@section('title_page')
    Verification Journals
@endsection

@section('breadcrumb_title')
    journals
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Verification Journal</h3>
                <a href="{{ route('verifications.journal.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <div class="row">
                            <dt class="col-sm-4">Verification Journal No</dt>
                            <dd class="col-sm-8">: {{ $verification_journal->nomor }} </dd>
                            <dt class="col-sm-4">SAP Journal No</dt>
                            <dd class="col-sm-8">: {{ $verification_journal->sap_journal_no }} </dd>
                            <dt class="col-sm-4">Project</dt>
                            <dd class="col-sm-8">: {{ $verification_journal->project }}</dd>
                            <dt class="col-sm-4">Date</dt>
                            <dd class="col-sm-8">: {{  date('d-M-Y', strtotime($verification_journal->date)) }}</dd>
                            <dt class="col-sm-4">Description</dt>
                            <dd class="col-sm-8">: {{ $verification_journal->description }}</dd>
                            <dt class="col-sm-4">Amount</dt>
                            <dd class="col-sm-8">: Rp.{{ number_format($verification_journal->amount, 2) }}</dd>
                            <dt class="col-sm-4">Created by</dt>
                            <dd class="col-sm-8">: {{ $verification_journal->createdBy->name }} on {{  date('d-M-Y', strtotime($verification_journal->created_at)) }}</dd>
                        </div>
                    </div>
                    <div class="col-4">
                        @if ($verification_journal->sap_journal_no)
                        <button class="btn btn-outline-danger btn-lg" style="pointer-events: none;"><b>POSTED</b></button>
                        @endif
                    </div>
                </div>
                
            </div>
            <div class="card-header">
                <h3 class="card-title">Detail</h3>
                <form action="{{ route('verifications.journal.cancel_sap_info') }}" method="POST">
                    @csrf
                    <input type="hidden" name="verification_journal_id" value="{{ $verification_journal->id }}">
                    <button class="btn btn-sm btn-danger float-right" {{ $verification_journal->sap_journal_no ? '' : 'disabled' }} onclick="return confirm('Are You sure You want to cancel this UPDATE? This action cannot be undone')">Cancel SAP Info</button>
                </form>
                <button class="btn btn-sm btn-warning float-right mr-2" data-toggle="modal" data-target="#update-sap" style="color: black; font-weight: bold" {{ $verification_journal->sap_journal_no ? 'disabled' : '' }}>Update SAP Info</button>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Description</th>
                            <th>Project</th>
                            <th>Dept</th>
                            <th class="text-right">Debit (IDR)</th>
                            <th class="text-right">Credit (IDR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($debits['debit_details'] as $item)
                        <tr>
                            <td>
                                {{ $item['account_number'] }} - {{ $item['account_name'] }}
                            </td>
                            <td>{{ $item['description'] }}</td>
                            <td>{{ $item['project'] }}</td>
                            <td>{{ $item['department'] }}</td>
                            <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                            <td class="text-right">0.00</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td>
                                {{ $credit['account_number'] }} - {{ $credit['account_name'] }}
                            </td>
                            <td>
                                {{ $verification_journal->nomor }}
                            </td>
                            <td>{{ $credit['project'] }}</td>
                            <td>{{ $credit['department'] }}</td>
                            <td class="text-right">0.00</td>
                            <td class="text-right">{{ number_format($credit['credit_amount'], 2) }}</td>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-right">TOTAL</th>
                            <th class="text-right">{{ number_format($debits['debit_amount'], 2) }}</th>
                            <th class="text-right">{{ number_format($credit['credit_amount'], 2) }}</th>
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
            <form action="{{ route('verifications.journal.update_sap_info') }}" method="POST">
                @csrf
                <input type="hidden" name="verification_journal_id" value="{{ $verification_journal->id }}">
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
