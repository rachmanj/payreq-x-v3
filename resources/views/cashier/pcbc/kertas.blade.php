<div class="card card-info">
    <div class="card-body">
        <div class="row">
            <div class="col-3 text-center"><label>Kopurs</label></div>
            <div class="col-4 text-center"><label>Qty</label></div>
            <div class="col-5 text-center"><label>Amount</label></div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 100.000" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" value="{{ $pcbc->seratus_ribu }}" name="seratus_ribu" onchange="calculateAmount(this, 'seratus_ribu_amount', 100000)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center kertas" id="seratus_ribu_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 50.000" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="lima_puluh_ribu" value="{{ $pcbc->lima_puluh_ribu }}" onchange="calculateAmount(this, 'lima_puluh_ribu_amount', 50000)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center kertas" id="lima_puluh_ribu_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 20.000" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="dua_puluh_ribu" value="{{ $pcbc->dua_puluh_ribu }}" onchange="calculateAmount(this, 'dua_puluh_ribu_amount', 20000)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center kertas" id="dua_puluh_ribu_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 10.000" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="sepuluh_ribu" value="{{ $pcbc->sepuluh_ribu }}" onchange="calculateAmount(this, 'sepuluh_ribu_amount', 10000)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center kertas" id="sepuluh_ribu_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 5.000" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="lima_ribu" value="{{ $pcbc->lima_ribu }}" onchange="calculateAmount(this, 'lima_ribu_amount', 5000)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center kertas" id="lima_ribu_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 2.000" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="dua_ribu" value="{{ $pcbc->dua_ribu }}" onchange="calculateAmount(this, 'dua_ribu_amount', 2000)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center kertas" id="dua_ribu_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 1.000" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="seribu" value="{{ $pcbc->seribu }}" onchange="calculateAmount(this, 'seribu_amount', 1000)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center kertas" id="seribu_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 500" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="lima_ratus" value="{{ $pcbc->lima_ratus }}" onchange="calculateAmount(this, 'lima_ratus_amount', 500)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center kertas" id="lima_ratus_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 100" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="seratus" value="{{ $pcbc->seratus }}" onchange="calculateAmount(this, 'seratus_amount', 100)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center kertas" id="seratus_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-7">
                <input type="text" class="form-control text-right" style="border: none; background: none; font-weight: bold;" value="TOTAL" readonly>
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center" style="background: none; font-weight: bold;" id="fisik_kertas" disabled>
            </div>
        </div>

    </div>
</div>