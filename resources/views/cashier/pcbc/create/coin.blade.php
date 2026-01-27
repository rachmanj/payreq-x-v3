<!-- Coin Money Section -->
<div class="card card-outline card-secondary mt-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-coins"></i> Coin Money (Uang Logam)</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool btn-sm" onclick="clearSection('logam')" title="Clear All">
                <i class="fas fa-times"></i> Clear All
            </button>
        </div>
    </div>
    <div class="card-body">
<div class="row">
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">1,000</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="logam_1rb" name="logam_1rb" value="0"
                    min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="logam_1rb_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">500</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="logam_500" name="logam_500" value="0"
                    min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="logam_500_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">200</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="logam_200" name="logam_200" value="0"
                    min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="logam_200_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">100</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="logam_100" name="logam_100" value="0"
                    min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="logam_100_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">50</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="logam_50" name="logam_50" value="0"
                    min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="logam_50_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">25</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="logam_25" name="logam_25" value="0"
                    min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="logam_25_result" readonly>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <div class="row">
            <div class="col-md-12 text-right">
                <strong>Coin Money Subtotal: <span id="logam-subtotal" class="text-secondary">Rp 0</span></strong>
            </div>
        </div>
    </div>
</div>
