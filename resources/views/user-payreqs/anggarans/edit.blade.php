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
                <h3 class="card-title">Edit RAB | {{ $anggaran->nomor }}</h3>
                <a href="{{ route('user-payreqs.anggarans.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
            
                <form action="{{ route('user-payreqs.anggarans.proses') }}" method="POST" enctype="multipart/form-data" id="form_anggaran">
                @csrf
                <input type="hidden" name="button_type" id="button_type_field" value="">

                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label for="rab_no">RAB No <small>(optional)</small></label>
                                <input type="hidden" name="anggaran_id" value="{{ $anggaran->id }}">
                                <input type="hidden" name="nomor" value="{{ $anggaran->nomor }}">
                                <input type="text" name="rab_no" id="rab_no" class="form-control" value="{{ $anggaran->rab_no }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="date">Date</label>
                                <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror" value="{{ $anggaran->date }}">
                                @error('date')
                                <div class="invalid-feedback">
                                {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="project">For Project <div></div></label>
                                <select name="project" id="project" class="form-control @error('project') is-invalid @enderror">
                                @foreach ($projects as $project)
                                    <option value="{{ $project->code }}" {{ $project->code == $anggaran->rab_project ? 'selected' : '' }}>{{ $project->code }}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror">{{ $anggaran->description }}</textarea>
                            @error('description')
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
                                <label for="amount">Amount</label>
                                <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ $anggaran->amount }}">
                                @error('amount')
                                <div class="invalid-feedback">
                                {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-8">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-6">
                                        <label for="file_upload">Existing file</label>
                                        <input type="text" class="form-control" readonly value="{{ $origin_filename }}">
                                    </div>
                                    <div class="col-6">
                                        <label for="file_upload">Replace file</label>
                                        <input type="file" name="file_upload" id="file_upload" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-secondary mt-2">
                        <div class="card-header"><h5 class="mb-0">Budget detail lines</h5></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" id="tbl-budget-details">
                                    <thead>
                                        <tr>
                                            <th style="min-width:220px">Account</th>
                                            <th>Description</th>
                                            <th style="width:90px">Qty</th>
                                            <th style="width:90px">Unit</th>
                                            <th style="width:110px">Unit price</th>
                                            <th style="width:110px">Amount</th>
                                            <th style="width:40px"></th>
                                        </tr>
                                    </thead>
                                    @include('user-payreqs.anggarans.partials.budget-detail-rows', ['accounts' => $accounts, 'details' => $anggaran->details])
                                </table>
                            </div>
                            <div class="p-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="btn-add-budget-detail">Add line</button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <label for="rab_type">Type</label>
                            <div class="form-group">
                                <div class="form-check d-inline mr-4">
                                    <input class="form-check-input" type="radio" value="periode" name="rab_type" {{ $anggaran->type === 'periode' ? 'checked' : '' }}>
                                    <label class="form-check-label">Periode</label>
                                </div>
                                <div class="form-check d-inline mr-4">
                                    <input class="form-check-input" type="radio" name="rab_type" value="event" {{ $anggaran->type === 'event' ? 'checked' : '' }}>
                                    <label class="form-check-label">Event</label>
                                </div>
                                <div class="form-check d-inline">
                                    <input class="form-check-input" type="radio" name="rab_type" value="buc" {{ $anggaran->type === 'buc' ? 'checked' : '' }}>
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
                                    <option value="{{ $periode_anggaran->periode }}" {{ $anggaran->periode_anggaran ==  $periode_anggaran->periode ? 'selected' : ''}}>{{ $formattedDate }}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="other">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $anggaran->start_date }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $anggaran->end_date }}">
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

        // when refresh page, check which radio button is checked
        if ($('input[type=radio][name=rab_type]:checked').val() == 'periode') {
            $('#periode').show();
            $('#other').hide();
        } else if ($('input[type=radio][name=rab_type]:checked').val() == 'event') {
            $('#periode').hide();
            $('#other').show();
        } else if ($('input[type=radio][name=rab_type]:checked').val() == 'buc') {
            $('#periode').hide();
            $('#other').show();
        }

        // when radio button is clicked
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

    $('#btn-draft').on('click', function() {
        $('#button_type_field').val('edit');
    });

    $('#btn-submit').on('click', function() {
        $('#button_type_field').val('edit_submit');
    });

    let detailIdx = $('#budget-detail-body tr').length;
    $('#btn-add-budget-detail').on('click', function () {
        const $tr = $('#budget-detail-body tr:first').clone();
        $tr.find('input,select').each(function () {
            const name = $(this).attr('name');
            if (!name) {
                return;
            }
            const newName = name.replace(/details\[\d+\]/, 'details[' + detailIdx + ']');
            $(this).attr('name', newName);
            if ($(this).is('select')) {
                $(this).prop('selectedIndex', 0);
            } else if ($(this).hasClass('detail-qty')) {
                $(this).val('1');
            } else if ($(this).hasClass('detail-unit-price') || $(this).hasClass('detail-amount')) {
                $(this).val('0');
            } else {
                $(this).val('');
            }
        });
        $('#budget-detail-body').append($tr);
        detailIdx++;
    });
</script>
@endsection