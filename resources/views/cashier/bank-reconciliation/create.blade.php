@extends('templates.main')

@section('title_page')
    New Bank Reconciliation
@endsection

@section('breadcrumb_title')
    cashier / bank-reconciliation / create
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Create reconciliation</h3>
                </div>
                <form action="{{ route('cashier.bank-reconciliation.store') }}" method="post">
                    @csrf
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Giro (bank account)</label>
                            <select name="giro_id" id="giro_id" class="form-control form-control-sm" required>
                                <option value="">-- select --</option>
                                @foreach ($giros as $giro)
                                    <option value="{{ $giro->id }}"
                                        @selected((int) ($prefill['giro_id'] ?? 0) === $giro->id)>
                                        {{ $giro->acc_no }} — {{ $giro->acc_name }}
                                        ({{ $giro->project }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Period (month)</label>
                            <input type="month" name="periode" class="form-control form-control-sm"
                                value="{{ old('periode', isset($prefill['periode']) && $prefill['periode'] ? \Carbon\Carbon::parse($prefill['periode'])->format('Y-m') : '') }}"
                                required>
                            <small class="text-muted">Must match the uploaded koran month.</small>
                        </div>

                        <div class="form-group">
                            <label>Koran dokumen (PDF uploaded)</label>
                            <select name="dokumen_id" class="form-control form-control-sm" required>
                                <option value="">-- select giro first --</option>
                                @foreach ($dokumens as $doc)
                                    @php
                                        $rawPeriode = $doc->getRawOriginal('periode');
                                        $periodeLabel = $rawPeriode
                                            ? \Carbon\Carbon::parse($rawPeriode)->format('Y-m')
                                            : '-';
                                        $fname = basename((string) $doc->getRawOriginal('filename1'));
                                    @endphp
                                    <option value="{{ $doc->id }}"
                                        @selected((int) ($prefill['dokumen_id'] ?? 0) === $doc->id)>
                                        {{ $periodeLabel }} — {{ $fname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <p class="text-muted small mb-0">
                            Submitting will queue AI parsing of the PDF and SAP GL fetch for this period.
                            Ensure <code>OPENROUTER_API_KEY</code> and SAP / SAP Bridge are configured.
                        </p>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-sm">Start reconciliation</button>
                        <a href="{{ route('cashier.bank-reconciliation.index') }}" class="btn btn-default btn-sm">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.getElementById('giro_id')?.addEventListener('change', function() {
            const id = this.value;
            const url = new URL(window.location.href);
            if (id) {
                url.searchParams.set('giro_id', id);
            } else {
                url.searchParams.delete('giro_id');
            }
            url.searchParams.delete('dokumen_id');
            window.location.href = url.toString();
        });
    </script>
@endsection
