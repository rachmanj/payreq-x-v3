<div class="modal fade" id="bulk-scan-modal" tabindex="-1" role="dialog" aria-labelledby="bulkScanModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="bulkScanModalLabel">
                    <i class="fas fa-camera"></i> Scan Fuel Receipts
                    <small class="d-block text-muted font-weight-normal mt-1" style="font-size: 0.85rem;">Hanya Nota Pembelian Fuel</small>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-row align-items-end mb-3">
                    <div class="col-md-8">
                        <label for="bulk-receipt-files">Select receipt images</label>
                        <input type="file" id="bulk-receipt-files" class="form-control-file" accept="image/*"
                            multiple>
                    </div>
                    <div class="col-md-4">
                        <button type="button" id="btn-bulk-scan-all" class="btn btn-info btn-block" disabled>
                            <i class="fas fa-search"></i> Scan All
                        </button>
                    </div>
                </div>
                <div id="bulk-scan-progress-wrap" class="mb-3" style="display: none;">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Scanning receipts...</span>
                        <span id="bulk-scan-progress-text">0 / 0</span>
                    </div>
                    <div class="progress">
                        <div id="bulk-scan-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                            role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="bulk-scan-review-table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 70px;">Photo</th>
                                <th>Description</th>
                                <th style="width: 110px;">Amount</th>
                                <th style="width: 120px;">Date</th>
                                <th style="width: 90px;">HM</th>
                                <th style="width: 90px;">Unit</th>
                                <th style="width: 90px;">Nopol</th>
                                <th style="width: 70px;">Qty</th>
                                @if ($realization->payreq->isAdvanceMultiBudget())
                                    <th style="width: 120px;">Anggaran</th>
                                @endif
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="bulk-scan-review-body">
                            <tr id="bulk-scan-empty-row">
                                <td colspan="{{ $realization->payreq->isAdvanceMultiBudget() ? 10 : 9 }}"
                                    class="text-center text-muted">
                                    Select images and click Scan All
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" id="btn-bulk-save-all" class="btn btn-success" disabled>
                    <i class="fas fa-save"></i> Save All
                </button>
            </div>
        </div>
    </div>
</div>

