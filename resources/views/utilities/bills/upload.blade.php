@extends('templates.main')

@section('title_page')
    Upload Struk Tagihan Kolektif
@endsection

@section('breadcrumb_title')
    utilities / tagihan / upload struk
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload Struk "Daftar Tagihan Kolektif"</h3>
                    <a href="{{ route('utilities.bills.index') }}" class="btn btn-sm btn-default float-right">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <form action="{{ route('utilities.bills.parse-upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <p class="text-muted small">
                            Upload gambar struk tagihan kolektif PLN/PDAM/TELKOM. Sistem akan mengekstrak daftar
                            tagihan via AI, lalu menampilkan preview sebelum disimpan.
                        </p>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jenis_utilitas">Jenis Utilitas <span class="text-danger">*</span></label>
                                    <select name="jenis_utilitas" id="jenis_utilitas"
                                        class="form-control @error('jenis_utilitas') is-invalid @enderror" required>
                                        <option value="">Pilih Jenis</option>
                                        @foreach ($jenisList as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ old('jenis_utilitas') === $key ? 'selected' : '' }}>
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
                                    <label for="project">Project <span class="text-danger">*</span></label>
                                    <select name="project" id="project"
                                        class="form-control @error('project') is-invalid @enderror" required>
                                        <option value="">Pilih Project</option>
                                        @foreach ($projects as $proj)
                                            <option value="{{ $proj->code }}"
                                                {{ old('project') === $proj->code ? 'selected' : '' }}>
                                                {{ $proj->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('project')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="periode">Periode <span class="text-danger">*</span></label>
                            <input type="month" name="periode" id="periode"
                                class="form-control @error('periode') is-invalid @enderror"
                                value="{{ old('periode', $periodeDefault) }}" required>
                            @error('periode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="file">Gambar Struk <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="file" accept="image/*"
                                class="form-control-file @error('file') is-invalid @enderror" required>
                            <small class="form-text text-muted">Format: JPG, JPEG, PNG. Maks. 5 MB.</small>
                            @error('file')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Ekstrak Tagihan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
