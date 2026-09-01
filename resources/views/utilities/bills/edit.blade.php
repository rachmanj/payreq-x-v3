@extends('templates.main')

@section('title_page')
    Edit Tagihan
@endsection

@section('breadcrumb_title')
    utilities / tagihan / edit
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-edit"></i> Edit Tagihan — {{ $bill->nomor_tagihan ?: '#' . $bill->id }}
                        </h3>
                        <a href="{{ route('utilities.bills.index') }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                <form action="{{ route('utilities.bills.update', $bill->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="utility_customer_id">ID Pelanggan <span class="text-danger">*</span></label>
                            <select name="utility_customer_id" id="utility_customer_id"
                                class="form-control select2bs4 @error('utility_customer_id') is-invalid @enderror" required>
                                <option value="">Pilih ID Pelanggan</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-tipe="{{ $customer->tipe ?? 'postpaid' }}"
                                        {{ old('utility_customer_id', $bill->utility_customer_id) == $customer->id ? 'selected' : '' }}>
                                        [{{ strtoupper($customer->jenis_utilitas) }}] {{ $customer->id_pelanggan }} —
                                        {{ $customer->nama }} ({{ $customer->project }})
                                    </option>
                                @endforeach
                            </select>
                            @error('utility_customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="tipe">Tipe Pembayaran <span class="text-danger">*</span></label>
                            <select name="tipe" id="tipe" class="form-control @error('tipe') is-invalid @enderror" required>
                                @foreach ($tipeList as $key => $label)
                                    <option value="{{ $key }}" {{ old('tipe', $bill->customer?->tipe ?? 'postpaid') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tipe')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="periode">Periode <span class="text-danger">*</span></label>
                                    <input type="month" name="periode" id="periode"
                                        class="form-control @error('periode') is-invalid @enderror"
                                        value="{{ old('periode', $bill->periode) }}" required>
                                    @error('periode')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jumlah_tagihan">Jumlah Tagihan <span class="text-danger">*</span></label>
                                    <input type="number" name="jumlah_tagihan" id="jumlah_tagihan" step="0.01" min="0"
                                        class="form-control @error('jumlah_tagihan') is-invalid @enderror"
                                        value="{{ old('jumlah_tagihan', $bill->jumlah_tagihan) }}" required>
                                    @error('jumlah_tagihan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row" id="field-postpaid">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_jatuh_tempo">Tanggal Jatuh Tempo <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_jatuh_tempo" id="tanggal_jatuh_tempo"
                                        class="form-control @error('tanggal_jatuh_tempo') is-invalid @enderror"
                                        value="{{ old('tanggal_jatuh_tempo', $bill->tanggal_jatuh_tempo?->format('Y-m-d')) }}">
                                    @error('tanggal_jatuh_tempo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nomor_tagihan">Nomor Tagihan</label>
                                    <input type="text" name="nomor_tagihan" id="nomor_tagihan"
                                        class="form-control @error('nomor_tagihan') is-invalid @enderror"
                                        value="{{ old('nomor_tagihan', $bill->nomor_tagihan) }}">
                                    @error('nomor_tagihan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row d-none" id="field-prepaid">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_bayar">Tanggal Beli <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_bayar" id="tanggal_bayar"
                                        class="form-control @error('tanggal_bayar') is-invalid @enderror"
                                        value="{{ old('tanggal_bayar', $bill->tanggal_bayar?->format('Y-m-d')) }}">
                                    @error('tanggal_bayar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nomor_token">Nomor Token</label>
                                    <input type="text" name="nomor_token" id="nomor_token"
                                        class="form-control @error('nomor_token') is-invalid @enderror"
                                        value="{{ old('nomor_token', $bill->nomor_token) }}" placeholder="Opsional">
                                    @error('nomor_token')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meter_awal">Meter Awal</label>
                                    <input type="number" name="meter_awal" id="meter_awal" min="0"
                                        class="form-control @error('meter_awal') is-invalid @enderror"
                                        value="{{ old('meter_awal', $bill->meter_awal) }}">
                                    @error('meter_awal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meter_akhir">Meter Akhir</label>
                                    <input type="number" name="meter_akhir" id="meter_akhir" min="0"
                                        class="form-control @error('meter_akhir') is-invalid @enderror"
                                        value="{{ old('meter_akhir', $bill->meter_akhir) }}">
                                    @error('meter_akhir')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="keterangan">Keterangan</label>
                            <textarea name="keterangan" id="keterangan" rows="2"
                                class="form-control @error('keterangan') is-invalid @enderror">{{ old('keterangan', $bill->keterangan) }}</textarea>
                            @error('keterangan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="vj-btn vj-btn-primary"><i class="fas fa-save"></i> Simpan</button>
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
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function() {
            function toggleTipeFields() {
                const tipe = $('#tipe').val();
                if (tipe === 'prepaid') {
                    $('#field-postpaid').addClass('d-none');
                    $('#field-prepaid').removeClass('d-none');
                    $('#tanggal_jatuh_tempo').prop('required', false);
                } else {
                    $('#field-postpaid').removeClass('d-none');
                    $('#field-prepaid').addClass('d-none');
                    $('#tanggal_jatuh_tempo').prop('required', true);
                }
            }

            function initCustomerSelect(tipe) {
                const $select = $('#utility_customer_id');

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    theme: 'bootstrap4',
                    matcher: function(params, data) {
                        const $opt = $(data.element);
                        // Placeholder ("Pilih ID Pelanggan") selalu tampil
                        if ($opt.val() === '') {
                            return data;
                        }
                        const optTipe = $opt.data('tipe') || 'postpaid';
                        if (optTipe !== tipe) {
                            return null;
                        }
                        const term = $.trim(params.term).toLowerCase();
                        if (term === '') {
                            return data;
                        }
                        return data.text.toLowerCase().indexOf(term) > -1 ? data : null;
                    }
                });

                // Hapus pilihan jika customer terpilih tidak cocok dengan tipe
                const $selected = $select.find(':selected');
                if ($selected.length && $selected.val() !== '' && ($selected.data('tipe') || 'postpaid') !== tipe) {
                    $select.val('').trigger('change');
                }
            }

            $('#tipe').on('change', function() {
                const tipe = $(this).val();
                toggleTipeFields();
                initCustomerSelect(tipe);
            });

            toggleTipeFields();
            initCustomerSelect($('#tipe').val());
        });
    </script>
@endsection
