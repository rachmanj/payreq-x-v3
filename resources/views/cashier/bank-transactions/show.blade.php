@extends('templates.main')

@section('title_page')
    View Bank Transaction
@endsection

@section('breadcrumb_title')
    bank-transactions/show
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Bank Transaction Details</h3>
                    <div class="float-right">
                        <a href="{{ route('cashier.bank-transactions.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        @if ($journal->status == 'draft')
                            <a href="{{ route('cashier.bank-transactions.edit', $journal->id) }}"
                                class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="{{ route('cashier.bank-transactions.submit', $journal->id) }}" method="POST"
                                class="d-inline">
                                @csrf
                                <button type="button" class="btn btn-success btn-sm submit-transaction">
                                    <i class="fas fa-paper-plane"></i> Submit
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">Journal Number</th>
                                    <td>{{ $journal->nomor ?? 'Not assigned yet' }}</td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td>{{ $journal->date ? date('d M Y', strtotime($journal->date)) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Type</th>
                                    <td>{{ $journal->type ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Project</th>
                                    <td>{{ $journal->project ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th><strong>Bank Account</strong></th>
                                    <td>{{ $journal->bank_account ? $journal->bank_account : 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <th><strong>Description</strong></th>
                                    <td>{{ $journal->description ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">Status</th>
                                    <td>
                                        @if ($journal->status == 'draft')
                                            <span class="badge badge-warning">Draft</span>
                                        @elseif($journal->status == 'submitted')
                                            <span class="badge badge-info">Submitted</span>
                                        @elseif($journal->status == 'posted')
                                            <span class="badge badge-success">Posted</span>
                                        @elseif($journal->status == 'canceled')
                                            <span class="badge badge-danger">Canceled</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $journal->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created By</th>
                                    <td>{{ $journal->createdBy->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>SAP Journal No</th>
                                    <td>{{ $journal->sap_journal_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>SAP Posting Date</th>
                                    <td>{{ $journal->sap_posting_date ? date('d M Y', strtotime($journal->sap_posting_date)) : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Posted By</th>
                                    <td>{{ $journal->postedBy->name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h4 class="mt-4">Transaction Details</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Realization Date</th>
                                    <th>Account Code</th>
                                    <th>Debit/Credit</th>
                                    <th>Description</th>
                                    <th>Project</th>
                                    <th>Cost Center</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($journal->verificationJournalDetails as $detail)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ date('d M Y', strtotime($detail->realization_date)) }}</td>
                                        <td>{{ $detail->account_code }}</td>
                                        <td>{{ ucfirst($detail->debit_credit) }}</td>
                                        <td>{{ $detail->description }}</td>
                                        <td>{{ $detail->project }}</td>
                                        <td>{{ $detail->cost_center }}</td>
                                        <td class="text-right">{{ number_format($detail->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No details found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="7" class="text-right">Total:</th>
                                    <th class="text-right">{{ number_format($journal->amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if ($journal->status == 'submitted' && $incoming)
                        <div class="mt-4">
                            <h4>Linked Incoming Record</h4>
                            <div class="alert alert-info">
                                <p><strong>Incoming ID:</strong> {{ $incoming->id }}</p>
                                <p><strong>Description:</strong> {{ $incoming->description }}</p>
                                <p><strong>Amount:</strong> {{ number_format($incoming->amount, 2) }}</p>
                                <p><strong>Received Date:</strong>
                                    {{ $incoming->receive_date ? date('d M Y H:i', strtotime($incoming->receive_date)) : 'Not received yet' }}
                                </p>
                                <p><strong>Created At:</strong> {{ date('d M Y H:i', strtotime($incoming->created_at)) }}
                                </p>
                                <a href="{{ route('cashier.incomings.received.index') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View in Received Incomings
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(function() {
            // Handle submit transaction button click
            $('.submit-transaction').on('click', function() {
                const form = $(this).closest('form');

                Swal.fire({
                    title: 'Submit Transaction?',
                    text: "This will submit the transaction and create an incoming record. You won't be able to edit it afterwards.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, submit it!',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
