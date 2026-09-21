@extends('templates.main')

@section('title_page')
    {{ $template ? 'Edit Template' : 'New Template' }}
@endsection

@section('breadcrumb_title')
    accounting / journal-entries / templates / {{ $template ? 'edit' : 'create' }}
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
                            <i class="fas fa-{{ $template ? 'edit' : 'plus-circle' }}"></i>
                            {{ $template ? 'Edit Template: '.$template->name : 'New Journal Entry Template' }}
                        </h3>
                        <a href="{{ route('accounting.journal-entries.templates.index') }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back</span>
                        </a>
                    </div>
                    <form id="je-form" action="{{ $template ? route('accounting.journal-entries.templates.update', $template->id) : route('accounting.journal-entries.templates.store') }}" method="POST">
                        @csrf
                        @if ($template)
                            @method('PUT')
                        @endif
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
                                <div class="col-md-6">
                                    <label for="name">Template Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        value="{{ old('name', $template?->name) }}" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="2">{{ old('description', $template?->description) }}</textarea>
                            </div>

                            <h5 class="mt-4 mb-2"><i class="fas fa-list text-muted"></i> Template Lines</h5>
                            @php
                                $templateLines = old('lines', $template
                                    ? $template->lines->map(fn ($l) => [
                                        'account_code' => $l->account_code,
                                        'debit_credit' => $l->debit_credit,
                                        'default_amount' => $l->default_amount,
                                        'project' => $l->project,
                                        'cost_center' => $l->cost_center,
                                        'description' => $l->description,
                                    ])->toArray()
                                    : [
                                        ['account_code' => '', 'debit_credit' => 'debit', 'default_amount' => '', 'project' => '', 'cost_center' => '', 'description' => ''],
                                        ['account_code' => '', 'debit_credit' => 'credit', 'default_amount' => '', 'project' => '', 'cost_center' => '', 'description' => ''],
                                    ]);
                            @endphp
                            @include('accounting.journal-entries.partials.line-grid', [
                                'initialLines' => $templateLines,
                                'projects' => $projects,
                                'departments' => $departments,
                                'amountField' => 'default_amount',
                            ])
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="vj-btn vj-btn-primary">
                                <i class="fas fa-save"></i> {{ $template ? 'Update Template' : 'Save Template' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
