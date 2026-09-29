<div class="card mb-3">
    <div class="card-header">
        <a href="{{ route('accounting.tax.ppn.index') }}"
            class="{{ request()->routeIs('accounting.tax.ppn.index') ? 'font-weight-bold text-uppercase' : '' }}">
            Dashboard PPN
        </a>
        |
        <a href="{{ route('accounting.tax.ppn.masukan.index', ['masa_pajak' => $masaPajak ?? request('masa_pajak')]) }}"
            class="{{ request()->routeIs('accounting.tax.ppn.masukan.*') ? 'font-weight-bold text-uppercase' : '' }}">
            Masukan
        </a>
        |
        <a href="{{ route('accounting.tax.ppn.keluaran.index', ['masa_pajak' => $masaPajak ?? request('masa_pajak')]) }}"
            class="{{ request()->routeIs('accounting.tax.ppn.keluaran.*') ? 'font-weight-bold text-uppercase' : '' }}">
            Keluaran
        </a>
        |
        <a href="{{ route('accounting.tax.ppn.belum-diterima.index', ['masa_pajak' => $masaPajak ?? request('masa_pajak')]) }}"
            class="{{ request()->routeIs('accounting.tax.ppn.belum-diterima.*') ? 'font-weight-bold text-uppercase' : '' }}">
            Belum diterima
        </a>
        |
        <a href="{{ route('accounting.tax.ppn.periksa.index') }}"
            class="{{ request()->routeIs('accounting.tax.ppn.periksa.*') ? 'font-weight-bold text-uppercase' : '' }}">
            Periksa
        </a>
        |
        <a href="{{ route('accounting.tax.ppn.sync.index') }}"
            class="{{ request()->routeIs('accounting.tax.ppn.sync.*') ? 'font-weight-bold text-uppercase' : '' }}">
            Sync SAP
        </a>
        |
        <a href="{{ route('accounting.vat.index', ['page' => 'dashboard']) }}">VAT (legacy)</a>
    </div>
</div>
