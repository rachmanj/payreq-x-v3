<div class="vj-inline-actions">
    <a href="{{ route('utilities.customers.edit', $model->id) }}" class="vj-action-item vj-action-item-xs vj-action-edit" title="Edit">
        <i class="fas fa-edit"></i><span>edit</span>
    </a>
    <form action="{{ route('utilities.customers.destroy', $model->id) }}" method="POST" class="d-inline">
        @csrf @method('DELETE')
        <button type="submit" class="vj-action-item vj-action-item-xs vj-action-cancel" title="Hapus"
            onclick="return confirm('Hapus ID Pelanggan ini? Semua tagihan terkait juga akan dihapus.')">
            <i class="fas fa-trash"></i><span>hapus</span>
        </button>
    </form>
</div>
