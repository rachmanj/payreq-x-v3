@hasanyrole('superadmin|admin|cashier')
    <div class="vj-inline-actions">
        <a class="vj-action-item vj-action-item-xs vj-action-edit" href="{{ route('accounting.loans.edit', $model->id) }}" title="Edit Loan">
            <i class="fas fa-edit"></i>
        </a>
        <a class="vj-action-item vj-action-item-xs vj-action-export" href="{{ route('accounting.loans.show', $model->id) }}" title="View Installments">
            <i class="fas fa-list"></i>
        </a>
        <a class="vj-action-item vj-action-item-xs vj-action-print" href="{{ route('accounting.loans.history', $model->id) }}" title="View History">
            <i class="fas fa-history"></i>
        </a>
        @if ($model->installments->count() == 0)
            <form action="{{ route('accounting.loans.destroy', $model->id) }}" method="POST" class="vj-action-item-form">
                @csrf @method('DELETE')
                <button type="submit" class="vj-action-item vj-action-item-xs vj-action-cancel"
                    onclick="return confirm('Are You sure You want to delete this record?');" title="Delete Loan">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        @endif
    </div>
@endhasanyrole
