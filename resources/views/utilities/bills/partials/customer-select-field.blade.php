<div class="form-group">
    <label for="utility_customer_id">ID Pelanggan <span class="text-danger">*</span></label>
    <select name="utility_customer_id" id="utility_customer_id"
        class="form-control select2bs4 @error('utility_customer_id') is-invalid @enderror" required>
        <option value="">Pilih ID Pelanggan</option>
        @foreach ($customers as $customer)
            @php
                $lokasiPart = filled($customer->lokasi) ? ' · '.$customer->lokasi : '';
                $meterPart = filled($customer->nomor_meter) ? ' · Meter '.$customer->nomor_meter : '';
                $optionLabel = '['.strtoupper($customer->jenis_utilitas).'] '.$customer->id_pelanggan.' — '.$customer->nama.$lokasiPart.' ('.$customer->project.')'.$meterPart;
                $tipe = $customer->tipe ?? 'postpaid';
            @endphp
            <option value="{{ $customer->id }}"
                data-tipe="{{ $tipe }}"
                data-id-pelanggan="{{ $customer->id_pelanggan }}"
                data-nomor-meter="{{ $customer->nomor_meter ?? '' }}"
                data-nama="{{ $customer->nama }}"
                data-lokasi="{{ $customer->lokasi ?? '' }}"
                data-project="{{ $customer->project }}"
                data-jenis="{{ strtoupper($customer->jenis_utilitas) }}"
                {{ (string) $selectedCustomerId === (string) $customer->id ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
    @error('utility_customer_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div id="utility-customer-info" class="alert alert-light border small mt-2 mb-0 py-2 px-3">
        <div class="row">
            <div class="col-sm-6 mb-1 mb-sm-0">
                <span class="text-muted">ID Pelanggan:</span>
                <span id="uci-id-pelanggan" class="font-weight-bold">—</span>
            </div>
            <div class="col-sm-6">
                <span class="text-muted">Nama:</span>
                <span id="uci-nama" class="font-weight-bold">—</span>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-sm-6 mb-1 mb-sm-0">
                <span class="text-muted">Lokasi:</span>
                <span id="uci-lokasi" class="font-weight-bold">—</span>
            </div>
            <div class="col-sm-6">
                <span class="text-muted">Jenis:</span>
                <span id="uci-jenis" class="font-weight-bold">—</span>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-sm-6 mb-1 mb-sm-0">
                <span class="text-muted">No. Meter:</span>
                <span id="uci-nomor-meter" class="font-weight-bold">—</span>
            </div>
            <div class="col-sm-6">
                <span class="text-muted">Tipe:</span>
                <span id="uci-tipe" class="font-weight-bold">—</span>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-sm-6 mb-1 mb-sm-0">
                <span class="text-muted">Project:</span>
                <span id="uci-project" class="font-weight-bold">—</span>
            </div>
        </div>
    </div>
</div>
