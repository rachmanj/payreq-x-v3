<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\PpnSyncRun;
use App\Services\PpnInputVatSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PpnInputSyncController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_tax_monitoring');
    }

    public function index(): View
    {
        $lastRun = PpnSyncRun::lastRun();
        $lastSuccess = PpnSyncRun::lastSuccessful();
        $showAlert = PpnSyncRun::shouldShowHealthAlert();
        $history = PpnSyncRun::query()
            ->orderByDesc('started_at')
            ->limit(20)
            ->get();

        return view('accounting.tax.ppn.sync', compact('lastRun', 'lastSuccess', 'showAlert', 'history'));
    }

    public function runNow(PpnInputVatSyncService $syncService): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_tax_monitoring'), 403);

        $run = $syncService->sync(60, auth()->id());

        if ($run->status === 'success') {
            return redirect()
                ->route('accounting.tax.ppn.sync.index')
                ->with('success', $run->message);
        }

        return redirect()
            ->route('accounting.tax.ppn.sync.index')
            ->with('error', $run->message ?? 'Sinkronisasi gagal.');
    }
}
