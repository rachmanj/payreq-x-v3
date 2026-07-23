@extends('templates.main')

@section('title_page')
    New Journal Entry
@endsection

@section('breadcrumb_title')
    accounting / journal-entries / create
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Manual Journal Entry</h3>
                    <a href="{{ route('accounting.journal-entries.index') }}" class="btn btn-sm btn-secondary float-right">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <form id="je-form" action="{{ route('accounting.journal-entries.store') }}" method="POST">
                    @csrf
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
                                    value="{{ old('date', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="reference">Reference</label>
                                <input type="text" name="reference" id="reference" class="form-control"
                                    value="{{ old('reference') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="template_select">Load from Template</label>
                                <select id="template_select" class="form-control">
                                    <option value="">— Select template —</option>
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="memo">Memo</label>
                            <textarea name="memo" id="memo" class="form-control" rows="2">{{ old('memo') }}</textarea>
                        </div>

                        <input type="hidden" name="journal_entry_template_id" id="journal_entry_template_id"
                            value="{{ old('journal_entry_template_id') }}">

                        <h5 class="mt-4">Journal Lines</h5>
                        @php
                            $oldLines = old('lines', [
                                ['account_code' => '', 'debit_credit' => 'debit', 'amount' => '', 'project' => '', 'cost_center' => '', 'description' => ''],
                                ['account_code' => '', 'debit_credit' => 'credit', 'amount' => '', 'project' => '', 'cost_center' => '', 'description' => ''],
                            ]);
                        @endphp
                        @include('accounting.journal-entries.partials.line-grid', [
                            'initialLines' => $oldLines,
                            'projects' => $projects,
                            'departments' => $departments,
                            'amountField' => 'amount',
                        ])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Journal Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $('#template_select').on('change', function() {
            const templateId = $(this).val();
            if (!templateId) return;

            $.getJSON('{{ url('accounting/journal-entries/templates') }}/' + templateId + '/lines', function(data) {
                $('#journal_entry_template_id').val(data.id);
                $('#je-lines-body').empty();
                data.lines.forEach(function(line, index) {
                    $('#add-line-btn').trigger('click');
                    const $row = $('#je-lines-body tr').last();
                    $row.find('[name$="[account_code]"]').val(line.account_code);
                    $row.find('.line-debit-credit').val(line.debit_credit);
                    $row.find('.line-amount').val(line.default_amount || '');
                    $row.find('[name$="[project]"]').val(line.project || '');
                    $row.find('[name$="[cost_center]"]').val(line.cost_center || '');
                    $row.find('[name$="[description]"]').val(line.description || '');
                });
                jeRenumberRows();
                jeRecalcTotals();
            });
        });
    </script>
@endsection

@stack('styles')
