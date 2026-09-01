<div class="vj-inline-actions">
    @if ($model->payreq_id || $model->utility_ap_invoice_id)
        <span class="vj-chip vj-chip-neutral" title="Sudah masuk payreq atau AP Invoice SAP, terkunci">
            <i class="fas fa-lock"></i> Terkunci
        </span>
    @else
        @if (! $model->tanggal_bayar)
            <a href="{{ route('utilities.bills.edit', $model->id) }}"
                class="vj-action-item vj-action-item-xs vj-action-edit" title="Edit Tagihan">
                <i class="fas fa-edit"></i><span>edit</span>
            </a>
            <button type="button" class="vj-action-item vj-action-item-xs vj-action-success btn-mark-paid" data-toggle="modal"
                data-target="#modal-mark-paid-{{ $model->id }}" title="Tandai Lunas">
                <i class="fas fa-check"></i><span>lunas</span>
            </button>
        @else
            <form action="{{ route('utilities.bills.unmark-paid', $model->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="vj-action-item vj-action-item-xs vj-action-cancel" title="Batalkan Lunas"
                    onclick="return confirm('Batalkan status lunas tagihan ini?')">
                    <i class="fas fa-undo"></i><span>batal</span>
                </button>
            </form>
        @endif
    @endif
</div>

@if (! $model->tanggal_bayar && ! $model->payreq_id)
    <div class="modal fade" id="modal-mark-paid-{{ $model->id }}">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tandai Lunas</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('utilities.bills.mark-paid', $model->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small mb-2">
                            {{ $model->customer->nama ?? '-' }} — {{ $model->periode }}
                        </p>
                        <div class="form-group">
                            <label for="tanggal_bayar_{{ $model->id }}">Tanggal Bayar</label>
                            <input type="date" name="tanggal_bayar" id="tanggal_bayar_{{ $model->id }}"
                                class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="form-group mb-0">
                            <label for="nomor_tagihan_{{ $model->id }}">Nomor Referensi</label>
                            <input type="text" name="nomor_tagihan" id="nomor_tagihan_{{ $model->id }}"
                                class="form-control form-control-sm" value="{{ $model->nomor_tagihan }}"
                                placeholder="Opsional">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="vj-action-item" data-dismiss="modal">Batal</button>
                        <button type="submit" class="vj-btn vj-btn-success"><i class="fas fa-check"></i> Lunas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
