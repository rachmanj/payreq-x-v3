@extends('templates.main')

@section('title_page')
    Create Delivery
@endsection

@section('breadcrumb_title')
    accounting / delivery / create
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <x-delivery-links page="dashboard" />
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Create New Delivery</h5>

        </div>
        <div class="card-body">
            <form action="{{ route('accounting.deliveries.store') }}" method="POST" id="deliveryForm">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delivery_number">Delivery Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="delivery_number" name="delivery_number"
                                value="auto generated" readonly>

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="document_date">Document Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('document_date') is-invalid @enderror"
                                id="document_date" name="document_date" value="{{ date('Y-m-d') }}" required readonly>
                            @error('document_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="origin">Origin <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('origin') is-invalid @enderror" id="origin"
                                name="origin" value="{{ $origin }}" required readonly>
                            @error('origin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="destination">Destination <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('destination') is-invalid @enderror"
                                id="destination" name="destination" value="{{ $destination }}" required readonly>
                            @error('destination')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="recipient_name">Recipient Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('recipient_name') is-invalid @enderror"
                                id="recipient_name" name="recipient_name" value="{{ old('recipient_name') }}" required>
                            @error('recipient_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="remarks">Remarks <small>(Optional)</small></label>
                            <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks">{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <h6 class="d-inline">Select Available Documents to Deliver</h6> <button type="button"
                            class="btn btn-primary btn-sm float-right mb-2" id="createDeliveryButton">Create
                            Delivery</button>
                        <script>
                            document.getElementById('createDeliveryButton').addEventListener('click', function() {
                                document.getElementById('deliveryForm').submit();
                            });
                        </script>
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center">Select</th>
                                    <th class="text-center">VJ Nomor</th>
                                    <th class="text-center">VJ Date</th>
                                    <th class="text-center">SAP Journal No</th>
                                    <th class="text-center">Realizations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($verificationJournals as $journal)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="verification_journals[]"
                                                value="{{ $journal->id }}">
                                        </td>
                                        <td class="text-center">{{ $journal->nomor }}</td>
                                        <td class="text-center">
                                            {{ \Carbon\Carbon::parse($journal->date)->format('d-M-Y') }}</td>
                                        <td class="text-center">{{ $journal->sap_journal_no }}</td>
                                        <td class="text-center">
                                            @if ($journal->realizations && $journal->realizations->count() > 0)
                                                <ul>
                                                    @foreach ($journal->realizations as $realization)
                                                        <li>{{ $realization->nomor }}
                                                            ({{ \Carbon\Carbon::parse($realization->realization_date)->format('d-M-Y') }})
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                No realizations
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-danger text-center">No verification journals
                                            available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <button type="submit" class="btn btn-sm btn-primary">Create Delivery</button>
            </form>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .card-header .active {
            font-weight: bold;
            color: black;
            text-transform: uppercase;
        }
    </style>
@endsection
