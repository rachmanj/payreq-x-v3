<div class="modal fade" id="generalOpPreviewModal" tabindex="-1" role="dialog" aria-labelledby="generalOpPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generalOpPreviewModalLabel"><i class="fas fa-eye"></i> Preview OP Umum</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Ringkasan</h6>
                        <table class="table table-sm table-bordered mb-0">
                            <tr>
                                <th style="width: 40%">Bank / Giro</th>
                                <td id="preview-bank-giro">-</td>
                            </tr>
                            <tr>
                                <th>Bilyet</th>
                                <td id="preview-bilyet">-</td>
                            </tr>
                            <tr>
                                <th>Tanggal OP</th>
                                <td id="preview-doc-date">-</td>
                            </tr>
                            <tr>
                                <th>Total OP</th>
                                <td id="preview-total" class="font-weight-bold">-</td>
                            </tr>
                            <tr>
                                <th>Remarks</th>
                                <td id="preview-remarks">-</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Dampak Saldo Lokal</h6>
                        <div class="alert alert-info py-2 mb-2" id="preview-local-note">
                            Saldo kas akan bertambah, akun advance berkurang sesuai nominal tiap akun tujuan.
                        </div>
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Akun Tujuan</th>
                                    <th class="text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody id="preview-local-accounts"></tbody>
                        </table>
                    </div>
                </div>

                <h6 class="text-muted mb-2">Payload SAP (ringkas)</h6>
                <pre id="preview-sap-payload" class="bg-light p-3 rounded small mb-0" style="max-height: 280px; overflow: auto;"></pre>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="vj-btn vj-btn-primary" id="btn-confirm-submit">
                    <i class="fas fa-paper-plane"></i> Submit ke SAP
                </button>
            </div>
        </div>
    </div>
</div>
