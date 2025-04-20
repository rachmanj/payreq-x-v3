<li class="nav-item dropdown">
    <a id="dropdownApprovals" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
        class="nav-link dropdown-toggle">Cashier</a>
    <ul aria-labelledby="dropdownApprovals" class="dropdown-menu border-0 shadow">
        @can('akses_transaksi_cashier')
            <li><a href="{{ route('cashier.approveds.index') }}" class="dropdown-item">Ready to Pay</a></li>
            <li><a href="{{ route('cashier.incomings.index') }}" class="dropdown-item">Incoming List</a></li>
            <li><a href="{{ route('verifications.index') }}" class="dropdown-item">Verifications</a></li>
            <li><a href="{{ route('cashier.sap-transactions.index') }}" class="dropdown-item">SAP Transactions </a></li>
            <li><a href="{{ route('cashier.cashonhand-transactions.index') }}" class="dropdown-item">Cash On-Hand
                    Transaction <span class="badge badge-success">Beta</span></a></li>
            {{-- <li><a href="{{ route('cashier.transaksis.index') }}" class="dropdown-item">Account History</a></li> --}}
            {{-- <li><a href="{{ route('cashier.outgoings.index') }}" class="dropdown-item">Outgoing List</a></li> --}}
            <li><a href="{{ route('cashier.bank-transactions.index') }}" class="dropdown-item">Bank Transaction<span
                        class="badge badge-success">Beta</span></a></li>
        @endcan
        @can('akses_verification_journal')
            <li><a href="{{ route('verifications.journal.index') }}" class="dropdown-item">Verification Journal</a></li>
        @endcan
        <li class="dropdown-divider">EOD</li>
        @can('akses_bilyet')
            <li><a href="{{ route('cashier.bilyets.index', ['page' => 'dashboard']) }}" class="dropdown-item">Administrasi
                    Bilyet</a></li>
        @endcan
        @can('akses_cashier_modal')
            <li><a href="{{ route('cashier.modal.index') }}" class="dropdown-item">Serah/Terima Modal</a></li>
        @endcan
        @can('akses_cash_journal')
            <li><a href="{{ route('cash-journals.index') }}" class="dropdown-item">Cash Journal</a></li>
        @endcan
        @can('akses_pcbc')
            <li><a href="{{ route('cashier.pcbc.index', ['page' => 'dashboard']) }}" class="dropdown-item">PCBC</a></li>
        @endcan
        @can('akses_koran')
            <li><a href="{{ route('cashier.koran.index', ['page' => 'dashboard']) }}" class="dropdown-item">Rekening
                    Koran</a></li>
        @endcan
        {{-- @can('akses_dokumen_upload')
      <li><a href="{{ route('cashier.dokumen.index') }}" class="dropdown-item">Upload Dokumen</a></li>
      @endcan --}}
        @can('akses_migrasi')
            <li><a href="{{ route('cashier.migrasi.index') }}" class="dropdown-item">Migrasi</a></li>
        @endcan
    </ul>
</li>
