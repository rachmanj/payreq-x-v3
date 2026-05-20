<div class="card card-outline card-secondary mb-3 receipt-scan-panel">
    <div class="card-header py-2" data-toggle="collapse" data-target="#receipt-scan-collapse-{{ $scanInputId }}"
        style="cursor: pointer;">
        <h6 class="card-title mb-0">
            <i class="fas fa-magic"></i> Scan Receipt with AI
            <small class="text-muted">(optional)</small>
            <small class="text-muted d-block font-weight-normal mt-1">Hanya untuk Nota Pembelian Fuel</small>
        </h6>
    </div>
    <div id="receipt-scan-collapse-{{ $scanInputId }}" class="collapse show">
        <div class="card-body py-2">
            <div class="form-row align-items-end">
                <div class="col-md-8">
                    <label class="small mb-1" for="{{ $scanInputId }}">Receipt photo</label>
                    <input type="file" id="{{ $scanInputId }}" class="form-control-file" accept="image/*"
                        capture="environment">
                </div>
                <div class="col-md-4">
                    <button type="button" id="{{ $scanBtnId }}" class="btn btn-info btn-block">
                        <i class="fas fa-search"></i> Scan
                    </button>
                </div>
            </div>
            <div id="{{ $scanPreviewId }}" class="mt-2" style="display: none;">
                <img src="" alt="Receipt preview" class="img-thumbnail" style="max-height: 120px;">
            </div>
            <div id="{{ $scanAlertId }}" class="mt-2" style="display: none;"></div>
            <div id="{{ $scanBtnId }}-loading" class="text-muted small mt-2" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i> Reading receipt...
            </div>
        </div>
    </div>
</div>
