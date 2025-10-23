@hasanyrole('superadmin|admin|cashier')
    <div class="btn-group btn-group-sm" role="group">
        <a class="btn btn-xs btn-warning" href="{{ route('accounting.loans.edit', $model->id) }}" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
        <a class="btn btn-xs btn-success" href="{{ route('accounting.loans.show', $model->id) }}" title="View Installments">
            <i class="fas fa-list"></i>
        </a>
        <a class="btn btn-xs btn-info" href="{{ route('accounting.loans.history', $model->id) }}" title="View History">
            <i class="fas fa-history"></i>
        </a>
        @if ($model->installments->count() == 0)
            <form action="{{ route('accounting.loans.destroy', $model->id) }}" method="POST" style="display: inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-xs btn-danger"
                    onclick="return confirm('Are You sure You want to delete this record?');" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        @endif
    </div>
@endhasanyrole
