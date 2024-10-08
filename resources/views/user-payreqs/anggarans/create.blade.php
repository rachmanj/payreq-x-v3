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
                <h3 class="card-title">Create new RAB | {{ $nomor }}</h3>
                <a href="{{ route('user-payreqs.anggarans.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
            
                <form action="{{ route('user-payreqs.anggarans.proses') }}" method="POST" enctype="multipart/form-data" id="form_anggaran">
                @csrf

                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label for="rab_no">RAB No <small>(optional)</small></label>
                                <input type="hidden" name="nomor" value="{{ $nomor }}">
                                <input type="text" name="rab_no" id="rab_no" class="form-control">
                            </div>
                        </div>
                        <div class="col-4">
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
                        <div class="col-4">
                            <div class="form-group">
                                <label for="project">For Project</label>
                                <select name="project" id="project" class="form-control @error('project') is-invalid @enderror">
                                @foreach ($projects as $project)
                                    <option value="{{ $project->code }}" {{ $project->code == Auth()->user()->project ? 'selected' : '' }}>{{ $project->code }}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"></textarea>
                            @error('description')
                            <div class="invalid-feedback">
                            {{ $message }}
                            </div>
                            @enderror
                        </div>
                        </div>
                    </div>

                    <div class="row">
                        
                        <div class="col-6">
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror">
                                @error('amount')
                                <div class="invalid-feedback">
                                {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="file_upload">Upload RAB</label>
                                <input type="file" name="file_upload" id="file_upload" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <label for="rab_type">Type</label>
                            <div class="form-group">
                                <div class="form-check d-inline mr-4">
                                    <input class="form-check-input" type="radio" value="periode" name="rab_type" checked>
                                    <label class="form-check-label">Periode</label>
                                </div>
                                <div class="form-check d-inline mr-4">
                                    <input class="form-check-input" type="radio" name="rab_type" value="event">
                                    <label class="form-check-label">Event</label>
                                </div>
                                <div class="form-check d-inline">
                                    <input class="form-check-input" type="radio" name="rab_type" value="buc">
                                    <label class="form-check-label">BUC <small>(DNC only)</small></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- this row will show depent on what radio button input is clicked --}}
                    <hr>
                    <div class="row" id="periode">
                        <div class="col-4">
                            <div class="form-group">
                                <label for="periode_anggaran">Periode</label>
                                <select name="periode_anggaran" id="periode_anggaran" class="form-control">
                                @foreach ($periode_anggarans as $periode_anggaran)
                                    @php
                                        $date = \Carbon\Carbon::parse($periode_anggaran->periode);
                                        $formattedDate = $date->format('F Y');
                                    @endphp
                                    <option value="{{ $periode_anggaran->periode }}">{{ $formattedDate }}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="other">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                
                </form>
                
            </div> {{-- card body --}}
            <div class="card-footer text-center">
                <div class="row">
                    <div class="col-6">
                        <button type="submit" class="btn btn-primary btn-block" id="btn-draft" form="form_anggaran"><i class="fas fa-save"></i> Save as Draft</button>
                    </div>
                    <div class="col-6">
                        <button type="submit" class="btn btn-warning btn-block" id="btn-submit" form="form_anggaran"><i class="fas fa-paper-plane"></i> Save and Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#other').hide();
        $('#periode').show();

        $('input[type=radio][name=rab_type]').change(function() {
            if (this.value == 'periode') {
                $('#periode').show();
                $('#other').hide();
            } else if (this.value == 'event') {
                $('#periode').hide();
                $('#other').show();
            } else if (this.value == 'buc') {
                $('#periode').hide();
                $('#other').show();
            }
        });
    });

    // btn-save as draft
    $('#btn-draft').click(function() {
        // add attribute name="draft" to form
        $('form').append('<input type="hidden" name="button_type" value="create">');
    });

    // btn-save and submit
    $('#btn-submit').click(function() {
        // add attribute name="draft" to form
        $('form').append('<input type="hidden" name="button_type" value="create_submit">');
    });
</script>
@endsection