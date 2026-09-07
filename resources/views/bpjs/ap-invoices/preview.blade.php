@extends('templates.main')

@section('title_page')
    Preview AP Invoice BPJS
@endsection

@section('breadcrumb_title')
    accounting / ap-invoice-bpjs / preview
@endsection

@section('content')
    <div class="vj-show">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="card-title mb-0">
                    <i class="fas fa-eye"></i> Preview AP Invoice BPJS #{{ $invoice->id }}
                </h3>
                <a href="{{ route('bpjs-ap-invoices.index') }}" class="vj-btn vj-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <th class="text-muted" style="width: 40%">Jenis</th>
                                <td>{{ $preview['jenis_label'] }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Unit</th>
                                <td>{{ $preview['unit_label'] }} ({{ $preview['unit'] }})</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Periode</th>
                                <td>{{ $preview['periode'] }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Nominal</th>
                                <td><strong>Rp {{ number_format($preview['amount'], 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <th class="text-muted" style="width: 40%">Vendor SAP</th>
                                <td>{{ $preview['vendor']['code'] }} — {{ $preview['vendor']['name'] }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Akun</th>
                                <td>{{ $preview['account_code'] }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Cost Center / Project</th>
                                <td>{{ $preview['costing_code'] }} / {{ $preview['project_code'] }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Vendor Ref. No.</th>
                                <td>{{ $preview['num_at_card'] }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <span class="text-muted small">DocDate / TaxDate</span>
                        <div>{{ $preview['dates']['doc_date'] }}</div>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted small">DocDueDate</span>
                        <div>{{ $preview['dates']['due_date'] }}</div>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted small">Tax Code</span>
                        <div>{{ $preview['tax_code'] }}</div>
                    </div>
                </div>

                <div class="vj-alert vj-alert-secondary mb-3">
                    <i class="fas fa-comment-alt"></i>
                    <div>
                        <strong>Comments & Item Description</strong><br>
                        {{ $preview['label'] }}
                    </div>
                </div>

                @if ($invoice->status === 'failed' && $invoice->sap_error_message)
                    <div class="vj-alert vj-alert-danger mb-3">
                        <i class="fas fa-times-circle"></i>
                        <div>
                            <strong>Error SAP terakhir</strong><br>
                            {{ $invoice->sap_error_message }}
                        </div>
                    </div>
                @endif

                <h5 class="mb-2">Document Line</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Akun</th>
                                <th>Deskripsi</th>
                                <th>Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th>VAT</th>
                                <th>CC</th>
                                <th>Project</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $preview['account_code'] }}</td>
                                <td>{{ $preview['label'] }}</td>
                                <td>1</td>
                                <td class="text-right">{{ number_format($preview['amount'], 0, ',', '.') }}</td>
                                <td>{{ $preview['tax_code'] }}</td>
                                <td>{{ $preview['costing_code'] }}</td>
                                <td>{{ $preview['project_code'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <details class="mt-3">
                    <summary class="text-muted small">Payload JSON (debug)</summary>
                    <pre class="bg-light p-2 small mt-2" style="max-height: 300px; overflow: auto;">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            </div>
            @if ($canSubmit && in_array($invoice->status, ['pending', 'failed'], true))
                <div class="card-footer d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('bpjs-ap-invoices.submit', $invoice) }}" id="submitBpjsForm">
                        @csrf
                        <button type="button" class="vj-btn vj-btn-success" id="btnSubmitSap">
                            <i class="fas fa-paper-plane"></i> Submit ke SAP
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    @include('partials.vj-soft-ui-swal')
    <script>
        $(function() {
            @if (session('success'))
                Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')) });
            @endif
            @if (session('error'))
                Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')) });
            @endif

            $('#btnSubmitSap').on('click', function() {
                Swal.fire({
                    title: 'Submit ke SAP B1?',
                    text: 'AP Invoice BPJS akan dibuat di SAP. Tindakan ini tidak bisa dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, submit',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#submitBpjsForm').submit();
                    }
                });
            });
        });
    </script>
@endsection
