<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('dashboard.index') }}" class="brand-link">
        <span class="brand-text font-weight-light"><strong>Accounting</strong>One</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        {{-- <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="info">
                <a href="#" class="d-block">{{ auth()->user()->name }}</a>
                <small class="text-muted">{{ auth()->user()->project }}</small>
            </div>
        </div> --}}

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <!-- Dashboard -->
                <li class="nav-item">
                    @can('cashier_dashboard')
                        <a href="{{ route('cashier.dashboard.index') }}"
                            class="nav-link {{ request()->routeIs('cashier.dashboard.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    @else
                        <a href="{{ route('dashboard.index') }}"
                            class="nav-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    @endcan
                </li>

                <!-- My PayReqs -->
                @can('akses_my_payreqs')
                    <li class="nav-item has-treeview {{ request()->routeIs('user-payreqs.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('user-payreqs.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>
                                My PayReqs
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('user-payreqs.index') }}"
                                    class="nav-link {{ request()->routeIs('user-payreqs.index') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Submissions</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('user-payreqs.realizations.index') }}"
                                    class="nav-link {{ request()->routeIs('user-payreqs.realizations.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Realizations</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('user-payreqs.lotclaims.index') }}"
                                    class="nav-link {{ request()->routeIs('user-payreqs.lotclaims.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>LOT Claims</p>
                                </a>
                            </li>
                            @can('akses_anggarans')
                                <li class="nav-item">
                                    <a href="{{ route('user-payreqs.anggarans.index') }}"
                                        class="nav-link {{ request()->routeIs('user-payreqs.anggarans.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>RAB</p>
                                    </a>
                                </li>
                            @endcan
                            <li class="nav-item">
                                <a href="{{ route('user-payreqs.histories.index') }}"
                                    class="nav-link {{ request()->routeIs('user-payreqs.histories.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Histories</p>
                                </a>
                            </li>
                            @canany(['request_faktur', 'update_faktur'])
                                <li class="nav-item">
                                    <a href="{{ route('user-payreqs.fakturs.index') }}"
                                        class="nav-link {{ request()->routeIs('user-payreqs.fakturs.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Faktur</p>
                                    </a>
                                </li>
                            @endcanany
                            @can('akses_dokumen_upload')
                                <li class="nav-item">
                                    <a href="{{ route('cashier.koran.index', ['page' => 'dashboard']) }}"
                                        class="nav-link {{ request()->routeIs('cashier.koran.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Rekening Koran</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_reports')
                                <li class="nav-item">
                                    <a href="{{ route('reports.index') }}"
                                        class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Reports</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                <!-- Cashier -->
                @can('akses_cashier_menu')
                    <li
                        class="nav-item has-treeview {{ request()->routeIs('cashier.*') || request()->routeIs('verifications.*') || request()->routeIs('cash-journals.*') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ request()->routeIs('cashier.*') || request()->routeIs('verifications.*') || request()->routeIs('cash-journals.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cash-register"></i>
                            <p>
                                Cashier
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('akses_transaksi_cashier')
                                <li class="nav-item">
                                    <a href="{{ route('cashier.approveds.index') }}"
                                        class="nav-link {{ request()->routeIs('cashier.approveds.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ready to Pay</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('cashier.incomings.index') }}"
                                        class="nav-link {{ request()->routeIs('cashier.incomings.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Incoming List</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('verifications.index') }}"
                                        class="nav-link {{ request()->routeIs('verifications.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Verifications</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('cashier.sap-transactions.index') }}"
                                        class="nav-link {{ request()->routeIs('cashier.sap-transactions.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>
                                            SAP Transactions
                                        </p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('cashier.cashonhand-transactions.index') }}"
                                        class="nav-link {{ request()->routeIs('cashier.cashonhand-transactions.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>
                                            Cash On-Hand Tx
                                        </p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('cashier.bank-transactions.index') }}"
                                        class="nav-link {{ request()->routeIs('cashier.bank-transactions.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>
                                            Bank Transaction
                                        </p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_verification_journal')
                                <li class="nav-item">
                                    <a href="{{ route('verifications.journal.index') }}"
                                        class="nav-link {{ request()->routeIs('verifications.journal.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Verification Journal</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_invoice_payment')
                                <li class="nav-item">
                                    <a href="{{ route('cashier.invoice-payment.index') }}"
                                        class="nav-link {{ request()->routeIs('cashier.invoice-payment.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Invoice Payment</p>
                                    </a>
                                </li>
                            @endcan
                            {{-- <li class="nav-header">EOD</li> --}}
                            @can('akses_bilyet')
                                <li class="nav-item">
                                    <a href="{{ route('cashier.bilyets.index', ['page' => 'dashboard']) }}"
                                        class="nav-link {{ request()->routeIs('cashier.bilyets.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Administrasi Bilyet</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_cashier_modal')
                                <li class="nav-item">
                                    <a href="{{ route('cashier.modal.index') }}"
                                        class="nav-link {{ request()->routeIs('cashier.modal.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Serah/Terima Modal</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_cash_journal')
                                <li class="nav-item">
                                    <a href="{{ route('cash-journals.index') }}"
                                        class="nav-link {{ request()->routeIs('cash-journals.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Cash Journal</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_pcbc')
                                <li class="nav-item">
                                    <a href="{{ route('cashier.pcbc.index', ['page' => 'dashboard']) }}"
                                        class="nav-link {{ request()->routeIs('cashier.pcbc.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>PCBC</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_koran')
                                <li class="nav-item">
                                    <a href="{{ route('cashier.koran.index', ['page' => 'dashboard']) }}"
                                        class="nav-link {{ request()->routeIs('cashier.koran.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Rekening Koran</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_migrasi')
                                <li class="nav-item">
                                    <a href="{{ route('cashier.migrasi.index') }}"
                                        class="nav-link {{ request()->routeIs('cashier.migrasi.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Migrasi</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                <!-- Accounting -->
                @can('akses_accounting_menu')
                    <li
                        class="nav-item has-treeview {{ request()->routeIs('accounting.*') || request()->routeIs('accounts.index') || request()->routeIs('document-overdue.*') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ request()->routeIs('accounting.*') || request()->routeIs('accounts.index') || request()->routeIs('document-overdue.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book"></i>
                            <p>
                                Accounting
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('akses_sap_sync')
                                <li class="nav-item">
                                    <a href="{{ route('accounting.sap-sync.index', ['page' => 'dashboard']) }}"
                                        class="nav-link {{ request()->routeIs('accounting.sap-sync.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>SAP Sync</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_coa')
                                <li class="nav-item">
                                    <a href="{{ route('accounts.index') }}"
                                        class="nav-link {{ request()->routeIs('accounts.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Available Accounts</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_exchange_rates')
                                <li class="nav-item">
                                    <a href="{{ route('accounting.exchange-rates.index') }}"
                                        class="nav-link {{ request()->routeIs('accounting.exchange-rates.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Exchange Rates</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_giro')
                                <li class="nav-item">
                                    <a href="{{ route('accounting.giros.index') }}"
                                        class="nav-link {{ request()->routeIs('accounting.giros.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Giro</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_project_payreqs')
                                <li class="nav-item">
                                    <a href="{{ route('accounting.payreqs.index') }}"
                                        class="nav-link {{ request()->routeIs('accounting.payreqs.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Project Payreqs</p>
                                    </a>
                                </li>
                            @endcan
                            @hasanyrole('superadmin|admin|cashier')
                                <li class="nav-item">
                                    <a href="{{ route('document-overdue.payreq.index') }}"
                                        class="nav-link {{ request()->routeIs('document-overdue.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Documents Overdue</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('accounting.customers.index') }}"
                                        class="nav-link {{ request()->routeIs('accounting.customers.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Customer List</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('accounting.daily-tx.index') }}"
                                        class="nav-link {{ request()->routeIs('accounting.daily-tx.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Daily Tx Upload</p>
                                    </a>
                                </li>
                            @endhasanyrole
                            @can('akses_wtax23')
                                <li class="nav-item">
                                    <a href="{{ route('accounting.vat.index', ['page' => 'dashboard']) }}"
                                        class="nav-link {{ request()->routeIs('accounting.vat.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>VAT</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('accounting.wtax23.index', ['page' => 'dashboard']) }}"
                                        class="nav-link {{ request()->routeIs('accounting.wtax23.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>WTax 23</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_delivery')
                                <li class="nav-item">
                                    <a href="{{ route('accounting.deliveries.index', ['page' => 'dashboard']) }}"
                                        class="nav-link {{ request()->routeIs('accounting.deliveries.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Delivery</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_loan_report')
                                <li class="nav-item">
                                    <a href="{{ route('accounting.loans.index') }}"
                                        class="nav-link {{ request()->routeIs('accounting.loans.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Loan List</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_reports')
                                <li class="nav-item">
                                    <a href="{{ route('reports.index') }}"
                                        class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Reports</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                <!-- Approvals -->
                @can('akses_approvals')
                    <li
                        class="nav-item has-treeview {{ request()->routeIs('approval-stages.*') || request()->routeIs('approvals.*') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ request()->routeIs('approval-stages.*') || request()->routeIs('approvals.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-clipboard-check"></i>
                            <p>
                                Approvals
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('akses_approval_stage')
                                <li class="nav-item">
                                    <a href="{{ route('approval-stages.index') }}"
                                        class="nav-link {{ request()->routeIs('approval-stages.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Approval Stages</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_approval_request')
                                <li class="nav-item">
                                    <a href="{{ route('approvals.request.payreqs.index') }}"
                                        class="nav-link {{ request()->routeIs('approvals.request.payreqs.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Payment Request</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('approvals.request.realizations.index') }}"
                                        class="nav-link {{ request()->routeIs('approvals.request.realizations.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Realizations</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('approvals.request.anggarans.index') }}"
                                        class="nav-link {{ request()->routeIs('approvals.request.anggarans.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>RAB</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_reports')
                                <li class="nav-item">
                                    <a href="{{ route('reports.index') }}"
                                        class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Reports</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                <!-- Admin -->
                @can('akses_admin')
                    <li
                        class="nav-item has-treeview {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('accounts.index') || request()->routeIs('currencies.*') || request()->routeIs('rabs.*') || request()->routeIs('equipments.*') || request()->routeIs('document-number.*') || request()->routeIs('parameters.*') || request()->routeIs('admin.*') || request()->routeIs('announcements.*') || request()->routeIs('document-overdue.*') || request()->routeIs('admin.creditors.*') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('accounts.index') || request()->routeIs('currencies.*') || request()->routeIs('rabs.*') || request()->routeIs('equipments.*') || request()->routeIs('document-number.*') || request()->routeIs('parameters.*') || request()->routeIs('admin.*') || request()->routeIs('announcements.*') || request()->routeIs('document-overdue.*') || request()->routeIs('admin.creditors.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>
                                Admin
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('accounts.index') }}"
                                    class="nav-link {{ request()->routeIs('accounts.index') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Accounts</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('currencies.index') }}"
                                    class="nav-link {{ request()->routeIs('currencies.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Currencies</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('document-overdue.payreq.index') }}"
                                    class="nav-link {{ request()->routeIs('document-overdue.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Documents Overdue</p>
                                </a>
                            </li>
                            @can('akses_sync_buc')
                                <li class="nav-item">
                                    <a href="{{ route('rabs.sync.index') }}"
                                        class="nav-link {{ request()->routeIs('rabs.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Sync BUCs</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_sync_equipments')
                                <li class="nav-item">
                                    <a href="{{ route('equipments.sync.index') }}"
                                        class="nav-link {{ request()->routeIs('equipments.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Sync Equipments</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_user')
                                <li class="nav-item">
                                    <a href="{{ route('users.index') }}"
                                        class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>User List</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_role')
                                <li class="nav-item">
                                    <a href="{{ route('roles.index') }}"
                                        class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Roles</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_permission')
                                <li class="nav-item">
                                    <a href="{{ route('permissions.index') }}"
                                        class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Permissions</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('document-number.index') }}"
                                        class="nav-link {{ request()->routeIs('document-number.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Document Numbering</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('parameters.index') }}"
                                        class="nav-link {{ request()->routeIs('parameters.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Advance Parameters</p>
                                    </a>
                                </li>
                            @endcan
                            @can('akses_admin')
                                <li class="nav-item">
                                    <a href="{{ route('admin.printable-documents.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.printable-documents.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Printable Documents</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.api-keys.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.api-keys.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>API Keys</p>
                                    </a>
                                </li>
                                @can('projects.view')
                                    <li class="nav-item">
                                        <a href="{{ route('admin.projects.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Projects</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('departments.view')
                                    <li class="nav-item">
                                        <a href="{{ route('admin.departments.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Departments</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('akses_admin')
                                    <li class="nav-item">
                                        <a href="{{ route('admin.business-partners.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.business-partners.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Business Partners</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.creditors.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.creditors.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Creditors</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.sap-master-data-sync.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.sap-master-data-sync.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>SAP Master Data Sync</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcan
                            <li class="nav-item">
                                <a href="{{ route('announcements.index') }}"
                                    class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Announcements</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                <!-- Search -->
                @can('can_search')
                    <li class="nav-item">
                        <a href="{{ route('search.index') }}"
                            class="nav-link {{ request()->routeIs('search.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-search"></i>
                            <p>Search</p>
                        </a>
                    </li>
                @endcan

            </ul>
        </nav>
    </div>
</aside>
