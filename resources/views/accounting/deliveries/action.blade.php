<a href="{{ route('accounting.deliveries.show', $model->id) }}" class="btn btn-xs btn-primary" title="View Delivery">
    <i class="fas fa-eye"></i>
</a>

@if ($model->status === 'pending')
    <button class="btn btn-xs btn-warning"
        onclick="event.preventDefault(); if(confirm('Are you sure to send this?')) { document.getElementById('send-form-{{ $model->id }}').submit(); }"
        title="Send Delivery">
        <i class="fas fa-paper-plane"></i>
    </button>
    <form id="send-form-{{ $model->id }}" action="{{ route('accounting.deliveries.send', $model->id) }}" method="POST"
        style="display: none;">
        @csrf
    </form>

    <a href="{{ route('accounting.deliveries.edit', $model->id) }}" class="btn btn-xs btn-warning"
        title="Edit Delivery">
        <i class="fas fa-edit"></i>
    </a>
    <!-- Start Generation Here -->
    <button class="btn btn-xs btn-danger"
        onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this delivery?')) { document.getElementById('delete-form-{{ $model->id }}').submit(); }"
        title="Delete Delivery">
        <i class="fas fa-trash"></i>
    </button>
    <form id="delete-form-{{ $model->id }}" action="{{ route('accounting.deliveries.destroy', $model->id) }}"
        method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endif

<a href="{{ route('accounting.deliveries.print', $model->id) }}" class="btn btn-xs btn-success" target="_blank"
    title="Print Delivery">
    <i class="fas fa-print"></i>
</a>
