<a href="{{ route('accounting.sap-sync.show', $model->id) }}" class="btn btn-xs btn-success" title="Show"><i class="fas fa-eye"></i></a>
<a href="{{ route('accounting.sap-sync.export', ['vj_id' => $model->id]) }}" class="btn btn-xs btn-warning" title="export to excel"><i class="fas fa-file-export"></i></a>
<a href="{{ route('verifications.journal.print', $model->id) }}" class="btn btn-xs btn-info" target="_blank" title="print verification journal"><i class="fas fa-print"></i> VJ</a>
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
