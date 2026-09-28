<a href="{{ route('cashier.bapsb.show', $model->id) }}" class="btn btn-xs btn-info" title="View">View</a>
@if (! $model->submitted_at)
    <a href="{{ route('cashier.bapsb.edit', $model->id) }}" class="btn btn-xs btn-warning" title="Edit">Edit</a>
@endif
<a href="{{ route('cashier.bapsb.print', $model->id) }}" class="btn btn-xs btn-success" title="Print" target="_blank">Print</a>
