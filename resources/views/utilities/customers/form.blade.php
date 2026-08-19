@php
    $customer = $customer ?? null;
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="jenis_utilitas">Jenis Utilitas <span class="text-danger">*</span></label>
            <select name="jenis_utilitas" id="jenis_utilitas"
                class="form-control @error('jenis_utilitas') is-invalid @enderror" required>
                <option value="">Pilih Jenis</option>
                @foreach ($jenisList as $key => $label)
                    <option value="{{ $key }}"
                        {{ old('jenis_utilitas', $customer->jenis_utilitas ?? '') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('jenis_utilitas')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="tipe">Tipe Pembayaran <span class="text-danger">*</span></label>
            <select name="tipe" id="tipe" class="form-control @error('tipe') is-invalid @enderror" required>
                @foreach ($tipeList as $key => $label)
                    <option value="{{ $key }}"
                        {{ old('tipe', $customer->tipe ?? 'postpaid') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('tipe')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="id_pelanggan">ID Pelanggan <span class="text-danger">*</span></label>
            <input type="text" name="id_pelanggan" id="id_pelanggan"
                class="form-control @error('id_pelanggan') is-invalid @enderror"
                value="{{ old('id_pelanggan', $customer->id_pelanggan ?? '') }}" required>
            @error('id_pelanggan')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="nama">Nama / Alias <span class="text-danger">*</span></label>
    <input type="text" name="nama" id="nama" class="form-control @error('nama') is-invalid @enderror"
        value="{{ old('nama', $customer->nama ?? '') }}" required>
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="lokasi">Lokasi</label>
    <input type="text" name="lokasi" id="lokasi" class="form-control @error('lokasi') is-invalid @enderror"
        value="{{ old('lokasi', $customer->lokasi ?? '') }}">
    @error('lokasi')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="project">Project <span class="text-danger">*</span></label>
            <select name="project" id="project" class="form-control select2bs4 @error('project') is-invalid @enderror"
                required>
                <option value="">Pilih Project</option>
                @foreach ($projects as $proj)
                    <option value="{{ $proj->code }}"
                        {{ old('project', $customer->project ?? '') === $proj->code ? 'selected' : '' }}>
                        {{ $proj->code }}
                    </option>
                @endforeach
            </select>
            @error('project')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="account_id">Akun COA</label>
            <select name="account_id" id="account_id"
                class="form-control select2bs4 @error('account_id') is-invalid @enderror">
                <option value="">Tanpa Mapping</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}"
                        {{ old('account_id', $customer->account_id ?? '') == $account->id ? 'selected' : '' }}>
                        {{ $account->account_number }} — {{ $account->account_name }}
                    </option>
                @endforeach
            </select>
            @error('account_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mb-0">
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
            {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}>
        <label class="custom-control-label" for="is_active">Aktif</label>
    </div>
</div>
