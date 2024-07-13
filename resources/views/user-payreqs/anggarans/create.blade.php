@extends('templates.main')

@section('title_page')
    RAB
@endsection

@section('breadcrumb_title')
    payreqs / rab
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create new RAB</h3>
                <a href="{{ route('user-payreqs.anggarans.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <form action="{{ route('user-payreqs.anggarans.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="nomor">RAB No</label>
                                    <input type="text" name="nomor" id="nomor" class="form-control @error('nomor') is-invalid @enderror">
                                    @error('nomor')
                                      <div class="invalid-feedback">
                                        {{ $message }}
                                      </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror">
                                    @error('date')
                                      <div class="invalid-feedback">
                                        {{ $message }}
                                      </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <textarea name="description" cols="120" rows="2"></textarea>
                            </div>
                        </div>



                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection