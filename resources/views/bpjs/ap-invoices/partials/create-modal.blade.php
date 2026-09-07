<div class="modal fade" id="createBpjsModal" tabindex="-1" role="dialog" aria-labelledby="createBpjsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('bpjs-ap-invoices.store') }}" id="createBpjsForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createBpjsModalLabel">
                        <i class="fas fa-file-invoice"></i> Buat AP Invoice BPJS
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jenis">Jenis BPJS <span class="text-danger">*</span></label>
                                <select name="jenis" id="jenis" class="form-control @error('jenis') is-invalid @enderror"
                                    required>
                                    <option value="">— Pilih —</option>
                                    @foreach (\App\Models\BpjsApInvoice::JENIS_LABELS as $key => $label)
                                        <option value="{{ $key }}" @selected(old('jenis') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('jenis')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="unit">Unit <span class="text-danger">*</span></label>
                                <select name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror"
                                    required>
                                    <option value="">— Pilih —</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->code }}" @selected(old('unit') === $project->code)>
                                            {{ $project->code }} — {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periode">Periode Iuran <span class="text-danger">*</span></label>
                                <input type="month" name="periode" id="periode"
                                    class="form-control @error('periode') is-invalid @enderror"
                                    value="{{ old('periode', now()->format('Y-m')) }}" required>
                                @error('periode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount">Nominal (IDR) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="amount" id="amount" min="1" step="1"
                                        class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount') }}" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="btnCopyLastAmount"
                                            title="Isi dari bulan lalu">
                                            <i class="fas fa-history"></i> Bulan lalu
                                        </button>
                                    </div>
                                </div>
                                <small id="lastAmountHint" class="form-text text-muted"></small>
                                @error('amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="doc_date">Tanggal Dokumen <span class="text-danger">*</span></label>
                                <input type="date" name="doc_date" id="doc_date"
                                    class="form-control @error('doc_date') is-invalid @enderror"
                                    value="{{ old('doc_date', now()->toDateString()) }}" required>
                                @error('doc_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="due_date">Jatuh Tempo <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" id="due_date"
                                    class="form-control @error('due_date') is-invalid @enderror"
                                    value="{{ old('due_date', now()->addDays(30)->toDateString()) }}" required>
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="vj-btn vj-btn-primary">
                        <i class="fas fa-eye"></i> Simpan & Preview
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const docDateInput = document.getElementById('doc_date');
        const dueDateInput = document.getElementById('due_date');

        if (docDateInput && dueDateInput) {
            docDateInput.addEventListener('change', function() {
                const base = new Date(this.value);
                if (!isNaN(base.getTime())) {
                    base.setDate(base.getDate() + 30);
                    dueDateInput.value = base.toISOString().split('T')[0];
                }
            });
        }

        const btnCopy = document.getElementById('btnCopyLastAmount');
        if (btnCopy) {
            btnCopy.addEventListener('click', function() {
                const jenis = document.getElementById('jenis').value;
                const unit = document.getElementById('unit').value;
                const periode = document.getElementById('periode').value;
                const hint = document.getElementById('lastAmountHint');

                if (!jenis || !unit || !periode) {
                    hint.textContent = 'Pilih jenis, unit, dan periode terlebih dahulu.';
                    return;
                }

                hint.textContent = 'Memuat...';

                fetch('{{ route('bpjs-ap-invoices.last-amount') }}?' + new URLSearchParams({
                    jenis,
                    unit,
                    periode
                }))
                    .then(r => r.json())
                    .then(data => {
                        if (data.found && data.amount) {
                            document.getElementById('amount').value = Math.round(data.amount);
                            hint.textContent = 'Diisi dari periode ' + data.periode_sumber + '.';
                        } else {
                            hint.textContent = 'Tidak ada data bulan sebelumnya (' + data.periode_sumber + ').';
                        }
                    })
                    .catch(() => {
                        hint.textContent = 'Gagal memuat nominal bulan lalu.';
                    });
            });
        }

        @if (isset($errors) && $errors->any() && !session('error'))
            $('#createBpjsModal').modal('show');
        @endif
    });
</script>
