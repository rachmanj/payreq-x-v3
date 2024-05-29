@extends('templates.main')

@section('title_page')
End Of Month Journal
@endsection

@section('breadcrumb_title')
reports / eom-journal / show
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">EOM Journal</h3>
                <a href="{{ route('reports.eom.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <div class="row">
                            <dt class="col-sm-4">Verification Journal No</dt>
                            <dd class="col-sm-8">: {{ $journal->nomor }} </dd>
                            <dt class="col-sm-4">SAP Journal No</dt>
                            <dd class="col-sm-8">: {{ $journal->sap_journal_no }} </dd>
                            <dt class="col-sm-4">Project</dt>
                            <dd class="col-sm-8">: {{ $journal->project }}</dd>
                            <dt class="col-sm-4">Date</dt>
                            <dd class="col-sm-8">: {{  date('d-M-Y', strtotime($journal->date)) }}</dd>
                            <dt class="col-sm-4">Description</dt>
                            <dd class="col-sm-8">: {{ $journal->description }}</dd>
                            <dt class="col-sm-4">Amount</dt>
                            <dd class="col-sm-8">: Rp.{{ number_format($journal->amount, 2) }}</dd>
                            <dt class="col-sm-4">Created by</dt>
                            <dd class="col-sm-8">: {{ $journal->createdBy->name }} on {{  date('d-M-Y H:m', strtotime($journal->created_at . '+8 hours')) }} wita</dd>
                            <dt class="col-sm-4">Posted by</dt>
                            <dd class="col-sm-8">: {{ ($journal->posted_by ? $journal->postedBy->name . ' on ' . date('d-M-Y H:m', strtotime($journal->updated_at . '+8 hours')) . ' wita' : 'not posted yet') }}</dd>
                        </div>
                    </div>
                    <div class="col-4">
                        @if ($journal->sap_journal_no)
                        <button class="btn btn-outline-danger btn-lg" style="pointer-events: none;"><b>POSTED</b></button>
                        @endif
                    </div>
                </div>
                
            </div>
            <div class="card-header">
                <form action="{{ route('reports.eom.cancel_sap_info') }}" method="POST">
                    @csrf
                    <input type="hidden" name="eom_journal_id" value="{{ $journal->id }}">
                    <button class="btn btn-sm btn-danger float-right" {{ $journal->sap_journal_no ? '' : 'disabled' }} onclick="return confirm('Are You sure You want to cancel this SAP Info? This action cannot be undone')">Cancel SAP Info</button>
                </form>
                <button class="btn btn-sm btn-warning float-right mr-2" data-toggle="modal" data-target="#update-sap" style="color: black; font-weight: bold" {{ $journal->sap_journal_no ? 'disabled' : '' }}>Update SAP Info</button>
                <a href="{{ route('reports.eom.export', ['eom_journal_id' => $journal->id]) }}" class="btn btn-sm btn-warning float-right mr-2" style="color: black; font-weight: bold">Export to Excel</a>
                <h3 class="card-title">Detail</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th>Project</th>
                            <th>CCenter</th>
                            <th class="text-right">Debit (IDR)</th>
                            <th class="text-right">Credit (IDR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($journal_details as $key => $item)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                {{ $item['account_number'] }} <br> 
                                @if ($item['account_name'] === 'not found')
                                    <span style="color: red;"><small><b>{{ $item['account_name'] }}</b></small></span>
                                @else
                                    <small><b>{{ $item['account_name'] }}</b></small>
                                @endif
                            </td>
                            <td>{{ $item['description'] }}</td>
                            <td>{{ $item['project'] }}</td>
                            <td>{{ $item['cost_center'] }}</td>
                            @if ($item['d_c'] === 'debit')
                                <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                                <td class="text-right">0.00</td>
                            @else
                                <td class="text-right">0.00</td>
                                <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                            @endif
                        </tr>
                        @endforeach
                        <tr>
                            <th class="text-right" colspan="5">TOTAL</th>
                            <th class="text-right">{{ number_format($journal_details->where('d_c', 'debit')->sum('amount'), 2) }}</th>
                            <th class="text-right">{{ number_format($journal_details->where('d_c', 'credit')->sum('amount'), 2) }}</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL UPDATE - SAP  --}}
<div class="modal fade" id="update-sap">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update SAP Info</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('reports.eom.update_sap_info') }}" method="POST">
                @csrf
                <input type="hidden" name="eom_journal_id" value="{{ $journal->id }}">
            <div class="modal-body">
                <div class="form-group">
                    <label for="sap_posting_date">SAP Posting Date</label>
                    <input type="date" name="sap_posting_date" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label for="sap_journal_no">SAP Journal No</label>
                    <input type="text" name="sap_journal_no" class="form-control">
                </div>
            </div>
           
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
            </form>
        </div>
    </div>
</div>
@endsection
