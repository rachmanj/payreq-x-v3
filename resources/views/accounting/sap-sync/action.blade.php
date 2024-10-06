<a href="{{ route('accounting.sap-sync.show', $model->id) }}" class="btn btn-xs btn-success" title="Show"><i class="fas fa-eye"></i></a>
<a href="{{ route('accounting.sap-sync.export', ['vj_id' => $model->id]) }}" class="btn btn-xs btn-warning" title="export to excel"><i class="fas fa-file-export"></i></a>
<a href="{{ route('verifications.journal.print', $model->id) }}" class="btn btn-xs btn-info" target="_blank" title="print verification journal"><i class="fas fa-print"></i> VJ</a>
@if($model->sap_filename)
    <a href="{{ asset('file_upload/') . '/'. $model->sap_filename }}" class="btn btn-xs btn-info" target="_blank" title="print SAP Journal"><i class="fas fa-print"></i> SAPJ</a>
@endif
