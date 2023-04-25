@extends('templates.main')

@section('title_page')
  GIRO  
@endsection

@section('breadcrumb_title')
    giro
@endsection

@section('content')
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Edit Data</h3>
            <a href="{{ route('giros.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-undo"></i> Back</a>
            <input type="submit" name="" id="" value="Save" form="form-edit" class="btn btn-sm btn-success float-right mx-2">
          </div>

          <div class="card-body">
            <form action="{{ route('giros.update', $giro->id) }}" method="POST" id="form-edit" enctype="multipart/form-data">
              @csrf @method('PUT')

              <div class="row">
                <div class="col-6">
                  <div class="form-group">
                    <label for="nomor">Document No</label>
                    <input name="nomor" id="nomor" value="{{ old('nomor', $giro->nomor) }}" class="form-control @error('nomor') is-invalid @enderror">
                    @error('nomor')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-group">
                    <label for="tanggal">Date</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', $giro->tanggal) }}" class="form-control @error('tanggal') is-invalid @enderror">
                    @error('tanggal')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-4">
                  <div class="form-group">
                    <label for="bank">Bank</label>
                    <select name="bank" id="bank" class="form-control select2bs4">
                      @foreach ($banks as $bank)
                          <option value="{{ $bank }}" {{ $giro->bank === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                      <label for="account">Account</label>
                      <select name="account" id="account" class="form-control select2bs4">
                        @foreach ($accounts as $account)
                            <option value="{{ $account }}" {{ $giro->account === $account ? 'selected' : '' }}>{{ $account }}</option>
                        @endforeach
                      </select>
                    </div>
                </div>
                <div class="col-4">
                  <div class="form-group">
                    <label for="giro_type">Type</label>
                    <select name="giro_type" id="giro_type" class="form-control">
                          <option value="cek" {{ $giro->giro_type === 'cek' ? 'selected' : '' }}>Cek</option>
                          <option value="bilyet" {{ $giro->giro_type === 'bilyet' ? 'selected' : '' }}>Bilyet Giro</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-12">
                  <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <input name="remarks" id="remarks" value="{{ old('remarks', $giro->remarks) }}" class="form-control">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-12">
                  <div class="form-group">
                    <label for="use_for">Use For</label>
                    <textarea name="use_for" id="use_for" class="form-control" cols="30" rows="2">{{ old('use_for', $giro->use_for) }}</textarea>
                  </div>
                </div>
              </div>

              <div class="col-6">
                <div class="form-group">
                  <label for="file_upload">Upload file</label>
                  <input type="file" name="file_upload" id="file_upload" class="form-control">
                </div>
              </div>

            </form>
          </div>          
           
        </div>
      </div>
    </div>
@endsection