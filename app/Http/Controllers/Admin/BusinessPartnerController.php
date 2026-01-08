<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SapBusinessPartner;
use App\Services\BusinessPartnerChangeDetectionService;
use App\Services\CustomerAutoSyncService;
use App\Services\SapMasterDataSyncService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BusinessPartnerController extends Controller
{
    public function __construct(
        protected SapMasterDataSyncService $syncService,
        protected BusinessPartnerChangeDetectionService $changeDetectionService,
        protected CustomerAutoSyncService $customerAutoSyncService
    ) {
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable($request);
        }

        return view('admin.business-partners.index');
    }

    protected function dataTable(Request $request)
    {
        $query = SapBusinessPartner::query();

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('active') && $request->active !== null) {
            $query->where('active', $request->active === '1');
        }

        return DataTables::of($query)
            ->addColumn('type_badge', function ($bp) {
                $badges = [
                    'cCustomer' => '<span class="badge badge-primary">Customer</span>',
                    'cSupplier' => '<span class="badge badge-success">Supplier</span>',
                    'cLead' => '<span class="badge badge-info">Lead</span>',
                ];
                return $badges[$bp->type] ?? '<span class="badge badge-secondary">' . $bp->type . '</span>';
            })
            ->editColumn('active', function ($bp) {
                return $bp->active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->editColumn('vat_liable', function ($bp) {
                return $bp->vat_liable
                    ? '<span class="badge badge-info">Yes</span>'
                    : '<span class="badge badge-secondary">No</span>';
            })
            ->editColumn('credit_limit', function ($bp) {
                return $bp->credit_limit ? number_format($bp->credit_limit, 2) : '-';
            })
            ->editColumn('balance', function ($bp) {
                if ($bp->balance === null) {
                    return '-';
                }
                $class = $bp->balance < 0 ? 'text-danger' : 'text-success';
                return '<span class="' . $class . '">' . number_format($bp->balance, 2) . '</span>';
            })
            ->editColumn('last_synced_at', function ($bp) {
                if (!$bp->last_synced_at) {
                    return '<span class="text-muted">Never</span>';
                }
                return $bp->last_synced_at->format('Y-m-d H:i:s');
            })
            ->rawColumns(['type_badge', 'active', 'vat_liable', 'balance', 'last_synced_at'])
            ->make(true);
    }

    public function syncFromSap(Request $request)
    {
        try {
            $result = $this->syncService->syncBusinessPartners();
            
            $autoSyncResult = null;
            if ($request->has('auto_sync_customers') && $request->auto_sync_customers) {
                $autoSyncResult = $this->customerAutoSyncService->syncAll();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully synced {$result['synced']} Business Partner(s)",
                    'synced' => $result['synced'],
                    'errors' => $result['errors'],
                    'customer_sync' => $autoSyncResult,
                ]);
            }

            $message = "Successfully synced {$result['synced']} Business Partner(s)";
            if ($autoSyncResult) {
                $message .= ". Customers: {$autoSyncResult['customers']['created']} created, {$autoSyncResult['customers']['updated']} updated. Vendors: {$autoSyncResult['vendors']['created']} created, {$autoSyncResult['vendors']['updated']} updated.";
            }

            return redirect()->route('admin.business-partners.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.business-partners.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function statistics()
    {
        $stats = $this->changeDetectionService->getStatistics();
        
        return response()->json($stats);
    }

    public function changes()
    {
        $changes = $this->changeDetectionService->detectChanges();
        
        return response()->json($changes);
    }

    public function syncCustomers(Request $request)
    {
        try {
            $result = $this->customerAutoSyncService->syncAll();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Customers and vendors synced successfully',
                    'data' => $result,
                ]);
            }

            return redirect()->route('admin.business-partners.index')
                ->with('success', 'Customers and vendors synced successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.business-partners.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
