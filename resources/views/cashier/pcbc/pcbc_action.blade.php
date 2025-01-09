@if (auth()->user()->hasRole('superadmin') || auth()->user()->id == $model->created_by)
    <a href="{{ route('cashier.pcbc.edit', $model->id) }}" class="btn btn-xs btn-warning" title="Edit PCBC">
        <i class="fas fa-edit"></i>
    </a>
    <form action="{{ route('cashier.pcbc.destroy_pcbc', $model->id) }}" method="POST" class="d-inline"
        onsubmit="return confirm('Are you sure you want to delete this PCBC?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-xs btn-danger" title="Delete PCBC">
            <i class="fas fa-trash"></i>
        </button>
    </form>
@endif

<a href="{{ route('cashier.pcbc.print', $model->id) }}" class="btn btn-xs btn-success" title="Print PCBC"
    target="_blank">
    <i class="fas fa-file-pdf"></i>
</a>
