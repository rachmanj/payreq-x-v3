@if ($model->sap_journal_no !== null)
<a href="{{ route('cash-journals.print', $model->id) }}" class="btn btn-xs btn-info"  data-toggle="tooltip" data-placement="top" title="Print" target="_blank"><i class="fas fa-print"></i></a>
@endif
<a href="{{ route('cash-journals.show', $model->id) }}" class="btn btn-xs btn-success"  data-toggle="tooltip" data-placement="top" title="View"><i class="fas fa-search"></i></a>