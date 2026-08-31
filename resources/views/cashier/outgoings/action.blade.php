{{-- button call modal to update --}}

<div class="vj-inline-actions">
    @if($model->payment_method === 'transfer')
        <button type="button" class="vj-action-item vj-action-item-xs vj-action-primary" data-toggle="modal"
            data-target="#outgoing-attachment-{{ $model->id }}" title="Upload bukti transfer">
            <i class="fas fa-upload"></i>
        </button>
        @foreach ($model->attachments as $attachment)
            @if ((int) $attachment->created_by === (int) auth()->id())
                <form action="{{ route('cashier.outgoing-attachments.destroy', $attachment) }}" method="POST"
                    class="vj-action-item-form js-delete-attachment">
                    @csrf @method('DELETE')
                    <button type="submit" class="vj-action-item vj-action-item-xs vj-action-cancel" title="Hapus bukti transfer">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            @endif
            @if (in_array($attachment->verification_status, ['failed', 'pending'], true))
                <form action="{{ route('cashier.outgoing-attachments.reverify', $attachment) }}" method="POST"
                    class="vj-action-item-form">
                    @csrf
                    <button type="submit" class="vj-action-item vj-action-item-xs vj-action-print" title="Verifikasi ulang">
                        <i class="fas fa-redo"></i>
                    </button>
                </form>
            @endif
        @endforeach
    @endif

    <form action="{{ route('cashier.outgoings.destroy', $model->id) }}" class="vj-action-item-form" method="POST">
        @csrf @method('DELETE')
        @if($model->payreq_id == null && $model->outgoing_date == null)
            <button type="button" class="vj-action-item vj-action-item-xs vj-action-success" data-toggle="modal" data-target="#outgoing-{{ $model->id }}">payment</button>
            <button type="submit" class="vj-action-item vj-action-item-xs vj-action-cancel" onclick="return confirm('Are you sure you want delete this record?')">delete</button>
        @endif
    </form>
</div>

{{-- modal upload bukti transfer --}}
@if($model->payment_method === 'transfer')
    <div class="modal fade" id="outgoing-attachment-{{ $model->id }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fas fa-upload mr-1"></i> Upload Bukti Transfer</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form action="{{ route('cashier.outgoing-attachments.store', $model) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Outgoing #{{ $model->nomor }} — Rp {{ number_format($model->amount, 0) }}
                        </p>
                        <div class="form-group">
                            <label>File bukti (jpg/png/pdf, maks 5MB)</label>
                            <input type="file" name="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>
                        <p class="text-muted small mb-0">Upload otomatis diverifikasi AI (bandingkan bank, no. rekening, atas nama, nominal).</p>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="vj-btn vj-btn-primary py-1 px-2"><i class="fas fa-upload"></i> Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- modal receive --}}
<div class="modal fade" id="outgoing-{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Payment Detail</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="{{ route('cashier.outgoings.payment') }}" method="POST">
                @csrf

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="hidden" name="incoming_id" value="{{ $model->id }}">
                                <input type="text" name="amount" id="amount" class="form-control" value="IDR {{ number_format($model->amount, 2) }}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="receive_date">Payment Date</label>
                                <input type="date" name="receive_date" id="receive_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
