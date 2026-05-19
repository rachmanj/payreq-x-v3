@php
    $rawAmount = old('amount', $amountDefault ?? '0');
    $numericAmount = is_numeric($rawAmount) ? (float) $rawAmount : 0.0;
    $displayAmount = number_format($numericAmount, 2, '.', ',');
@endphp
<div class="form-group">
    <label for="amount_display">Amount <small class="text-muted">(auto from lines)</small></label>
    <input type="hidden" name="amount" id="amount" value="{{ $numericAmount }}">
    <input type="text" id="amount_display" readonly
        class="form-control bg-light text-right @error('amount') is-invalid @enderror"
        value="{{ $displayAmount }}" autocomplete="off">
    @error('amount')
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror
</div>
