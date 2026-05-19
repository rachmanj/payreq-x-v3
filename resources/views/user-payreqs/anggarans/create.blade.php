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
                    <a href="{{ route('user-payreqs.anggarans.index') }}" class="btn btn-sm btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">

                    <form action="{{ route('user-payreqs.anggarans.proses') }}" method="POST" enctype="multipart/form-data"
                        id="form_anggaran">
                        @csrf
                        <input type="hidden" name="button_type" id="button_type_field"
                            value="{{ old('button_type', '') }}">

                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="rab_no">RAB No <small>(optional)</small></label>
                                    <input type="hidden" name="nomor" value="{{ $nomor }}">
                                    <input type="text" name="rab_no" id="rab_no" class="form-control"
                                        value="{{ old('rab_no', '') }}">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" id="date"
                                        class="form-control @error('date') is-invalid @enderror"
                                        value="{{ old('date', '') }}">
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
                                    <select name="project" id="project"
                                        class="form-control @error('project') is-invalid @enderror">
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->code }}"
                                                {{ old('project', auth()->user()->project) == $project->code ? 'selected' : '' }}>
                                                {{ $project->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea name="description" id="description"
                                        class="form-control @error('description') is-invalid @enderror">{{ old('description', '') }}</textarea>
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
                                @include('user-payreqs.anggarans.partials.amount-field', ['amountDefault' => '0'])
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="file_upload">Upload RAB</label>
                                    <input type="file" name="file_upload" id="file_upload" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="card card-outline card-secondary mt-2">
                            <div class="card-header">
                                <h5 class="mb-0">Budget detail lines</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" id="tbl-budget-details">
                                        <thead>
                                            <tr>
                                                <th>Description</th>
                                                <th style="width:90px">Qty</th>
                                                <th style="width:90px">Unit</th>
                                                <th style="width:110px">Unit price</th>
                                                <th style="width:110px">Amount</th>
                                                <th style="width:50px"></th>
                                            </tr>
                                        </thead>
                                        @include('user-payreqs.anggarans.partials.budget-detail-rows')
                                    </table>
                                </div>
                                <div class="p-2">
                                    <button type="button" class="btn btn-sm btn-secondary" id="btn-add-budget-detail">Add
                                        line</button>
                                    @error('details')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="rab_type">Type</label>
                                <div class="form-group">
                                    <div class="form-check d-inline mr-4">
                                        <input class="form-check-input" type="radio" value="periode" name="rab_type"
                                            {{ old('rab_type', 'periode') === 'periode' ? 'checked' : '' }}>
                                        <label class="form-check-label">Periode</label>
                                    </div>
                                    <div class="form-check d-inline mr-4">
                                        <input class="form-check-input" type="radio" name="rab_type" value="event"
                                            {{ old('rab_type') === 'event' ? 'checked' : '' }}>
                                        <label class="form-check-label">Event</label>
                                    </div>
                                    <div class="form-check d-inline">
                                        <input class="form-check-input" type="radio" name="rab_type" value="buc"
                                            {{ old('rab_type') === 'buc' ? 'checked' : '' }}>
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
                                            <option value="{{ $periode_anggaran->periode }}"
                                                {{ old('periode_anggaran') == $periode_anggaran->periode ? 'selected' : '' }}>
                                                {{ $formattedDate }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="other">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control"
                                        value="{{ old('start_date', '') }}">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control"
                                        value="{{ old('end_date', '') }}">
                                </div>
                            </div>
                        </div>

                    </form>

                </div> {{-- card body --}}
                <div class="card-footer text-center">
                    <div class="row">
                        <div class="col-6">
                            <button type="submit" class="btn btn-primary btn-block" id="btn-draft"
                                form="form_anggaran"><i class="fas fa-save"></i> Save as Draft</button>
                        </div>
                        <div class="col-6">
                            <button type="submit" class="btn btn-warning btn-block" id="btn-submit"
                                form="form_anggaran"><i class="fas fa-paper-plane"></i> Save and Submit</button>
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
            function toggleRabTypeSections() {
                const val = $('input[type=radio][name=rab_type]:checked').val();
                if (val === 'periode') {
                    $('#periode').show();
                    $('#other').hide();
                } else {
                    $('#periode').hide();
                    $('#other').show();
                }
            }

            toggleRabTypeSections();

            $('input[type=radio][name=rab_type]').change(toggleRabTypeSections);
        });

        $('#btn-draft').on('click', function() {
            $('#button_type_field').val('create');
        });

        $('#btn-submit').on('click', function() {
            $('#button_type_field').val('create_submit');
        });

    </script>
    @include('user-payreqs.anggarans.partials.budget-detail-scripts')
@endsection
