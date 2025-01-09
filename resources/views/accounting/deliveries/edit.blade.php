@extends('templates.main')

@section('title_page')
    Edit Delivery
@endsection

@section('breadcrumb_title')
    accounting / delivery / edit
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h5>Edit Delivery</h5>
                    <a href="{{ route('accounting.deliveries.index', ['page' => 'list']) }}"
                        class="btn btn-sm btn-primary float-right">Back to Deliveries List</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('accounting.deliveries.update', $delivery->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="delivery_number">Delivery Number</label>
                                    <input type="text"
                                        class="form-control @error('delivery_number') is-invalid @enderror"
                                        id="delivery_number" name="delivery_number"
                                        value="{{ old('delivery_number', $delivery->delivery_number) }}" required readonly>
                                    @error('delivery_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="document_date">Document Date</label>
                                    <input type="date" class="form-control @error('document_date') is-invalid @enderror"
                                        id="document_date" name="document_date"
                                        value="{{ old('document_date', $delivery->document_date) }}" required readonly>
                                    @error('document_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="origin">Origin</label>
                                    <input type="text" class="form-control @error('origin') is-invalid @enderror"
                                        id="origin" name="origin" value="{{ old('origin', $delivery->origin) }}"
                                        required readonly>
                                    @error('origin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="destination">Destination</label>
                                    <input type="text" class="form-control @error('destination') is-invalid @enderror"
                                        id="destination" name="destination"
                                        value="{{ old('destination', $delivery->destination) }}" required readonly>
                                    @error('destination')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="recipient_name">Recipient Name</label>
                                    <input type="text" class="form-control @error('recipient_name') is-invalid @enderror"
                                        id="recipient_name" name="recipient_name"
                                        value="{{ old('recipient_name', $delivery->recipient_name) }}" required>
                                    @error('recipient_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="remarks">Remarks (Optional)</label>
                                    <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks">{{ old('remarks', $delivery->remarks) }}</textarea>
                                    @error('remarks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h6>Select Verification Journals</h6>
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
                                        @foreach ($verificationJournals as $journal)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" name="verification_journals[]"
                                                        value="{{ $journal->id }}"
                                                        {{ in_array($journal->id, $selectedJournalIds) ? 'checked' : '' }}>
                                                </td>
                                                <td class="text-center">{{ $journal->nomor }}</td>
                                                <td class="text-center">
                                                    {{ \Carbon\Carbon::parse($journal->date)->format('d-M-Y') }}</td>
                                                <td class="text-center">{{ $journal->sap_journal_no }}</td>
                                                <td>
                                                    @if ($journal->realizations)
                                                        <ul>
                                                            @foreach ($journal->realizations as $realization)
                                                                <li>{{ $realization->nomor }}
                                                                    ({{ $realization->realization_date }})
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-danger">No realizations</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-primary">Update Delivery</button>
                                <a href="{{ route('accounting.deliveries.index', ['page' => 'list']) }}"
                                    class="btn btn-sm btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
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

@section('scripts')
    <script>
        $(function() {
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '{{ session('success') }}',
                    confirmButtonText: 'OK'
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    confirmButtonText: 'OK'
                });
            @endif
        });
    </script>
@endsection
