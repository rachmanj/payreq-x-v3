@extends('templates.main')

@section('title_page')
Send Verification Journal to SAP
@endsection

@section('breadcrumb_title')
accounting / sap-sync / show
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Verification Journal</h3>
                <a href="{{ route('accounting.sap-sync.index', ['project' => $vj->project]) }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <div class="row">
                            <dt class="col-sm-4">Verification Journal No</dt>
                            <dd class="col-sm-8">: {{ $vj->nomor }} </dd>
                            <dt class="col-sm-4">SAP Journal No</dt>
                            <dd class="col-sm-8">: {{ $vj->sap_journal_no }} </dd>
                            <dt class="col-sm-4">Project</dt>
                            <dd class="col-sm-8">: {{ $vj->project }}</dd>
                            <dt class="col-sm-4">Date</dt>
                            <dd class="col-sm-8">: {{  date('d-M-Y', strtotime($vj->date)) }}</dd>
                            <dt class="col-sm-4">Description</dt>
                            <dd class="col-sm-8">: {{ $vj->description }}</dd>
                            <dt class="col-sm-4">Amount</dt>
                            <dd class="col-sm-8">: Rp.{{ number_format($vj->amount, 2) }}</dd>
                            <dt class="col-sm-4">Created by</dt>
                            <dd class="col-sm-8">: {{ $vj->createdBy->name }} on {{  date('d-M-Y H:m', strtotime($vj->created_at . '+8 hours')) }} wita</dd>
                            <dt class="col-sm-4">Posted by</dt>
                            <dd class="col-sm-8">: {{ ($vj->posted_by ? $vj->postedBy->name . ' on ' . date('d-M-Y H:m', strtotime($vj->updated_at . '+8 hours')) . ' wita' : 'not posted yet') }}</dd>
                        </div>
                    </div>
                    <div class="col-4">
                        @if ($vj->sap_journal_no)
                        <button class="btn btn-outline-danger btn-lg" style="pointer-events: none;"><b>POSTED</b></button>
                        @endif
                    </div>
                </div>
                
            </div>
            <div class="card-header">
                <h3 class="card-title">Detail</h3>
                <form action="{{ route('accounting.sap-sync.cancel_sap_info') }}" method="POST">
                    @csrf
                    <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
                    <button class="btn btn-xs btn-danger float-right" {{ $vj->sap_journal_no ? '' : 'disabled' }} onclick="return confirm('Are You sure You want to cancel this SAP Info? This action cannot be undone')">Cancel SAP Info</button>
                </form>
                <button class="btn btn-xs btn-warning float-right mr-2" data-toggle="modal" data-target="#update-sap" style="color: black; font-weight: bold" {{ $vj->sap_journal_no ? 'disabled' : '' }}>Update SAP Info</button>
                <button class="btn btn-xs btn-warning float-right mr-2" data-toggle="modal" data-target="#upload-journal" style="color: black; font-weight: bold" {{ $vj->sap_journal_no ? '' : 'disabled' }}>Upload SAP Journal</button>
                <a href="{{ route('accounting.sap-sync.export', ['vj_id' => $vj->id]) }}" class="btn btn-xs btn-warning float-right mr-2" style="color: black; font-weight: bold">Export to Excel</a>
                <a href="{{ route('verifications.journal.print', $vj->id) }}" class="btn btn-xs float-right mr-2 btn-warning" style="color: black; font-weight: bold" target="_blank">Print Journal</a>
                @if($vj->sap_journal_no === null)
                <a href="{{ route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $vj->id]) }}" class="btn btn-xs btn-warning float-right mr-2" style="color: black; font-weight: bold">Edit VJDetails</a>
                @endif
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
                        @foreach ($vj_details as $key => $item)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                {{ $item['account_code'] }} <br> 
                                @if ($item['account_name'] === 'not found')
                                    <span style="color: red;"><small><b>{{ $item['account_name'] }}</b></small></span>
                                @else
                                    <small><b>{{ $item['account_name'] }}</b></small>
                                @endif
                            </td>
                            <td>{{ $item['description'] }}</td>
                            <td>{{ $item['project'] }}</td>
                            <td>{{ $item['cost_center'] }}</td>
                            @if ($item['debit_credit'] === 'debit')
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
                            <th class="text-right">{{ number_format($vj_details->where('debit_credit', 'debit')->sum('amount'), 2) }}</th>
                            <th class="text-right">{{ number_format($vj_details->where('debit_credit', 'credit')->sum('amount'), 2) }}</th>
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
            <form action="{{ route('accounting.sap-sync.update_sap_info') }}" method="POST">
                @csrf
                <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
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

{{-- UPLOAD JOURNAL --}}
<div class="modal fade" id="upload-journal">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload SAP Journal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('accounting.sap-sync.upload_sap_journal') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="verification_journal_id" value="{{ $vj->id }}">
                <div class="form-group">
                    <label for="sap_journal_file">Journal File</label>
                    <input type="file" name="sap_journal_file" class="form-control">
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-upload"></i> Upload</button>
            </div>
            </form>
        </div>
    </div>
</div>
@endsection
