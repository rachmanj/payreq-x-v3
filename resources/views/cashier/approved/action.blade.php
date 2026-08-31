<div class="vj-inline-actions">
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
    <form action="{{ route('cashier.approveds.auto_outgoing', $model->id) }}" method="POST" class="vj-action-item-form">
      @csrf @method('PUT')
      <button type="submit" class="vj-action-item vj-action-item-xs vj-action-success" onclick="{{ $autoConfirm }}">auto</button>
    </form>
  @endif
  <a href="{{ route('cashier.approveds.pay', $model->id) }}" class="vj-action-item vj-action-item-xs vj-action-export"><i class="fas fa-money-bill-wave"></i> pay</a>
</div>
