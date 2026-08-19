@extends('templates.main')

@section('title_page')
    Tambah Tagihan
@endsection

@section('breadcrumb_title')
    utilities / tagihan / tambah
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Input Tagihan Baru</h3>
                    <a href="{{ route('utilities.bills.index') }}" class="btn btn-sm btn-default float-right">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <form action="{{ route('utilities.bills.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="utility_customer_id">ID Pelanggan <span class="text-danger">*</span></label>
                            <select name="utility_customer_id" id="utility_customer_id"
                                class="form-control select2bs4 @error('utility_customer_id') is-invalid @enderror" required>
                                <option value="">Pilih ID Pelanggan</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-tipe="{{ $customer->tipe ?? 'postpaid' }}"
                                        {{ old('utility_customer_id') == $customer->id ? 'selected' : '' }}>
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
                                    <option value="{{ $key }}" {{ old('tipe', 'postpaid') === $key ? 'selected' : '' }}>
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
                                        value="{{ old('periode', $periodeDefault) }}" required>
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
                                        value="{{ old('jumlah_tagihan') }}" required>
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
                                        value="{{ old('tanggal_jatuh_tempo') }}">
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
                                        value="{{ old('nomor_tagihan') }}">
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
                                        value="{{ old('tanggal_bayar', now()->toDateString()) }}">
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
                                        value="{{ old('nomor_token') }}" placeholder="Opsional">
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
                                        value="{{ old('meter_awal') }}">
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
                                        value="{{ old('meter_akhir') }}">
                                    @error('meter_akhir')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="keterangan">Keterangan</label>
                            <textarea name="keterangan" id="keterangan" rows="2"
                                class="form-control @error('keterangan') is-invalid @enderror">{{ old('keterangan') }}</textarea>
                            @error('keterangan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

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

            function syncTipeFromCustomer() {
                const selected = $('#utility_customer_id option:selected');
                const customerTipe = selected.data('tipe');
                if (customerTipe) {
                    $('#tipe').val(customerTipe);
                    toggleTipeFields();
                }
            }

            $('#tipe').on('change', toggleTipeFields);
            $('#utility_customer_id').on('change', syncTipeFromCustomer);

            toggleTipeFields();
            if ($('#utility_customer_id').val()) {
                syncTipeFromCustomer();
            }
        });
    </script>
@endsection
