<div class="vj-inline-actions">
  @if ($model->outgoings->count() == 0)
    @php
      if ($model->payment_method === 'transfer' && $model->transferAccount) {
          $autoTitle = 'Konfirmasi Transfer';
          $autoText = 'Transfer ke: '.$model->transferAccount->displayLabel.' - Rp '.number_format($model->amount, 0).'? Lanjutkan bayar?';
      } elseif ($model->payment_method === 'transfer') {
          $autoTitle = 'Konfirmasi Transfer';
          $autoText = 'Transfer (akun tidak ditemukan) - Rp '.number_format($model->amount, 0).'? Lanjutkan bayar?';
      } else {
          $autoTitle = 'Konfirmasi Pembayaran';
          $autoText = 'Bayar payreq ini?';
      }
    @endphp
    <form action="{{ route('cashier.approveds.auto_outgoing', $model->id) }}" method="POST" class="vj-action-item-form">
      @csrf @method('PUT')
      <button type="submit" class="vj-action-item vj-action-item-xs vj-action-success js-auto-pay"
        data-title="{{ $autoTitle }}" data-text="{{ $autoText }}">auto</button>
    </form>
  @endif
  <a href="{{ route('cashier.approveds.pay', $model->id) }}" class="vj-action-item vj-action-item-xs vj-action-export"><i class="fas fa-money-bill-wave"></i> pay</a>
</div>
