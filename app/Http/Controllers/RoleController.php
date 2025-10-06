<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return view('roles.index', compact('roles'));
    }


    public function create()
    {
        $permissions = Permission::orderBy('name', 'asc')->get();
        return view('roles.create', compact('permissions'));
    }


    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
        ]);

        $role = Role::create(['name' => $request->name]);

        $role->givePermissionTo($request->input('permission'));

        return redirect()->route('roles.index')->with('success', 'Role created successfully');
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        $role               = Role::find($id);
        $permissions        = Permission::orderBy('name', 'asc')->get();
        $rolePermissions    = $role->permissions()->get()->pluck('id')->toArray();

        // Group permissions by feature categories
        $permissionGroups = [
            'System Administration' => [
                'akses_admin',
                'akses_permission',
                'akses_role',
                'akses_user',
                'edit_user'
            ],
            'Dashboard Access' => [
                'akses_dashboard_000H',
                'akses_dashboard_001H',
                'akses_dashboard_017C',
                'akses_dashboard_021C',
                'akses_dashboard_022C',
                'akses_dashboard_023C',
                'akses_dashboard_025C',
                'cashier_dashboard'
            ],
            'Accounting Operations' => [
                'akses_accounting_menu',
                'akses_coa',
                'akses_eom',
                'akses_verification_journal',
                'akses_anggarans',
                'akses_periode_anggaran',
                'edit_verification_project',
                'see_vj_not_posted'
            ],
            'Cashier Operations' => [
                'akses_cashier_menu',
                'akses_cashier_giro',
                'akses_cashier_modal',
                'akses_transaksi_cashier',
                'akses_today_transaction',
                'akses_cash_journal'
            ],
            'Payreq Management' => [
                'akses_my_payreqs',
                'akses_project_payreqs',
                'akses_payreq_aging',
                'recalculate_release',
                'rab_select'
            ],
            'Approval System' => [
                'akses_approvals',
                'akses_approval_request',
                'akses_approval_stage'
            ],
            'Bilyet Management' => [
                'akses_bilyet',
                'add_bilyet',
                'delete_bilyet',
                'see_bilyet_dashboard'
            ],
            'Exchange Rates' => [
                'akses_exchange_rates',
                'create_exchange_rates',
                'edit_exchange_rates',
                'delete_exchange_rates',
                'import_exchange_rates',
                'export_exchange_rates'
            ],
            'Reports & Analytics' => [
                'akses_reports',
                'akses_report_rab',
                'akses_loan_report',
                'akses_sum_expense_by_equipment',
                'see_activities_chart'
            ],
            'Document Management' => [
                'akses_dokumen_upload',
                'akses_delivery',
                'upload_dokumen',
                'request_faktur',
                'update_faktur'
            ],
            'Data Upload & Import' => [
                'upload_koran',
                'upload_pcbc',
                'akses_koran',
                'akses_pcbc',
                'akses_migrasi'
            ],
            'SAP Integration' => [
                'akses_sap_sync',
                'akses_sync_buc',
                'akses_sync_equipments'
            ],
            'Advance Reports' => [
                'see_rekap_advance_017',
                'see_rekap_advance_021',
                'see_rekap_advance_022',
                'see_rekap_advance_023',
                'see_rekap_advance_025',
                'see_rekap_advance_bo',
                'see_rekap_advance_ho',
                'rekap_dokumen_creation_bo',
                'rekap_dokumen_creation_ho'
            ],
            'Document Reports' => [
                'report_dokumen_koran',
                'report_dokumen_pcbc'
            ],
            'Giro Management' => [
                'akses_giro',
                'create_outgoing'
            ],
            'Tax Management' => [
                'akses_wtax23'
            ],
            'Search & Navigation' => [
                'akses_search',
                'can_search'
            ],
            'Team Management' => [
                'see_team'
            ]
        ];

        // Filter permissions by groups
        $groupedPermissions = [];
        foreach ($permissionGroups as $groupName => $permissionNames) {
            $groupPermissions = $permissions->filter(function ($permission) use ($permissionNames) {
                return in_array($permission->name, $permissionNames);
            });

            if ($groupPermissions->count() > 0) {
                $groupedPermissions[$groupName] = $groupPermissions;
            }
        }

        // Add any remaining permissions not in groups to "Other"
        $groupedPermissionNames = collect($permissionGroups)->flatten()->toArray();
        $remainingPermissions = $permissions->filter(function ($permission) use ($groupedPermissionNames) {
            return !in_array($permission->name, $groupedPermissionNames);
        });

        if ($remainingPermissions->count() > 0) {
            $groupedPermissions['Other'] = $remainingPermissions;
        }

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions', 'groupedPermissions'));
    }


    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        $this->validate($request, [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $id],
        ]);

        $role->update(['name' => $request->name]);

        $role->syncPermissions($request->input('permission'));

        return redirect()->route('roles.index')->with('success', 'Role successfully updated!');
    }


    public function destroy($id)
    {
        //
    }

    public function data()
    {
        $roles = Role::with(['permissions', 'users'])->orderBy('name', 'asc')->get();

        return datatables()->of($roles)
            ->addColumn('users_count', function ($role) {
                return $role->users->count();
            })
            ->addColumn('permissions_preview', function ($role) {
                $permissions = $role->permissions->take(3)->pluck('name')->toArray();
                $preview = implode(', ', $permissions);
                if ($role->permissions->count() > 3) {
                    $preview .= '... (+' . ($role->permissions->count() - 3) . ' more)';
                }
                return $preview ?: 'No permissions';
            })
            ->addColumn('permissions_badges', function ($role) {
                // Group permissions by categories for badge display
                $permissionGroups = [
                    'Admin' => ['akses_admin', 'akses_permission', 'akses_role', 'akses_user', 'edit_user'],
                    'Dashboard' => ['akses_dashboard_000H', 'akses_dashboard_001H', 'akses_dashboard_017C', 'akses_dashboard_021C', 'akses_dashboard_022C', 'akses_dashboard_023C', 'akses_dashboard_025C', 'cashier_dashboard'],
                    'Accounting' => ['akses_accounting_menu', 'akses_coa', 'akses_eom', 'akses_verification_journal', 'akses_anggarans', 'akses_periode_anggaran', 'edit_verification_project', 'see_vj_not_posted'],
                    'Cashier' => ['akses_cashier_menu', 'akses_cashier_giro', 'akses_cashier_modal', 'akses_transaksi_cashier', 'akses_today_transaction', 'akses_cash_journal'],
                    'Payreq' => ['akses_my_payreqs', 'akses_project_payreqs', 'akses_payreq_aging', 'recalculate_release', 'rab_select'],
                    'Approval' => ['akses_approvals', 'akses_approval_request', 'akses_approval_stage'],
                    'Bilyet' => ['akses_bilyet', 'add_bilyet', 'delete_bilyet', 'see_bilyet_dashboard'],
                    'Exchange' => ['akses_exchange_rates', 'create_exchange_rates', 'edit_exchange_rates', 'delete_exchange_rates', 'import_exchange_rates', 'export_exchange_rates'],
                    'Reports' => ['akses_reports', 'akses_report_rab', 'akses_loan_report', 'akses_sum_expense_by_equipment', 'see_activities_chart'],
                    'Documents' => ['akses_dokumen_upload', 'akses_delivery', 'upload_dokumen', 'request_faktur', 'update_faktur'],
                    'Upload' => ['upload_koran', 'upload_pcbc', 'akses_koran', 'akses_pcbc', 'akses_migrasi'],
                    'SAP' => ['akses_sap_sync', 'akses_sync_buc', 'akses_sync_equipments'],
                    'Advance' => ['see_rekap_advance_017', 'see_rekap_advance_021', 'see_rekap_advance_022', 'see_rekap_advance_023', 'see_rekap_advance_025', 'see_rekap_advance_bo', 'see_rekap_advance_ho', 'rekap_dokumen_creation_bo', 'rekap_dokumen_creation_ho'],
                    'Giro' => ['akses_giro', 'create_outgoing'],
                    'Tax' => ['akses_wtax23'],
                    'Search' => ['akses_search', 'can_search'],
                    'Team' => ['see_team']
                ];

                $badges = [];
                foreach ($permissionGroups as $groupName => $groupPermissions) {
                    $hasPermission = $role->permissions->whereIn('name', $groupPermissions)->count() > 0;
                    if ($hasPermission) {
                        $badges[] = '<span class="badge badge-info">' . $groupName . '</span>';
                    }
                }

                return implode(' ', $badges);
            })
            ->addColumn('action', 'roles.action')
            ->addIndexColumn()
            ->rawColumns(['permissions_badges', 'action'])
            ->toJson();
    }
}
