<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CoretaxInputVat;
use App\Models\TaxPeriod;
use App\Services\CoretaxInputVatImportService;
use App\Services\TaxPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoretaxImportController extends Controller
{
    public function __construct(
        private CoretaxInputVatImportService $importService,
        private TaxPeriodService $taxPeriodService,
    ) {
        $this->middleware('permission:view_tax_monitoring');
    }

    public function index(Request $request): View
    {
        $masaPajak = $request->query('masa_pajak', now()->format('Y-m'));
        if (preg_match('/^\d{4}-\d{2}$/', (string) $masaPajak) !== 1) {
            $masaPajak = now()->format('Y-m');
        }

        $importedCount = CoretaxInputVat::query()->where('masa_pajak', $masaPajak)->count();
        $importedPpn = (float) CoretaxInputVat::query()->where('masa_pajak', $masaPajak)->sum('ppn');

        $masaOptions = TaxPeriod::query()
            ->where('tax_type', 'ppn')
            ->orderByDesc('masa_pajak')
            ->pluck('masa_pajak')
            ->merge(
                CoretaxInputVat::query()->distinct()->orderByDesc('masa_pajak')->pluck('masa_pajak')
            )
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if ($masaOptions === []) {
            $masaOptions = [$masaPajak];
        }

        return view('accounting.tax.ppn.import-coretax', [
            'masaPajak' => $masaPajak,
            'masaOptions' => $masaOptions,
            'importedCount' => $importedCount,
            'importedPpn' => $importedPpn,
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('manage_tax_monitoring'), 403);

        $validated = $request->validate([
            'masa_pajak' => 'required|regex:/^\d{4}-\d{2}$/',
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:20480',
        ]);

        $period = $this->taxPeriodService->findOrCreatePeriod($validated['masa_pajak']);
        if ($period->status === 'locked') {
            return response()->json([
                'success' => false,
                'message' => 'Masa pajak terkunci; impor tidak diizinkan.',
            ], 422);
        }

        $result = $this->importService->preview($request->file('file'), $validated['masa_pajak']);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_tax_monitoring'), 403);

        $validated = $request->validate([
            'preview_token' => 'required|string',
        ]);

        $result = $this->importService->commit($validated['preview_token'], auth()->user());

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->back()
                ->with('error', $result['message'] ?? 'Impor gagal.');
        }

        return redirect()
            ->route('accounting.tax.ppn.import-coretax.index', [
                'masa_pajak' => $result['masa_pajak'] ?? now()->format('Y-m'),
            ])
            ->with('success', $result['message']);
    }

    public function rekonsiliasi(Request $request): View
    {
        $masaPajak = $request->query('masa_pajak', now()->format('Y-m'));
        if (preg_match('/^\d{4}-\d{2}$/', (string) $masaPajak) !== 1) {
            $masaPajak = now()->format('Y-m');
        }

        $period = TaxPeriod::query()
            ->where('masa_pajak', $masaPajak)
            ->where('tax_type', 'ppn')
            ->first();

        $snapshot = $period?->snapshot_json;
        $threeWay = is_array($snapshot) ? ($snapshot['three_way'] ?? null) : null;

        $masaOptions = TaxPeriod::query()
            ->where('tax_type', 'ppn')
            ->whereNotNull('snapshot_json')
            ->orderByDesc('masa_pajak')
            ->pluck('masa_pajak')
            ->all();

        return view('accounting.tax.ppn.rekonsiliasi', [
            'masaPajak' => $masaPajak,
            'masaOptions' => $masaOptions,
            'period' => $period,
            'threeWay' => $threeWay,
            'snapshot' => $snapshot,
        ]);
    }
}
