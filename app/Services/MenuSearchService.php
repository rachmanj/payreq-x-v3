<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\URL;

class MenuSearchService
{
    public function __construct(
        protected PcbcComplianceService $pcbcComplianceService
    ) {}

    /**
     * @return array<int, array{title: string, route: string, icon: string, category: string, breadcrumb: string, keywords: array<int, string>, searchText: string}>
     */
    public function getItemsForUser(User $user): array
    {
        $items = [];

        $this->pushDashboard($user, $items);
        $this->pushMyPayReqs($user, $items);
        $this->pushCashier($user, $items);
        $this->pushAccounting($user, $items);
        $this->pushApprovals($user, $items);
        $this->pushAdmin($user, $items);
        $this->pushSearch($user, $items);

        return $items;
    }

    /**
     * @param  array<int, array{title: string, route: string, icon: string, category: string, breadcrumb: string, keywords: array<int, string>, searchText: string}>  $items
     */
    protected function pushDashboard(User $user, array &$items): void
    {
        if ($user->can('cashier_dashboard')) {
            $items[] = $this->makeItem(
                'Dashboard',
                route('cashier.dashboard.index'),
                'fas fa-tachometer-alt',
                'Dashboard',
                ['cashier', 'home']
            );

            return;
        }

        $items[] = $this->makeItem(
            'Dashboard',
            route('dashboard.index'),
            'fas fa-tachometer-alt',
            'Dashboard',
            ['home']
        );
    }

    /**
     * @param  array<int, array{title: string, route: string, icon: string, category: string, breadcrumb: string, keywords: array<int, string>, searchText: string}>  $items
     */
    protected function pushMyPayReqs(User $user, array &$items): void
    {
        if (! $user->can('akses_my_payreqs')) {
            return;
        }

        $cat = 'My PayReqs';

        $items[] = $this->makeItem('Submissions', route('user-payreqs.index'), 'far fa-circle', $cat, ['payreq', 'payment request']);
        $items[] = $this->makeItem('Realizations', route('user-payreqs.realizations.index'), 'far fa-circle', $cat, ['realization']);
        $items[] = $this->makeItem('LOT Claims', route('user-payreqs.lotclaims.index'), 'far fa-circle', $cat, ['lot']);

        if ($user->can('akses_anggarans')) {
            $items[] = $this->makeItem('RAB', route('user-payreqs.anggarans.index'), 'far fa-circle', $cat, ['budget', 'anggaran']);
        }

        $items[] = $this->makeItem('Histories', route('user-payreqs.histories.index'), 'far fa-circle', $cat, ['history']);

        if ($user->canAny(['request_faktur', 'update_faktur'])) {
            $items[] = $this->makeItem('Faktur', route('user-payreqs.fakturs.index'), 'far fa-circle', $cat, ['invoice', 'tax']);
        }

        if ($user->can('akses_dokumen_upload')) {
            $items[] = $this->makeItem(
                'Rekening Koran',
                route('cashier.koran.index', ['page' => 'dashboard']),
                'far fa-circle',
                $cat,
                ['bank statement', 'koran']
            );
        }

        if ($user->can('akses_reports')) {
            $items[] = $this->makeItem('Reports', route('reports.index'), 'far fa-circle', $cat, ['report']);
        }
    }

    /**
     * @param  array<int, array{title: string, route: string, icon: string, category: string, breadcrumb: string, keywords: array<int, string>, searchText: string}>  $items
     */
    protected function pushCashier(User $user, array &$items): void
    {
        if (! $user->can('akses_cashier_menu')) {
            return;
        }

        $cat = 'Cashier';
        $inCashierPcbcScope = $user->can('akses_transaksi_cashier') || $user->can('akses_pcbc');
        $sanctioned = $inCashierPcbcScope && $this->pcbcComplianceService->isSanctioned($user);

        if ($user->can('akses_transaksi_cashier')) {
            if (! $sanctioned) {
                $items[] = $this->makeItem('Ready to Pay', route('cashier.approveds.index'), 'far fa-circle', $cat, ['approved', 'payment']);
                $items[] = $this->makeItem('Incoming List', route('cashier.incomings.index'), 'far fa-circle', $cat, ['incoming']);
            }

            $items[] = $this->makeItem('Verifications', route('verifications.index'), 'far fa-circle', $cat, ['verify']);
            $items[] = $this->makeItem('SAP Transactions', route('cashier.sap-transactions.index'), 'far fa-circle', $cat, ['sap']);
            $items[] = $this->makeItem('Cash On-Hand Tx', route('cashier.cashonhand-transactions.index'), 'far fa-circle', $cat, ['cash', 'on hand']);
            $items[] = $this->makeItem('Bank Transaction', route('cashier.bank-transactions.index'), 'far fa-circle', $cat, ['bank']);
        }

        if ($user->can('akses_realization_attachments')) {
            $items[] = $this->makeItem(
                'Realization Attachments',
                route('cashier.realization-attachments.index'),
                'far fa-circle',
                $cat,
                ['attachments']
            );
        }

        if ($user->can('akses_verification_journal')) {
            $items[] = $this->makeItem(
                'Verification Journal',
                route('verifications.journal.index'),
                'far fa-circle',
                $cat,
                ['journal']
            );
        }

        if ($user->can('akses_invoice_payment')) {
            $items[] = $this->makeItem(
                'Invoice Payment',
                route('cashier.invoice-payment.index'),
                'far fa-circle',
                $cat,
                ['invoice']
            );
        }

        if ($user->can('akses_bilyet')) {
            $items[] = $this->makeItem(
                'Administrasi Bilyet',
                route('cashier.bilyets.index', ['page' => 'dashboard']),
                'far fa-circle',
                $cat,
                ['bilyet']
            );
        }

        if ($user->can('akses_cashier_modal')) {
            $items[] = $this->makeItem(
                'Serah/Terima Modal',
                route('cashier.modal.index'),
                'far fa-circle',
                $cat,
                ['modal', 'cash']
            );
        }

        if ($user->can('akses_cash_journal')) {
            $items[] = $this->makeItem('Cash Journal', route('cash-journals.index'), 'far fa-circle', $cat, ['journal']);
        }

        if ($user->can('akses_pcbc')) {
            $items[] = $this->makeItem(
                'PCBC',
                route('cashier.pcbc.index', ['page' => 'dashboard']),
                'far fa-circle',
                $cat,
                ['compliance']
            );
        }

        if ($user->can('akses_koran')) {
            $items[] = $this->makeItem(
                'Rekening Koran',
                route('cashier.koran.index', ['page' => 'dashboard']),
                'far fa-circle',
                $cat,
                ['bank statement', 'koran']
            );
        }

        if ($user->can('akses_migrasi')) {
            $items[] = $this->makeItem('Migrasi', route('cashier.migrasi.index'), 'far fa-circle', $cat, ['migration']);
        }
    }

    /**
     * @param  array<int, array{title: string, route: string, icon: string, category: string, breadcrumb: string, keywords: array<int, string>, searchText: string}>  $items
     */
    protected function pushAccounting(User $user, array &$items): void
    {
        if (! $user->can('akses_accounting_menu')) {
            return;
        }

        $cat = 'Accounting';

        if ($user->can('akses_sap_sync')) {
            $items[] = $this->makeItem(
                'SAP Sync',
                route('accounting.sap-sync.index', ['page' => 'dashboard']),
                'far fa-circle',
                $cat,
                ['sap', 'sync']
            );
        }

        if ($user->can('akses_coa')) {
            $items[] = $this->makeItem(
                'Available Accounts',
                route('accounts.index'),
                'far fa-circle',
                $cat,
                ['coa', 'chart of accounts', 'gl']
            );
        }

        if ($user->can('akses_exchange_rates')) {
            $items[] = $this->makeItem(
                'Exchange Rates',
                route('accounting.exchange-rates.index'),
                'far fa-circle',
                $cat,
                ['fx', 'currency']
            );
        }

        if ($user->can('akses_giro')) {
            $items[] = $this->makeItem('Giro', route('accounting.giros.index'), 'far fa-circle', $cat, ['cheque']);
        }

        if ($user->can('akses_project_payreqs')) {
            $items[] = $this->makeItem(
                'Project Payreqs',
                route('accounting.payreqs.index'),
                'far fa-circle',
                $cat,
                ['payreq', 'project']
            );
        }

        if ($user->hasAnyRole(['superadmin', 'admin', 'cashier'])) {
            $items[] = $this->makeItem(
                'Documents Overdue',
                route('document-overdue.payreq.index'),
                'far fa-circle',
                $cat,
                ['overdue', 'documents']
            );

            if ($user->can('approve_overdue_extension')) {
                $items[] = $this->makeItem(
                    'Approve overdue extensions',
                    route('document-overdue.extensions.index'),
                    'far fa-circle',
                    $cat,
                    ['extension', 'approve']
                );
            }

            $items[] = $this->makeItem(
                'Customer List',
                route('accounting.customers.index'),
                'far fa-circle',
                $cat,
                ['customer', 'vendor']
            );

            $items[] = $this->makeItem(
                'Daily Tx Upload',
                route('accounting.daily-tx.index'),
                'far fa-circle',
                $cat,
                ['transaction', 'upload']
            );
        }

        if ($user->can('akses_wtax23')) {
            $items[] = $this->makeItem(
                'VAT',
                route('accounting.vat.index', ['page' => 'dashboard']),
                'far fa-circle',
                $cat,
                ['ppn', 'tax']
            );
            $items[] = $this->makeItem(
                'WTax 23',
                route('accounting.wtax23.index', ['page' => 'dashboard']),
                'far fa-circle',
                $cat,
                ['withholding', 'tax']
            );
        }

        if ($user->can('akses_delivery')) {
            $items[] = $this->makeItem(
                'Delivery',
                route('accounting.deliveries.index', ['page' => 'dashboard']),
                'far fa-circle',
                $cat,
                ['kirim']
            );
        }

        if ($user->can('akses_loan_report')) {
            $items[] = $this->makeItem('Loan List', route('accounting.loans.index'), 'far fa-circle', $cat, ['loan', 'pinjaman']);
        }

        if ($user->can('akses_reports')) {
            $items[] = $this->makeItem('Reports', route('reports.index'), 'far fa-circle', $cat, ['report']);
        }
    }

    /**
     * @param  array<int, array{title: string, route: string, icon: string, category: string, breadcrumb: string, keywords: array<int, string>, searchText: string}>  $items
     */
    protected function pushApprovals(User $user, array &$items): void
    {
        if (! $user->can('akses_approvals')) {
            return;
        }

        $cat = 'Approvals';

        if ($user->can('akses_approval_stage')) {
            $items[] = $this->makeItem(
                'Approval Stages',
                route('approval-stages.index'),
                'far fa-circle',
                $cat,
                ['workflow', 'stage']
            );
        }

        if ($user->can('akses_approval_request')) {
            $items[] = $this->makeItem(
                'Payment Request',
                route('approvals.request.payreqs.index'),
                'far fa-circle',
                $cat,
                ['payreq', 'approval']
            );
            $items[] = $this->makeItem(
                'Realizations',
                route('approvals.request.realizations.index'),
                'far fa-circle',
                $cat,
                ['realization']
            );
            $items[] = $this->makeItem(
                'RAB',
                route('approvals.request.anggarans.index'),
                'far fa-circle',
                $cat,
                ['budget', 'anggaran']
            );
        }

        if ($user->can('akses_reports')) {
            $items[] = $this->makeItem('Reports', route('reports.index'), 'far fa-circle', $cat, ['report']);
        }
    }

    /**
     * @param  array<int, array{title: string, route: string, icon: string, category: string, breadcrumb: string, keywords: array<int, string>, searchText: string}>  $items
     */
    protected function pushAdmin(User $user, array &$items): void
    {
        if (! $user->can('akses_admin')) {
            return;
        }

        $cat = 'Admin';

        $items[] = $this->makeItem('Accounts', route('accounts.index'), 'far fa-circle', $cat, ['coa', 'gl']);
        $items[] = $this->makeItem('Currencies', route('currencies.index'), 'far fa-circle', $cat, ['currency', 'money']);
        $items[] = $this->makeItem(
            'Documents Overdue',
            route('document-overdue.payreq.index'),
            'far fa-circle',
            $cat,
            ['overdue']
        );

        if ($user->can('approve_overdue_extension')) {
            $items[] = $this->makeItem(
                'Approve overdue extensions',
                route('document-overdue.extensions.index'),
                'far fa-circle',
                $cat,
                ['extension']
            );
        }

        if ($user->can('akses_sync_buc')) {
            $items[] = $this->makeItem('Sync BUCs', route('rabs.sync.index'), 'far fa-circle', $cat, ['rab', 'buc', 'sync']);
        }

        if ($user->can('akses_sync_equipments')) {
            $items[] = $this->makeItem(
                'Sync Equipments',
                route('equipments.sync.index'),
                'far fa-circle',
                $cat,
                ['equipment', 'sync']
            );
        }

        if ($user->can('akses_user')) {
            $items[] = $this->makeItem('User List', route('users.index'), 'far fa-circle', $cat, ['users']);
        }

        if ($user->can('akses_role')) {
            $items[] = $this->makeItem('Roles', route('roles.index'), 'far fa-circle', $cat, ['role']);
        }

        if ($user->can('akses_permission')) {
            $items[] = $this->makeItem('Permissions', route('permissions.index'), 'far fa-circle', $cat, ['permission']);
            $items[] = $this->makeItem(
                'Document Numbering',
                route('document-number.index'),
                'far fa-circle',
                $cat,
                ['number', 'document']
            );
            $items[] = $this->makeItem(
                'Advance Parameters',
                route('parameters.index'),
                'far fa-circle',
                $cat,
                ['parameters', 'settings']
            );
        }

        if ($user->can('akses_admin')) {
            $items[] = $this->makeItem(
                'Printable Documents',
                route('admin.printable-documents.index'),
                'far fa-circle',
                $cat,
                ['print']
            );
            $items[] = $this->makeItem('API Keys', route('admin.api-keys.index'), 'far fa-circle', $cat, ['api', 'key']);

            if ($user->can('projects.view')) {
                $items[] = $this->makeItem('Projects', route('admin.projects.index'), 'far fa-circle', $cat, ['project']);
            }

            if ($user->can('departments.view')) {
                $items[] = $this->makeItem(
                    'Departments',
                    route('admin.departments.index'),
                    'far fa-circle',
                    $cat,
                    ['department']
                );
            }

            if ($user->can('akses_admin')) {
                $items[] = $this->makeItem(
                    'Business Partners',
                    route('admin.business-partners.index'),
                    'far fa-circle',
                    $cat,
                    ['partner', 'vendor']
                );
                $items[] = $this->makeItem(
                    'Creditors',
                    route('admin.creditors.index'),
                    'far fa-circle',
                    $cat,
                    ['creditor']
                );
                $items[] = $this->makeItem(
                    'SAP Master Data Sync',
                    route('admin.sap-master-data-sync.index'),
                    'far fa-circle',
                    $cat,
                    ['sap', 'master']
                );
            }
        }

        $items[] = $this->makeItem(
            'Announcements',
            route('announcements.index'),
            'far fa-circle',
            $cat,
            ['news', 'banner']
        );
    }

    /**
     * @param  array<int, array{title: string, route: string, icon: string, category: string, breadcrumb: string, keywords: array<int, string>, searchText: string}>  $items
     */
    protected function pushSearch(User $user, array &$items): void
    {
        if (! $user->can('can_search')) {
            return;
        }

        $items[] = $this->makeItem(
            'Search',
            route('search.index'),
            'fas fa-search',
            'Search',
            ['document', 'find', 'nomor']
        );
    }

    /**
     * @param  array<int, string>  $keywords
     * @return array{title: string, route: string, icon: string, category: string, breadcrumb: string, keywords: array<int, string>, searchText: string}
     */
    protected function makeItem(string $title, string $url, string $icon, string $category, array $keywords = []): array
    {
        $breadcrumb = 'MAIN > '.$category.' > '.$title;
        $parts = array_merge([$title, $breadcrumb], $keywords);
        $searchText = strtolower(implode(' ', $parts));
        $searchText = preg_replace('/\s+/', ' ', $searchText) ?? $searchText;

        return [
            'title' => $title,
            'route' => URL::to($url),
            'icon' => $icon,
            'category' => $category,
            'breadcrumb' => $breadcrumb,
            'keywords' => array_values($keywords),
            'searchText' => $searchText,
        ];
    }
}
