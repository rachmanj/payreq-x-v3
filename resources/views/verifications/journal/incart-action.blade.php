<form action="{{ route('verifications.journal.remove_from_cart') }}" method="POST">
    @csrf
    <input type="hidden" name="verification_id" value="{{ $model->id }}">
    <button type="submit" class="btn btn-xs btn-primary" title="move to cart"><i class="fas fa-arrow-up"></i></button>
</form>