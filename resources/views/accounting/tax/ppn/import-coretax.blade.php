@extends('templates.main')

@section('title_page')
    Impor Prepopulasi Coretax (PPN Masukan)
@endsection

@section('breadcrumb_title')
    accounting / tax / ppn / import-coretax
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            @include('accounting.tax.ppn.partials.nav', ['masaPajak' => $masaPajak])

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Unggah ekspor Coretax</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Gunakan berkas Excel hasil ekspor prepopulasi PPN Masukan dari portal Coretax (sheet <strong>data</strong>, 20 kolom).
                        Impor ulang untuk masa yang sama akan <strong>memperbarui</strong> baris yang sudah ada (idempoten).
                    </p>

                    <form method="GET" action="{{ route('accounting.tax.ppn.import-coretax.index') }}" class="form-inline mb-3">
                        <label class="mr-2" for="masa_pajak">Masa pajak impor</label>
                        <select name="masa_pajak" id="masa_pajak" class="form-control form-control-sm mr-2">
                            @foreach ($masaOptions as $opt)
                                <option value="{{ $opt }}" @selected($opt === $masaPajak)>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-secondary">Tampilkan</button>
                    </form>

                    @if ($importedCount > 0)
                        <div class="alert alert-info py-2">
                            Masa <strong>{{ $masaPajak }}</strong>: {{ number_format($importedCount) }} baris tersimpan,
                            total PPN {{ number_format($importedPpn, 0, ',', '.') }}.
                        </div>
                    @endif

                    @can('manage_tax_monitoring')
                        <div class="form-group">
                            <label for="coretax_file">Berkas (.xlsx atau .csv)</label>
                            <input type="file" id="coretax_file" class="form-control-file" accept=".xlsx,.xls,.csv">
                        </div>
                        <button type="button" id="btn-preview" class="btn btn-primary">Pratinjau</button>
                    @else
                        <p class="text-muted mb-0">Anda hanya dapat melihat ringkasan impor. Izin kelola diperlukan untuk mengunggah.</p>
                    @endcan
                </div>
            </div>

            <div id="preview-panel" class="card mb-3 d-none">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Pratinjau impor</h3>
                    <span id="preview-summary" class="text-muted small"></span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-striped" id="preview-table">
                        <thead>
                            <tr>
                                <th>Nomor FP</th>
                                <th>Penjual</th>
                                <th>Tanggal</th>
                                <th>DPP</th>
                                <th>PPN</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                @can('manage_tax_monitoring')
                    <div class="card-footer">
                        <form method="POST" action="{{ route('accounting.tax.ppn.import-coretax.store') }}" id="commit-form">
                            @csrf
                            <input type="hidden" name="preview_token" id="preview_token" value="">
                            <button type="submit" class="btn btn-success" id="btn-commit"
                                onclick="return confirm('Simpan data prepopulasi ke database?');">
                                Simpan impor
                            </button>
                        </form>
                    </div>
                @endcan
            </div>

            <div id="preview-error" class="alert alert-danger d-none"></div>

        </div>
    </div>
@endsection

@push('scripts')
    @can('manage_tax_monitoring')
        <script>
            document.getElementById('btn-preview')?.addEventListener('click', function () {
                const fileInput = document.getElementById('coretax_file');
                const masa = document.getElementById('masa_pajak').value;
                if (!fileInput.files.length) {
                    alert('Pilih berkas terlebih dahulu.');
                    return;
                }

                const formData = new FormData();
                formData.append('file', fileInput.files[0]);
                formData.append('masa_pajak', masa);
                formData.append('_token', '{{ csrf_token() }}');

                document.getElementById('preview-error').classList.add('d-none');
                document.getElementById('btn-preview').disabled = true;

                fetch('{{ route('accounting.tax.ppn.import-coretax.preview') }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' },
                })
                    .then(r => r.json().then(data => ({ ok: r.ok, data })))
                    .then(({ ok, data }) => {
                        document.getElementById('btn-preview').disabled = false;
                        if (!ok || !data.success) {
                            const msg = data.message || 'Pratinjau gagal.';
                            const el = document.getElementById('preview-error');
                            el.textContent = msg;
                            el.classList.remove('d-none');
                            document.getElementById('preview-panel').classList.add('d-none');
                            return;
                        }

                        document.getElementById('preview_token').value = data.preview_token;
                        const s = data.summary;
                        document.getElementById('preview-summary').textContent =
                            s.row_count + ' baris · Total PPN ' + Number(s.total_ppn).toLocaleString('id-ID');

                        const tbody = document.querySelector('#preview-table tbody');
                        tbody.innerHTML = '';
                        (data.rows || []).forEach(function (row) {
                            const tr = document.createElement('tr');
                            tr.innerHTML = '<td><small>' + row.faktur_no + '</small></td>'
                                + '<td><small>' + (row.supplier_name || '') + '</small></td>'
                                + '<td><small>' + (row.faktur_date || '—') + '</small></td>'
                                + '<td class="text-right"><small>' + Number(row.dpp).toLocaleString('id-ID') + '</small></td>'
                                + '<td class="text-right"><small>' + Number(row.ppn).toLocaleString('id-ID') + '</small></td>'
                                + '<td><small>' + (row.status_faktur || '') + '</small></td>';
                            tbody.appendChild(tr);
                        });

                        document.getElementById('preview-panel').classList.remove('d-none');
                    })
                    .catch(function () {
                        document.getElementById('btn-preview').disabled = false;
                        alert('Kesalahan jaringan saat pratinjau.');
                    });
            });
        </script>
    @endcan
@endpush
