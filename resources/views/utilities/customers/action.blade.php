<a href="{{ route('utilities.customers.edit', $model->id) }}" class="btn btn-xs btn-warning" title="Edit">
    <i class="fas fa-edit"></i>
</a>
<form action="{{ route('utilities.customers.destroy', $model->id) }}" method="POST" class="d-inline">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-xs btn-danger" title="Hapus"
        onclick="return confirm('Hapus ID Pelanggan ini? Semua tagihan terkait juga akan dihapus.')">
        <i class="fas fa-trash"></i>
    </button>
</form>
