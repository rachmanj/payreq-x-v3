<div class="form-row">
    <div class="form-group col-md-6">
        <label>Transaction date</label>
        <input type="date" name="transaction_date" class="form-control form-control-sm">
    </div>
    <div class="form-group col-md-6">
        <label>Value date</label>
        <input type="date" name="value_date" class="form-control form-control-sm">
    </div>
</div>
<div class="form-group">
    <label>Description</label>
    <input type="text" name="description" class="form-control form-control-sm">
</div>
<div class="form-group">
    <label>Reference</label>
    <input type="text" name="reference" class="form-control form-control-sm">
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label>Debit</label>
        <input type="number" step="0.01" min="0" name="debit" class="form-control form-control-sm" value="0" required>
    </div>
    <div class="form-group col-md-4">
        <label>Credit</label>
        <input type="number" step="0.01" min="0" name="credit" class="form-control form-control-sm" value="0" required>
    </div>
    <div class="form-group col-md-4">
        <label>Balance</label>
        <input type="number" step="0.01" name="balance" class="form-control form-control-sm">
    </div>
</div>
<div class="form-group mb-0">
    <label>Notes</label>
    <input type="text" name="line_notes" class="form-control form-control-sm" maxlength="500">
</div>
