@extends('templates.main')

@section('title_page')
    Loans
@endsection

@section('breadcrumb_title')
    accounting / loans / installment / generate
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Generate Installment for Loan {{ $loan->loan_code }}</h3>
                    <a href="{{ route('accounting.loans.show', $loan->id) }}" class="btn btn-sm btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('accounting.loans.installments.store_generate') }}" method="POST">
                        @csrf

                        <input type="hidden" name="loan_id" value="{{ $loan->id }}">

                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle"></i> Loan Information:</strong><br>
                            <strong>Principal:</strong> IDR {{ number_format($loan->principal, 0) }}<br>
                            <strong>Tenor:</strong> {{ $loan->tenor }} months<br>
                            <strong>Creditor:</strong> {{ $loan->creditor->name ?? 'N/A' }}
                        </div>

                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="start_due_date">Tanggal Jatuh Tempo Pertama <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="start_due_date"
                                        class="form-control @error('start_due_date') is-invalid @enderror"
                                        value="{{ old('start_due_date', now()->addMonth()->startOfMonth()->format('Y-m-d')) }}"
                                        required>
                                    @error('start_due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Tanggal angsuran pertama jatuh tempo</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="tenor">Jumlah Angsuran (Tenor) <span class="text-danger">*</span></label>
                                    <input type="number" name="tenor" value="{{ old('tenor', $loan->tenor) }}"
                                        class="form-control @error('tenor') is-invalid @enderror" min="1"
                                        max="360" required>
                                    @error('tenor')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Berapa kali angsuran (dalam bulan)</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="installment_amount">Jumlah per Angsuran <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">IDR</span>
                                        </div>
                                        <input type="number" name="installment_amount"
                                            value="{{ old('installment_amount', $loan->principal > 0 && $loan->tenor > 0 ? round($loan->principal / $loan->tenor, 0) : '') }}"
                                            class="form-control @error('installment_amount') is-invalid @enderror"
                                            step="0.01" min="0" required>
                                    </div>
                                    @error('installment_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Nilai rupiah per angsuran (auto-calculated dari
                                        principal ÷ tenor)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="start_angsuran_ke">Mulai Angsuran ke</label>
                                    <input type="number" name="start_angsuran_ke"
                                        class="form-control @error('start_angsuran_ke') is-invalid @enderror"
                                        value="{{ old('start_angsuran_ke', 1) }}" min="1">
                                    @error('start_angsuran_ke')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Angsuran mulai dari nomor berapa</small>
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="form-group">
                                    <label for="account_id">Account Pembebanan</label>
                                    <select name="account_id"
                                        class="form-control select2bs4 @error('account_id') is-invalid @enderror">
                                        <option value="">-- pilih bank account untuk pembayaran (opsional) --</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ $account->id == old('account_id') ? 'selected' : '' }}>
                                                {{ $account->account_number }} - {{ $account->account_name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Rekening bank akan otomatis terisi saat linking dengan bilyet giro atau auto debit. Kosongkan jika akan di-link kemudian.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning" id="generation-summary" style="display:none;">
                                    <h5><i class="fas fa-calculator"></i> Generation Summary:</h5>
                                    <div id="summary-content"></div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-6">
                                    <a href="{{ route('accounting.loans.show', $loan->id) }}"
                                        class="btn btn-secondary btn-sm">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                                <div class="col-6 text-right">
                                    <button type="submit" class="btn btn-success btn-sm" id="generate-btn">
                                        <i class="fas fa-cog"></i> Generate Installments
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-selection__rendered {
            line-height: 2.2 !important;
        }
    </style>
@endsection

@section('scripts')
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function() {
            // Initialize Select2 with better formatting
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: '-- pilih bank account untuk pembayaran (opsional) --',
                allowClear: true,
                width: '100%'
            });

            // Update summary when account is selected
            $('#account_id').on('select2:select', function(e) {
                calculateSummary();
            });

            // Calculate and show generation summary
            function calculateSummary() {
                const tenor = parseInt($('input[name="tenor"]').val()) || 0;
                const amount = parseFloat($('input[name="installment_amount"]').val()) || 0;
                const startDate = $('input[name="start_due_date"]').val();
                const startNumber = parseInt($('input[name="start_angsuran_ke"]').val()) || 1;

                if (tenor > 0 && amount > 0 && startDate) {
                    const totalAmount = tenor * amount;
                    const endNumber = startNumber + tenor - 1;

                    const selectedAccount = $('#account_id').val();
                    const accountText = selectedAccount ? $('#account_id option:selected').text() : '<em class="text-muted">Akan di-set saat linking dengan bilyet giro/auto debit</em>';
                    
                    let summaryHtml = `
            <div class="row">
              <div class="col-md-6">
                <p><strong>Jumlah Angsuran:</strong> ${tenor} installments</p>
                <p><strong>Nomor Angsuran:</strong> #${startNumber} sampai #${endNumber}</p>
                <p><strong>Tanggal Mulai:</strong> ${formatDate(startDate)}</p>
              </div>
              <div class="col-md-6">
                <p><strong>Amount per Bulan:</strong> IDR ${formatNumber(amount)}</p>
                <p><strong>Total Amount:</strong> IDR ${formatNumber(totalAmount)}</p>
                <p><strong>Account:</strong> ${accountText}</p>
              </div>
            </div>
          `;

                    $('#summary-content').html(summaryHtml);
                    $('#generation-summary').slideDown();
                } else {
                    $('#generation-summary').slideUp();
                }
            }

            function formatNumber(num) {
                return num.toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            }

            function formatDate(dateStr) {
                const date = new Date(dateStr);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }

            // Trigger calculation on input change
            $('input[name="tenor"], input[name="installment_amount"], input[name="start_due_date"], input[name="start_angsuran_ke"]')
                .on('change keyup', function() {
                    calculateSummary();
                });

            // Form validation before submit
            $('form').on('submit', function(e) {
                const tenor = parseInt($('input[name="tenor"]').val());
                const amount = parseFloat($('input[name="installment_amount"]').val());
                const startDate = $('input[name="start_due_date"]').val();

                if (!tenor || !amount || !startDate) {
                    e.preventDefault();
                    alert('Please fill in all required fields (marked with *)');
                    return false;
                }

                if (!confirm(`Are you sure you want to generate ${tenor} installments?`)) {
                    e.preventDefault();
                    return false;
                }

                $('#generate-btn').html('<i class="fas fa-spinner fa-spin"></i> Generating...').prop(
                    'disabled', true);
            });

            // Initial calculation
            calculateSummary();
        });
    </script>
@endsection
