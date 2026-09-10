<div class="btn-group" role="group">
    <a href="{{ route('activities.edit', $model->id) }}" class="btn btn-xs btn-warning" title="Edit">
        <i class="fas fa-edit"></i>
    </a>
    @if ($model->status === 'open')
        <form action="{{ route('activities.close', $model->id) }}" method="POST" class="d-inline"
            onsubmit="return confirm('Tutup kegiatan ini? Kegiatan closed tidak bisa dipilih lagi.');">
            @csrf
            <button type="submit" class="btn btn-xs btn-dark" title="Tutup Kegiatan">
                <i class="fas fa-lock"></i>
            </button>
        </form>
    @endif
</div>
