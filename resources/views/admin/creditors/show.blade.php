@extends('templates.main')

@section('title_page')
    Creditor Details
@endsection

@section('breadcrumb_title')
    creditors / show
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Creditor Details</h3>
                    <a href="{{ route('admin.creditors.index') }}" class="btn btn-sm btn-primary float-right">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Creditor Name:</th>
                                    <td>{{ $creditor->name }}</td>
                                </tr>
                                <tr>
                                    <th>SAP Code:</th>
                                    <td>
                                        @if($creditor->sapBusinessPartner)
                                            <span class="badge badge-success">{{ $creditor->sapBusinessPartner->code }}</span>
                                        @else
                                            <span class="badge badge-warning">Not Linked</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>SAP Name:</th>
                                    <td>{{ $creditor->sapBusinessPartner?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>SAP Status:</th>
                                    <td>
                                        @if($creditor->sapBusinessPartner)
                                            @if($creditor->sapBusinessPartner->active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($creditor->sapBusinessPartner)
                            <div class="card card-info">
                                <div class="card-header">
                                    <h5 class="card-title">SAP Business Partner Details</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th width="40%">Code:</th>
                                            <td>{{ $creditor->sapBusinessPartner->code }}</td>
                                        </tr>
                                        <tr>
                                            <th>Name:</th>
                                            <td>{{ $creditor->sapBusinessPartner->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Type:</th>
                                            <td>{{ $creditor->sapBusinessPartner->type }}</td>
                                        </tr>
                                        <tr>
                                            <th>Active:</th>
                                            <td>
                                                @if($creditor->sapBusinessPartner->active)
                                                    <span class="badge badge-success">Yes</span>
                                                @else
                                                    <span class="badge badge-danger">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($creditor->sapBusinessPartner->federal_tax_id)
                                        <tr>
                                            <th>NPWP:</th>
                                            <td>{{ $creditor->sapBusinessPartner->federal_tax_id }}</td>
                                        </tr>
                                        @endif
                                        @if($creditor->sapBusinessPartner->phone)
                                        <tr>
                                            <th>Phone:</th>
                                            <td>{{ $creditor->sapBusinessPartner->phone }}</td>
                                        </tr>
                                        @endif
                                        @if($creditor->sapBusinessPartner->email)
                                        <tr>
                                            <th>Email:</th>
                                            <td>{{ $creditor->sapBusinessPartner->email }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <th>Last Synced:</th>
                                            <td>
                                                {{ $creditor->sapBusinessPartner->last_synced_at 
                                                    ? $creditor->sapBusinessPartner->last_synced_at->format('d-M-Y H:i:s') 
                                                    : 'Never' }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                This creditor is not linked to a SAP Business Partner. 
                                <a href="{{ route('admin.creditors.edit', $creditor->id) }}">Link it now</a> to enable AP Invoice creation.
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Related Loans</h5>
                                </div>
                                <div class="card-body">
                                    @if($creditor->loans->count() > 0)
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Loan Code</th>
                                                    <th>Start Date</th>
                                                    <th>Principal</th>
                                                    <th>Tenor</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($creditor->loans as $loan)
                                                <tr>
                                                    <td>{{ $loan->loan_code }}</td>
                                                    <td>{{ $loan->start_date ? \Carbon\Carbon::parse($loan->start_date)->format('d-M-Y') : '-' }}</td>
                                                    <td>{{ number_format($loan->principal, 2) }}</td>
                                                    <td>{{ $loan->tenor }} months</td>
                                                    <td>{{ $loan->status ?? '-' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <p class="text-muted">No loans found for this creditor.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('admin.creditors.edit', $creditor->id) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.creditors.index') }}" class="btn btn-default btn-sm">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
