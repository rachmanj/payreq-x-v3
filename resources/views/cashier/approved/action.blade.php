<form action="{{ route('cashier.approveds.auto_outgoing', $model->id) }}" method="POST">
  @csrf @method('PUT')
  @if ($model->outgoings->count() == 0)
    @php
      if ($model->payment_method === 'transfer' && $model->transferAccount) {
          $autoConfirm = "return confirm('Transfer ke: {$model->transferAccount->displayLabel} - Rp " . number_format($model->amount, 0) . "? Lanjutkan bayar?')";
      } elseif ($model->payment_method === 'transfer') {
          $autoConfirm = "return confirm('Transfer (akun tidak ditemukan) - Rp " . number_format($model->amount, 0) . "? Lanjutkan bayar?')";
      } else {
          $autoConfirm = "return confirm('Bayar payreq ini?')";
      }
    @endphp
    <button type="submit" class="btn btn-xs btn-success" onclick="{{ $autoConfirm }}">auto</button>
  @endif
  <a href="{{ route('cashier.approveds.pay', $model->id) }}" class="btn btn-xs btn-info">pay</a>
</form>
