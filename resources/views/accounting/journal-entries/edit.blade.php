@extends('templates.main')

@section('title_page')
    Edit Journal Entry
@endsection

@section('breadcrumb_title')
    accounting / journal-entries / edit
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Journal Entry — {{ $journalEntry->number }}</h3>
                    <a href="{{ route('accounting.journal-entries.show', $journalEntry->id) }}" class="btn btn-sm btn-secondary float-right">
                        <i class="fas fa-arrow-left"></i> Back
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

                        <h5 class="mt-4">Journal Lines</h5>
                        @php
                            $editLines = old('lines', $journalEntry->lines->map(fn ($l) => [
                                'account_code' => $l->account_code,
                                'debit_credit' => $l->debit_credit,
                                'amount' => $l->amount,
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
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Journal Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@stack('styles')
