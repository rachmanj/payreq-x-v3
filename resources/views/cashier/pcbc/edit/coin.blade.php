<h5 class="mt-4">Coin Money</h5>
<div class="row">
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">1,000</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="logam_1rb" name="logam_1rb"
                    value="{{ old('logam_1rb', $pcbc->logam_1rb) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="logam_1rb_result" readonly>
            </div>
        </div>
    </div>
    <!-- Continue with other coin denominations -->
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">500</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="logam_500" name="logam_500"
                    value="{{ old('logam_500', $pcbc->logam_500) }}" min="0">
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
                <input type="number" class="form-control money-input" id="logam_200" name="logam_200"
                    value="{{ old('logam_200', $pcbc->logam_200) }}" min="0">
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
                <input type="number" class="form-control money-input" id="logam_100" name="logam_100"
                    value="{{ old('logam_100', $pcbc->logam_100) }}" min="0">
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
                <input type="number" class="form-control money-input" id="logam_50" name="logam_50"
                    value="{{ old('logam_50', $pcbc->logam_50) }}" min="0">
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
                <input type="number" class="form-control money-input" id="logam_25" name="logam_25"
                    value="{{ old('logam_25', $pcbc->logam_25) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="logam_25_result" readonly>
            </div>
        </div>
    </div>
</div>
