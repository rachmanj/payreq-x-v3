<h5 class="mt-4">Paper Money</h5>
<div class="row">
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">100,000</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="kertas_100rb" name="kertas_100rb"
                    value="{{ old('kertas_100rb', $pcbc->kertas_100rb) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="kertas_100rb_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">50,000</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="kertas_50rb" name="kertas_50rb"
                    value="{{ old('kertas_50rb', $pcbc->kertas_50rb) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="kertas_50rb_result" readonly>
            </div>
        </div>
    </div>
    <!-- Continue with other paper money denominations -->
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">20,000</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="kertas_20rb" name="kertas_20rb"
                    value="{{ old('kertas_20rb', $pcbc->kertas_20rb) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="kertas_20rb_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">10,000</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="kertas_10rb" name="kertas_10rb"
                    value="{{ old('kertas_10rb', $pcbc->kertas_10rb) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="kertas_10rb_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">5,000</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="kertas_5rb" name="kertas_5rb"
                    value="{{ old('kertas_5rb', $pcbc->kertas_5rb) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="kertas_5rb_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">2,000</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="kertas_2rb" name="kertas_2rb"
                    value="{{ old('kertas_2rb', $pcbc->kertas_2rb) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="kertas_2rb_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">1,000</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="kertas_1rb" name="kertas_1rb"
                    value="{{ old('kertas_1rb', $pcbc->kertas_1rb) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="kertas_1rb_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">500</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="kertas_500" name="kertas_500"
                    value="{{ old('kertas_500', $pcbc->kertas_500) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="kertas_500_result" readonly>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group row">
            <label class="col-sm-4 col-form-label">100</label>
            <div class="col-sm-4">
                <input type="number" class="form-control money-input" id="kertas_100" name="kertas_100"
                    value="{{ old('kertas_100', $pcbc->kertas_100) }}" min="0">
            </div>
            <div class="col-sm-4">
                <input type="text" class="form-control money-result text-right" id="kertas_100_result" readonly>
            </div>
        </div>
    </div>
</div>
