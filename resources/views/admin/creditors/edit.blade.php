@extends('templates.main')

@section('title_page')
    Edit Creditor
@endsection

@section('breadcrumb_title')
    creditors / edit
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Creditor</h3>
                    <a href="{{ route('admin.creditors.index') }}" class="btn btn-sm btn-primary float-right">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.creditors.update', $creditor->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="name">Creditor Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name', $creditor->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="sap_business_partner_id">SAP Business Partner (Supplier/Vendor)</label>
                                    <select name="sap_business_partner_id" id="sap_business_partner_id" 
                                            class="form-control select2bs4 @error('sap_business_partner_id') is-invalid @enderror">
                                        <option value="">-- Select SAP Business Partner --</option>
                                        @foreach($sapPartners as $partner)
                                            <option value="{{ $partner->id }}" 
                                                    {{ old('sap_business_partner_id', $creditor->sap_business_partner_id) == $partner->id ? 'selected' : '' }}>
                                                {{ $partner->code }} - {{ $partner->name }}
                                                @if(!$partner->active)
                                                    (Inactive)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sap_business_partner_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        Link this creditor to a SAP Business Partner (Supplier/Vendor) to enable AP Invoice creation.
                                    </small>
                                </div>
                            </div>
                        </div>

                        @if($creditor->sapBusinessPartner)
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <strong>Current SAP Link:</strong><br>
                                    <strong>Code:</strong> {{ $creditor->sapBusinessPartner->code }}<br>
                                    <strong>Name:</strong> {{ $creditor->sapBusinessPartner->name }}<br>
                                    <strong>Status:</strong> 
                                    @if($creditor->sapBusinessPartner->active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                    <br>
                                    <strong>Last Synced:</strong> {{ $creditor->sapBusinessPartner->last_synced_at ? $creditor->sapBusinessPartner->last_synced_at->format('d-M-Y H:i:s') : 'Never' }}
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="card-footer">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Update
                            </button>
                            <a href="{{ route('admin.creditors.index') }}" class="btn btn-default btn-sm">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });
        });
    </script>
@endsection
