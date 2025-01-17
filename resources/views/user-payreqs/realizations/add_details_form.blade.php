<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h4 class="card-title">Form</h4>
                <a href="{{ route('user-payreqs.realizations.index') }}" class="btn btn-sm btn-info float-right"><i
                        class="fas fa-arrow-left"></i> Back</a>
                <form action="{{ route('user-payreqs.realizations.submit_realization') }}" method="POST">
                    @csrf
                    @if ($realization_details->count() > 0)
                        <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                        <button type="submit" class="btn btn-sm btn-warning float-right mx-2"
                            onclick="return confirm('Are you sure you want to submit this realization?')">Submit
                            Realization</button>
                    @endif
                </form>
            </div>
            <form action="{{ route('user-payreqs.realizations.store_detail') }}" method="POST">
                @csrf
                <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <input type="text" name="description" value="{{ old('description') }}"
                                    id="description" class="form-control @error('description') is-invalid @enderror">
                                @error('description')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="text" name="amount" id="amount" class="form-control"
                                    value="{{ old('amount') }}" onkeyup="formatNumber(this)">
                                @error('amount')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <script>
                                function formatNumber(input) {
                                    // Remove any non-digit characters except dots
                                    let value = input.value.replace(/[^\d.]/g, '');

                                    // Ensure only one decimal point
                                    let parts = value.split('.');
                                    if (parts.length > 2) {
                                        parts = [parts[0], parts.slice(1).join('')];
                                    }

                                    // Add thousand separators
                                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

                                    // Join with decimal part if exists
                                    input.value = parts.join('.');
                                }
                            </script>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-4">
                            <div class="form-group">
                                <label for="unit_no">Unit No</label>
                                <select id="unit_no" name="unit_no" class="form-control select2bs4">
                                    <option value="">-- select unit no --</option>
                                    @foreach ($equipments as $item)
                                        <option value="{{ $item->unit_code }}">{{ $item->unit_code }} -
                                            {{ $item->project }} - {{ $item->plant_group }} -
                                            {{ $item->nomor_polisi }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="nopol">No Polisi <small>(optional)</small></label>
                                <input type="text" name="nopol" value="{{ old('nopol') }}" id="nopol"
                                    class="form-control">
                            </div>
                        </div>

                        <div class="col-1">
                            <div class="form-group">
                                <label for="qty">Qty</label>
                                <input id="qty" name="qty" class="form-control">
                            </div>
                        </div>
                        <div class="col-1">
                            <div class="form-group">
                                <label for="km_position">HM</label>
                                <input id="km_position" name="km_position" class="form-control">
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="type">Type</label>
                                <select id="type" name="type" class="form-control select2bs4">
                                    <option value="">-- type --</option>
                                    <option value="fuel">Fuel</option>
                                    <option value="service">Service</option>
                                    <option value="tax">STNK / Tax</option>
                                    <option value="other">Others</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="uom">UOM</label>
                                <select id="uom" name="uom" class="form-control select2bs4">
                                    <option value="">-- uom --</option>
                                    <option value="liter">liter</option>
                                    <option value="each">Each</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-sm btn-success btn-block"><i class="fas fa-save"></i> ADD
                        DETAIL</button>
                </div>
            </form>
        </div>
    </div>
</div>
