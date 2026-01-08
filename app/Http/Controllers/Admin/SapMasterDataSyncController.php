<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SapMasterDataSyncService;
use Illuminate\Http\Request;

class SapMasterDataSyncController extends Controller
{
    public function __construct(
        protected SapMasterDataSyncService $syncService
    ) {
    }

    public function index()
    {
        return view('admin.sap-master-data-sync.index');
    }

    public function syncAll(Request $request)
    {
        try {
            $results = $this->syncService->syncAll();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'All master data synced successfully',
                    'results' => $results,
                ]);
            }

            $message = 'All master data synced successfully: ';
            $messages = [];
            foreach ($results as $type => $result) {
                $messages[] = ucfirst(str_replace('_', ' ', $type)) . ": {$result['synced']} synced";
            }

            return redirect()->route('admin.sap-master-data-sync.index')
                ->with('success', $message . implode(', ', $messages));
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.sap-master-data-sync.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function syncProjects(Request $request)
    {
        try {
            $result = $this->syncService->syncProjects();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully synced {$result['synced']} Project(s)",
                    'synced' => $result['synced'],
                    'errors' => $result['errors'],
                ]);
            }

            return redirect()->route('admin.sap-master-data-sync.index')
                ->with('success', "Successfully synced {$result['synced']} Project(s)");
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.sap-master-data-sync.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function syncCostCenters(Request $request)
    {
        try {
            $result = $this->syncService->syncCostCenters();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully synced {$result['synced']} Cost Center(s)",
                    'synced' => $result['synced'],
                    'errors' => $result['errors'],
                ]);
            }

            return redirect()->route('admin.sap-master-data-sync.index')
                ->with('success', "Successfully synced {$result['synced']} Cost Center(s)");
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.sap-master-data-sync.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function syncAccounts(Request $request)
    {
        try {
            $result = $this->syncService->syncAccounts();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully synced {$result['synced']} Account(s)",
                    'synced' => $result['synced'],
                    'errors' => $result['errors'],
                ]);
            }

            return redirect()->route('admin.sap-master-data-sync.index')
                ->with('success', "Successfully synced {$result['synced']} Account(s)");
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.sap-master-data-sync.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function syncBusinessPartners(Request $request)
    {
        try {
            $result = $this->syncService->syncBusinessPartners();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully synced {$result['synced']} Business Partner(s)",
                    'synced' => $result['synced'],
                    'errors' => $result['errors'],
                ]);
            }

            return redirect()->route('admin.sap-master-data-sync.index')
                ->with('success', "Successfully synced {$result['synced']} Business Partner(s)");
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.sap-master-data-sync.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
