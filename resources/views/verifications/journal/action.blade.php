
{{-- if sap_journal_no not empty --}}
@if ($model->sap_journal_no)
<a href="{{ route('verifications.journal.print', $model->id) }}" class="btn btn-xs btn-info"  data-toggle="tooltip" data-placement="top" title="Print" target="_blank"><i class="fas fa-print"></i> VJ</a>
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

@if($model->sap_filename)
    <a href="{{ asset('file_upload/') . '/'. $model->sap_filename }}" class="btn btn-xs btn-info" target="_blank" title="print SAP Journal"><i class="fas fa-print"></i> SAPJ</a>
@endif