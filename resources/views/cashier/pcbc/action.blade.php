<a href="{{ route('cashier.pcbc.print', $model->id) }}" class="btn btn-xs btn-info"><i class="fas fa-print"></i></a>
@if (auth()->user()->id == $model->cashier_id)
<a href="{{ route('cashier.pcbc.edit', $model->id) }}" class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
<form action="{{ route('cashier.pcbc.destroy', $model->id) }}" method="post" class="d-inline">
    @csrf
    @method('delete')
    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure? This action cannot be undone')">
        <i class="fa fa-trash"></i>
    </button>
</form>
@endif
