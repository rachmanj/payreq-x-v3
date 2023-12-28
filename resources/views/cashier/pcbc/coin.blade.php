<div class="card card-info">
    <div class="card-body">
        <div class="row">
            <div class="col-3 text-center"><label>Kopurs</label></div>
            <div class="col-4 text-center"><label>Qty</label></div>
            <div class="col-5 text-center"><label>Amount</label></div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 1.000" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="coin_seribu" value="{{ $pcbc->coin_seribu }}" onchange="calculateAmount(this, 'coin_seribu_amount', 1000)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center coins" id="coin_seribu_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 500" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="coin_lima_ratus" value="{{ $pcbc->coin_lima_ratus }}" onchange="calculateAmount(this, 'coin_lima_ratus_amount', 500)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center coins" id="coin_lima_ratus_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 200" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="coin_dua_ratus" value="{{ $pcbc->coin_dua_ratus }}" onchange="calculateAmount(this, 'coin_dua_ratus_amount', 200)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center coins" id="coin_dua_ratus_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 100" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="coin_seratus" value="{{ $pcbc->coin_seratus }}" onchange="calculateAmount(this, 'coin_seratus_amount', 100)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center coin" id="coin_seratus_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 50" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="coin_lima_puluh" value="{{ $pcbc->coin_lima_puluh }}" onchange="calculateAmount(this, 'coin_lima_puluh_amount', 50)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center coins" id="coin_lima_puluh_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <input type="text" class="form-control text-center" value="Rp. 25" disabled>
            </div>
            <div class="col-4">
                <input type="text" class="form-control text-center" name="coin_dua_puluh_lima" value="{{ $pcbc->coin_dua_puluh_lima }}" onchange="calculateAmount(this, 'coin_dua_puluh_lima_amount', 25)">
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center coins" id="coin_dua_puluh_lima_amount" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-7">
                <input type="text" class="form-control text-right" style="border: none; background: none; font-weight: bold;" value="TOTAL" readonly>
            </div>
            <div class="col-5">
                <input type="text" class="form-control text-center" style="background: none; font-weight: bold;" id="fisik_coin" disabled>
            </div>
        </div>

    </div>
</div>