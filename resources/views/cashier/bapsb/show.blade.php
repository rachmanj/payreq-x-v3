@extends('templates.main')

@section('title_page')
    BAPSB {{ $bapsb->nomor }}
@endsection

@section('breadcrumb_title')
    cashier / bapsb
@endsection

@section('content')
    <div class="row vj-show">
        <div class="col-12">
            <div class="card card-outline mb-3">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">{{ $bapsb->nomor }}</h5>
                    <div>
                        <a href="{{ route('cashier.bapsb.print', $bapsb) }}" class="btn btn-sm btn-success" target="_blank">Print</a>
                        @if ($bapsb->isEditable())
                            <a href="{{ route('cashier.bapsb.edit', $bapsb) }}" class="btn btn-sm btn-warning">Edit</a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Period</dt><dd class="col-sm-9">{{ $bapsb->period }}</dd>
                        <dt class="col-sm-3">Project</dt><dd class="col-sm-9">{{ $bapsb->project }}</dd>
                        <dt class="col-sm-3">Date</dt><dd class="col-sm-9">{{ $bapsb->bapsb_date?->format('d M Y') }}</dd>
                        <dt class="col-sm-3">Prepared by</dt><dd class="col-sm-9">{{ $bapsb->preparedBy?->name }}</dd>
                        <dt class="col-sm-3">Validation</dt>
                        <dd class="col-sm-9">
                            <span class="vj-chip vj-chip-{{ $bapsb->validation_status === 'validated' ? 'success' : 'warning' }}">{{ ucfirst($bapsb->validation_status) }}</span>
                            @if ($bapsb->validated_at)
                                <small class="text-muted">by {{ $bapsb->validatedBy?->name }} on {{ $bapsb->validated_at->format('d M Y H:i') }}</small>
                            @endif
                        </dd>
                        <dt class="col-sm-3">Submission</dt>
                        <dd class="col-sm-9">{{ $bapsb->submitted_at ? $bapsb->submitted_at->format('d M Y H:i') : 'Draft' }}</dd>
                        <dt class="col-sm-3">Signed PDF</dt>
                        <dd class="col-sm-9">
                            @if ($bapsb->dokumen)
                                <a href="{{ $bapsb->dokumen->filename1 }}" target="_blank">Download PDF</a>
                                <small class="text-muted">({{ $bapsb->dokumen->validation_status }})</small>
                            @else
                                <span class="text-muted">Not uploaded</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card card-outline mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Bilyets</h5></div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>Bank account</th>
                                <th>Physical</th>
                                <th>Location</th>
                                <th>History</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bapsb->lines as $line)
                                <tr>
                                    <td>{{ $line->nomor }}</td>
                                    <td>{{ $line->bank_account }}</td>
                                    <td>{{ $line->physical_present ? 'Present' : 'Missing' }}</td>
                                    <td>{{ $line->location }} @if($line->location_note)<small class="text-muted">({{ $line->location_note }})</small>@endif</td>
                                    <td><a href="{{ route('cashier.bilyets.history', $line->bilyet_id) }}">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($bapsb->isEditable())
                <div class="card card-outline mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Upload signed PDF</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('cashier.bapsb.upload', $bapsb) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <input type="file" name="attachment" accept="application/pdf" class="form-control-file" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Upload PDF</button>
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ route('cashier.bapsb.submit', $bapsb) }}">
                    @csrf
                    <button type="submit" class="btn btn-info" @disabled(! $bapsb->dokumen_id)>Submit for validation</button>
                </form>
            @endif

            @can('validate_bapsb_report')
                @if ($bapsb->submitted_at && $bapsb->validation_status !== 'validated')
                    <div class="card card-outline mt-3">
                        <div class="card-header"><h5 class="card-title mb-0">HO validation</h5></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('cashier.bapsb.validate', $bapsb) }}">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label>Note (optional)</label>
                                    <textarea name="validation_note" class="form-control" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success">Validate BAPSB</button>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection
