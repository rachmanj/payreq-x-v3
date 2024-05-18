<form action="{{ route('cashier.approveds.auto_outgoing', $model->id) }}" method="POST">
  @csrf @method('PUT')
  @if ($model->outgoings->count() == 0)
    <button type="submit" class="btn btn-xs btn-success" onclick="return confirm('Are You sure You want to pay this payreq?')">auto</button>
  @endif
  <a href="{{ route('cashier.approveds.pay', $model->id) }}" class="btn btn-xs btn-info">pay</a>
</form>


