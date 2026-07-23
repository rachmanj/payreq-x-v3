@extends('templates.main')

@section('title_page')
    JE Templates
@endsection

@section('breadcrumb_title')
    accounting / journal-entries / templates
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Journal Entry Templates</h3>
                    <div class="float-right">
                        <a href="{{ route('accounting.journal-entries.index') }}" class="btn btn-sm btn-secondary mr-2">
                            <i class="fas fa-arrow-left"></i> Journal Entries
                        </a>
                        <a href="{{ route('accounting.journal-entries.templates.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> New Template
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Lines</th>
                                <th>Created By</th>
                                <th>Updated</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($templates as $template)
                                <tr>
                                    <td><strong>{{ $template->name }}</strong></td>
                                    <td>{{ $template->description ?? '—' }}</td>
                                    <td>{{ $template->lines_count }}</td>
                                    <td>{{ $template->createdBy?->name }}</td>
                                    <td>{{ $template->updated_at->format('d-M-Y') }}</td>
                                    <td>
                                        <a href="{{ route('accounting.journal-entries.templates.edit', $template->id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('accounting.journal-entries.templates.destroy', $template->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Delete template {{ $template->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No templates yet. Create one to reuse recurring journal entry layouts.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
