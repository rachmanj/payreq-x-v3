@extends('templates.main')

@section('title_page')
    Edit Journal Entry
@endsection

@section('breadcrumb_title')
    accounting / journal-entries / edit
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-edit"></i> Edit Journal Entry — {{ $journalEntry->number }}
                        </h3>
                        <a href="{{ route('accounting.journal-entries.show', $journalEntry->id) }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back</span>
                        </a>
                    </div>
                    <form id="je-form" action="{{ route('accounting.journal-entries.update', $journalEntry->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="date">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date" id="date" class="form-control"
                                        value="{{ old('date', $journalEntry->date?->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="reference">Reference</label>
                                    <input type="text" name="reference" id="reference" class="form-control"
                                        value="{{ old('reference', $journalEntry->reference) }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="memo">Memo</label>
                                <textarea name="memo" id="memo" class="form-control" rows="2">{{ old('memo', $journalEntry->memo) }}</textarea>
                            </div>

                            <input type="hidden" name="journal_entry_template_id" value="{{ old('journal_entry_template_id', $journalEntry->journal_entry_template_id) }}">

                            <h5 class="mt-4 mb-2"><i class="fas fa-list text-muted"></i> Journal Lines</h5>
                            @php
                                $editLines = old('lines', $journalEntry->lines->map(fn ($l) => [
                                    'account_code' => $l->account_code,
                                    'debit_credit' => $l->debit_credit,
                                    'currency' => $l->currency ?? 'IDR',
                                    'amount' => $l->amount,
                                    'fc_amount' => $l->fc_amount,
                                    'exchange_rate' => $l->exchange_rate,
                                    'project' => $l->project,
                                    'cost_center' => $l->cost_center,
                                    'description' => $l->description,
                                ])->toArray());
                            @endphp
                            @include('accounting.journal-entries.partials.line-grid', [
                                'initialLines' => $editLines,
                                'projects' => $projects,
                                'departments' => $departments,
                                'amountField' => 'amount',
                            ])
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="vj-btn vj-btn-primary">
                                <i class="fas fa-save"></i> Update Journal Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('partials.vj-soft-ui-swal')
@endpush
