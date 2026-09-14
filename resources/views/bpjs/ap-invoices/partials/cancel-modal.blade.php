<div class="modal fade" id="cancelBpjsModal" tabindex="-1" role="dialog" aria-labelledby="cancelBpjsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="#" id="cancelBpjsForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelBpjsModalLabel">
                        <i class="fas fa-ban"></i> Batalkan AP Invoice BPJS
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="cancelBpjsInvoiceInfo"></p>
                    <div class="form-group mb-0">
                        <label for="cancel_reason">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <textarea name="cancel_reason" id="cancel_reason" class="form-control" rows="3" minlength="5"
                            required placeholder="Minimal 5 karakter"></textarea>
                        <small class="form-text text-muted">Alasan wajib diisi, minimal 5 karakter.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="vj-btn vj-btn-warning" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="vj-btn vj-btn-primary" id="cancelBpjsSubmitBtn">
                        <i class="fas fa-ban"></i> Batalkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
