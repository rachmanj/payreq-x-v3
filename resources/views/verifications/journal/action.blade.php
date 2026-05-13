
{{-- if sap_journal_no not empty --}}
@if (filled($model->sap_journal_no))
<a href="{{ route('verifications.journal.print', $model->id) }}" class="btn btn-xs btn-info" data-toggle="tooltip" data-placement="top" title="Print" target="_blank"><i class="fas fa-print"></i> VJ</a>
@endif

<a href="{{ route('verifications.journal.show', $model->id) }}" class="btn btn-xs btn-success" data-toggle="tooltip" data-placement="top" title="View"><i class="fas fa-eye"></i></a>
@if ($model->sap_journal_no === null)
    {{-- delete button --}}
    <form action="{{ route('verifications.journal.destroy', $model->id) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button class="btn btn-xs btn-danger" onclick="return confirm('Are You sure You want to delete this data? This action cannot be undone')" data-toggle="tooltip" data-placement="top" title="Delete"><i class="fas fa-trash"></i></button>
    </form>
@endif

@php
    $sapPdfName = trim((string) ($model->sap_filename ?? ''));
    $sapPdfAvailable = $sapPdfName !== '' && is_file(public_path('file_upload/'.$sapPdfName));
@endphp
@if (filled($model->sap_journal_no))
    @if ($sapPdfAvailable)
        <a href="{{ asset('file_upload/'.$sapPdfName) }}" class="btn btn-xs btn-outline-info ml-1" target="_blank" title="SAP journal PDF upload"><i class="fas fa-file-pdf"></i> SAPJ</a>
    @else
        <a href="{{ route('verifications.journal.print_sap_journal', $model->id) }}" class="btn btn-xs btn-outline-secondary ml-1" target="_blank" title="Print SAP Journal (template)"><i class="fas fa-print"></i> SAPJ</a>
    @endif
@endif
