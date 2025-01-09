@extends('templates.main')

@section('title_page')
    Delivery
@endsection

@section('breadcrumb_title')
    accounting / delivery / show
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h5>Delivery Details</h5>
                    <a href="{{ route('accounting.deliveries.index', ['page' => 'list']) }}"
                        class="btn btn-sm btn-primary float-right">Back to Deliveries List</a>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Delivery Number:</dt>
                        <dd class="col-sm-8">{{ $delivery->delivery_number }}</dd>

                        <dt class="col-sm-4">Delivery Date:</dt>
                        <dd class="col-sm-8">{{ date('d-M-Y', strtotime($delivery->sent_date)) }}</dd>

                        <dt class="col-sm-4">Origin:</dt>
                        <dd class="col-sm-8">{{ $delivery->origin }}</dd>

                        <dt class="col-sm-4">Destination:</dt>
                        <dd class="col-sm-8">{{ $delivery->destination }}</dd>

                        <dt class="col-sm-4">Recipient Name:</dt>
                        <dd class="col-sm-8">{{ $delivery->recipient_name }}</dd>

                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">{{ ucfirst($delivery->status) }}</dd>

                        <dt class="col-sm-4">Remarks:</dt>
                        <dd class="col-sm-8">{{ $delivery->remarks }}</dd>
                    </dl>

                    <h5 class="mt-4">Verification Journals</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>VJ Number</th>
                                    <th>VJ Date</th>
                                    <th>SAP Journal No</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($delivery->verificationJournals as $journal)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $journal->nomor }}</td>
                                        <td>{{ date('d-M-Y', strtotime($journal->date)) }}</td>
                                        <td>{{ $journal->sap_journal_no }}</td>
                                        <td class="text-right">{{ number_format($journal->amount, 2) }}</td>
                                    </tr>
                                    @if ($journal->realizations->count() > 0)
                                        <tr>
                                            <td colspan="5">
                                                <strong>Realizations:</strong>
                                                <div class="row">
                                                    @foreach ($journal->realizations as $realization)
                                                        <div class="col-sm-3">
                                                            <li>
                                                                <small>{{ $realization->nomor }}</small>
                                                                <small>({{ date('d-M-Y', strtotime($realization->created_at)) }})</small>
                                                            </li>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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
