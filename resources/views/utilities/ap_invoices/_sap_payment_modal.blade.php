<div class="modal fade" id="utilitySapPaymentModal" tabindex="-1" role="dialog" aria-labelledby="utilitySapPaymentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="utilitySapPaymentModalLabel">
                    <i class="fas fa-money-bill-wave text-primary"></i> Buat OP — Outgoing Payment SAP
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="utilitySapPaymentForm">
                <div class="modal-body">
                    <div id="utilitySapPaymentError" class="vj-alert vj-alert-danger d-none mb-3"></div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="small text-muted d-block">Vendor Ref. No.</label>
                            <strong id="utility_sap_num_at_card">-</strong>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted d-block">SAP AP DocNum</label>
                            <strong id="utility_sap_ap_doc_num">-</strong>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="utility_payment_means">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-control" id="utility_payment_means" name="payment_means" required>
                                    <option value="transfer">Bank Transfer</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="utility_account_id">Akun Kas/Bank <span class="text-danger">*</span></label>
                                <select class="form-control select2bs4" id="utility_account_id" name="account_id" required>
                                    <option value="">Pilih akun...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="utility_payment_date">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="utility_payment_date" name="payment_date" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="utility_payment_amount">Jumlah <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="utility_payment_amount" name="payment_amount"
                                        min="1" step="1" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="utility_fill_remaining_btn"
                                            title="Isikan sisa">
                                            Sisa
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Sisa: <span id="utility_remaining_display">-</span></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="utility_payment_remarks">Keterangan</label>
                                <input type="text" class="form-control" id="utility_payment_remarks" name="remarks"
                                    maxlength="500" placeholder="Opsional">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="utility_prepared_by">Prepared By <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="utility_prepared_by" name="prepared_by"
                                    maxlength="100" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="utility_approved_by">Approved By <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="utility_approved_by" name="approved_by"
                                    maxlength="100" required>
                            </div>
                        </div>
                    </div>

                    <div id="utility_sap_payment_preview_panel" class="vj-form-panel d-none mt-3">
                        <h6 class="mb-2"><i class="fas fa-search"></i> Ringkasan Preview</h6>
                        <div class="row small">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Vendor:</strong> <span id="utility_preview_vendor">-</span></p>
                                <p class="mb-1"><strong>AP DocNum:</strong> <span id="utility_preview_ap_doc">-</span></p>
                                <p class="mb-1"><strong>Total AP:</strong> <span id="utility_preview_doc_total">-</span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Sudah dibayar:</strong> <span id="utility_preview_paid_to_date">-</span></p>
                                <p class="mb-1"><strong>Sisa:</strong> <span id="utility_preview_remaining">-</span></p>
                                <p class="mb-1"><strong>Akan dibayar:</strong> <span id="utility_preview_payment_amount">-</span></p>
                                <p class="mb-0"><strong>Metode / Akun:</strong> <span id="utility_preview_means_account">-</span></p>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="utility_sap_invoice_id">
                    <input type="hidden" id="utility_sap_remaining_value" value="0">
                </div>
                <div class="modal-footer">
                    <button type="button" class="vj-btn vj-btn-warning" data-dismiss="modal">Batal</button>
                    <button type="button" class="vj-btn vj-btn-secondary" id="utilitySapPaymentPreviewBtn">
                        <i class="fas fa-search"></i> Preview
                    </button>
                    <button type="button" class="vj-btn vj-btn-primary d-none" id="utilitySapPaymentSubmitBtn" disabled>
                        <i class="fas fa-paper-plane"></i> Submit OP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
