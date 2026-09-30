<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\PpnMasaPajakExport;
use App\Exports\PpnMissingFakturExport;
use App\Http\Controllers\Controller;
use App\Models\CoretaxInputVat;
use App\Models\Faktur;
use App\Models\TaxPeriod;
use App\Services\PpnReconciliationService;
use App\Services\TaxPeriodService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PpnMonitoringController extends Controller
{
    public function __construct(
        private PpnReconciliationService $reconciliationService,
        private TaxPeriodService $taxPeriodService,
    ) {
        $this->middleware('permission:view_tax_monitoring');
    }

    public function index(Request $request): View
    {
        $masaPajak = $this->resolveMasaPajak($request->query('masa_pajak'));
        $period = $this->taxPeriodService->findOrCreatePeriod($masaPajak);

        $masaOptions = $this->masaPajakOptions();

        $periods = TaxPeriod::query()
            ->where('tax_type', 'ppn')
            ->orderByDesc('masa_pajak')
            ->limit(24)
            ->get();

        $appPk = (float) Faktur::query()->where('type', 'sales')->where('masa_pajak', $masaPajak)->sum('ppn');
        $appPm = (float) Faktur::query()->where('type', 'purchase')->where('masa_pajak', $masaPajak)->sum('ppn');

        $coretaxPmLive = (float) CoretaxInputVat::query()->where('masa_pajak', $masaPajak)->sum('ppn');
        $coretaxImported = CoretaxInputVat::query()->where('masa_pajak', $masaPajak)->exists();
        $diffCoretaxAppLive = $coretaxImported ? round($coretaxPmLive - $appPm, 2) : null;

        return view('accounting.tax.ppn.index', compact(
            'masaPajak',
            'period',
            'periods',
            'masaOptions',
            'appPk',
            'appPm',
            'coretaxPmLive',
            'coretaxImported',
            'diffCoretaxAppLive',
        ));
    }

    public function data(Request $request): JsonResponse
    {
        $type = $request->query('type', 'periods');

        if ($type === 'periods') {
            $periods = TaxPeriod::query()
                ->where('tax_type', 'ppn')
                ->orderByDesc('masa_pajak')
                ->get();

            return datatables()->of($periods)
                ->addColumn('status_label', fn (TaxPeriod $p) => $this->statusBadge($p->status))
                ->addColumn('pk_display', fn (TaxPeriod $p) => $this->formatMoney($p->pk_total))
                ->addColumn('pm_display', fn (TaxPeriod $p) => $this->formatMoney($p->pm_total))
                ->addColumn('kb_lb_display', fn (TaxPeriod $p) => $this->formatMoney($p->kb_lb))
                ->addColumn('actions', function (TaxPeriod $p) {
                    $url = route('accounting.tax.ppn.index', ['masa_pajak' => $p->masa_pajak]);

                    return '<a href="'.$url.'" class="btn btn-xs btn-outline-primary">Buka</a>';
                })
                ->rawColumns(['status_label', 'actions'])
                ->toJson();
        }

        return response()->json(['error' => 'Tipe data tidak dikenali'], 400);
    }

    public function masukan(Request $request): View
    {
        $masaPajak = $this->resolveMasaPajak($request->query('masa_pajak'));

        return view('accounting.tax.ppn.masukan', [
            'masaPajak' => $masaPajak,
            'masaOptions' => $this->masaPajakOptions(),
        ]);
    }

    public function masukanData(Request $request): JsonResponse
    {
        $masaPajak = $this->resolveMasaPajak($request->query('masa_pajak'));

        $query = Faktur::query()
            ->with('customer')
            ->where('type', 'purchase')
            ->where('masa_pajak', $masaPajak);

        return datatables()->of($query)
            ->addColumn('vendor', fn (Faktur $f) => '<small>'.e($f->customer->name).'</small>'
                .($f->npwp_lawan ? '<br><small class="text-muted">'.e($f->npwp_lawan).'</small>' : ''))
            ->addColumn('faktur', function (Faktur $f) {
                $tgl = $f->faktur_date ? date('d-M-Y', strtotime($f->faktur_date)) : '—';

                return '<small>'.e($f->faktur_no ?? '—').'</small><br><small>'.$tgl.'</small>';
            })
            ->addColumn('amount', function (Faktur $f) {
                return '<small>DPP: '.number_format((float) $f->dpp, 0, ',', '.').'</small><br>'
                    .'<small>PPN: '.number_format((float) $f->ppn, 0, ',', '.').'</small>';
            })
            ->addColumn('ppn_rate_display', fn (Faktur $f) => $f->ppn_rate !== null ? number_format((float) $f->ppn_rate, 2, ',', '.').'%' : '—')
            ->addColumn('validation_label', fn (Faktur $f) => $this->validationBadge($f->validation_status))
            ->addColumn('coretax_label', fn (Faktur $f) => $this->coretaxBadge($f->coretax_status))
            ->addColumn('sap_match', fn (Faktur $f) => $this->sapMatchBadge($f))
            ->addColumn('actions', function (Faktur $f) use ($masaPajak) {
                if (! auth()->user()?->can('manage_tax_monitoring')) {
                    return '';
                }

                $period = TaxPeriod::query()
                    ->where('masa_pajak', $masaPajak)
                    ->where('tax_type', 'ppn')
                    ->first();
                if ($period && ! $period->isMutable()) {
                    return '<small class="text-muted">Masa terkunci</small>';
                }

                $validUrl = route('accounting.tax.ppn.masukan.validate', $f);
                $invalidUrl = route('accounting.tax.ppn.masukan.validate', $f);

                return '<form method="POST" action="'.$validUrl.'" class="d-inline">'
                    .csrf_field()
                    .'<input type="hidden" name="validation_status" value="valid">'
                    .'<button type="submit" class="btn btn-xs btn-success" onclick="return confirm(\'Tandai valid?\')">Valid</button>'
                    .'</form> '
                    .'<form method="POST" action="'.$invalidUrl.'" class="d-inline">'
                    .csrf_field()
                    .'<input type="hidden" name="validation_status" value="tidak_valid">'
                    .'<button type="submit" class="btn btn-xs btn-danger" onclick="return confirm(\'Tandai tidak valid?\')">Tidak valid</button>'
                    .'</form>';
            })
            ->rawColumns(['vendor', 'faktur', 'amount', 'validation_label', 'coretax_label', 'sap_match', 'actions'])
            ->toJson();
    }

    public function validateMasukan(Request $request, Faktur $faktur): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_tax_monitoring'), 403);

        if ($faktur->type !== 'purchase') {
            return redirect()->back()->with('error', 'Hanya faktur masukan yang bisa divalidasi di sini.');
        }

        $request->validate([
            'validation_status' => 'required|in:valid,tidak_valid',
        ]);

        if ($faktur->masa_pajak) {
            $period = TaxPeriod::query()
                ->where('masa_pajak', $faktur->masa_pajak)
                ->where('tax_type', 'ppn')
                ->first();
            if ($period && ! $period->isMutable()) {
                return redirect()->back()->with('error', 'Masa pajak sudah dilaporkan atau terkunci.');
            }
        }

        $faktur->update([
            'validation_status' => $request->input('validation_status'),
        ]);

        return redirect()->back()->with('success', 'Status validasi diperbarui.');
    }

    public function keluaran(Request $request): View
    {
        $masaPajak = $this->resolveMasaPajak($request->query('masa_pajak'));

        return view('accounting.tax.ppn.keluaran', [
            'masaPajak' => $masaPajak,
            'masaOptions' => $this->masaPajakOptions(),
        ]);
    }

    public function keluaranData(Request $request): JsonResponse
    {
        $masaPajak = $this->resolveMasaPajak($request->query('masa_pajak'));

        $query = Faktur::query()
            ->with('customer')
            ->where('type', 'sales')
            ->where('masa_pajak', $masaPajak);

        return datatables()->of($query)
            ->addColumn('customer_name', fn (Faktur $f) => '<small>'.e($f->customer->name).'</small>')
            ->addColumn('faktur', function (Faktur $f) {
                $tgl = $f->faktur_date ? date('d-M-Y', strtotime($f->faktur_date)) : '—';

                return '<small>'.e($f->faktur_no ?? '—').'</small><br><small>'.$tgl.'</small>';
            })
            ->addColumn('amount', function (Faktur $f) {
                return '<small>DPP: '.number_format((float) $f->dpp, 0, ',', '.').'</small><br>'
                    .'<small>PPN: '.number_format((float) $f->ppn, 0, ',', '.').'</small>';
            })
            ->addColumn('sap_status', fn (Faktur $f) => $this->sapSubmissionBadge($f))
            ->addColumn('coretax_label', fn (Faktur $f) => $this->coretaxBadge($f->coretax_status))
            ->addColumn('umur_hari', function (Faktur $f) {
                if (! $f->faktur_date) {
                    return '—';
                }

                return (string) Carbon::parse($f->faktur_date)->diffInDays(now());
            })
            ->rawColumns(['customer_name', 'faktur', 'amount', 'sap_status', 'coretax_label'])
            ->toJson();
    }

    public function belumDiterima(Request $request): View
    {
        $masaPajak = $this->resolveMasaPajak($request->query('masa_pajak'));
        $period = TaxPeriod::query()
            ->where('masa_pajak', $masaPajak)
            ->where('tax_type', 'ppn')
            ->first();

        $groups = $this->groupedExposure($period);

        return view('accounting.tax.ppn.belum-diterima', [
            'masaPajak' => $masaPajak,
            'period' => $period,
            'groups' => $groups,
            'masaOptions' => $this->masaPajakOptions(),
        ]);
    }

    public function belumDiterimaExport(Request $request): BinaryFileResponse
    {
        $masaPajak = $this->resolveMasaPajak($request->query('masa_pajak'));
        $period = TaxPeriod::query()
            ->where('masa_pajak', $masaPajak)
            ->where('tax_type', 'ppn')
            ->first();

        $groups = $this->groupedExposure($period);
        $filename = 'ppn-belum-diterima-'.$masaPajak.'.xlsx';

        return Excel::download(new PpnMissingFakturExport($masaPajak, $groups), $filename);
    }

    public function periksa(Request $request): View
    {
        $masaPajak = $request->query('masa_pajak');

        return view('accounting.tax.ppn.periksa', [
            'masaPajak' => $masaPajak,
            'masaOptions' => $this->masaPajakOptions(),
        ]);
    }

    public function periksaData(Request $request): JsonResponse
    {
        $masaFilter = $request->query('masa_pajak');

        $duplicateKeys = Faktur::query()
            ->select('masa_pajak', 'type', 'faktur_no')
            ->whereNotNull('faktur_no')
            ->where('faktur_no', '!=', '')
            ->when($masaFilter, fn ($q) => $q->where('masa_pajak', $masaFilter))
            ->groupBy('masa_pajak', 'type', 'faktur_no')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->map(fn ($row) => $row->masa_pajak.'|'.$row->type.'|'.$row->faktur_no)
            ->all();

        $query = Faktur::query()
            ->with('customer')
            ->where(function ($q) use ($duplicateKeys) {
                $q->where(function ($inner) {
                    $inner->whereNull('ppn_rate')->orWhereNull('dpp_calculated');
                })
                    ->orWhereNull('faktur_date')
                    ->orWhere('faktur_date', '');

                if ($duplicateKeys !== []) {
                    $q->orWhere(function ($dup) use ($duplicateKeys) {
                        foreach ($duplicateKeys as $key) {
                            [$masa, $type, $no] = explode('|', $key, 3);
                            $dup->orWhere(function ($one) use ($masa, $type, $no) {
                                $one->where('masa_pajak', $masa)
                                    ->where('type', $type)
                                    ->where('faktur_no', $no);
                            });
                        }
                    });
                }
            })
            ->when($masaFilter, fn ($q) => $q->where('masa_pajak', $masaFilter));

        return datatables()->of($query)
            ->addColumn('issue', function (Faktur $f) use ($duplicateKeys) {
                $issues = [];
                if ($f->ppn_rate === null || $f->dpp_calculated === null) {
                    $issues[] = 'Tarif/DPP tidak dapat ditentukan';
                }
                if (! $f->faktur_date) {
                    $issues[] = 'Tanpa tanggal faktur';
                }
                $key = $f->masa_pajak.'|'.$f->type.'|'.$f->faktur_no;
                if ($f->faktur_no && in_array($key, $duplicateKeys, true)) {
                    $issues[] = 'Nomor faktur duplikat dalam masa';
                }

                return implode('; ', $issues);
            })
            ->addColumn('vendor', fn (Faktur $f) => e($f->customer->name))
            ->addColumn('type_label', fn (Faktur $f) => $f->type === 'purchase' ? 'Masukan' : 'Keluaran')
            ->rawColumns(['issue'])
            ->toJson();
    }

    public function reconcile(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_tax_monitoring'), 403);

        $request->validate([
            'masa_pajak' => 'required|regex:/^\d{4}-\d{2}$/',
        ]);

        $masaPajak = $request->input('masa_pajak');
        $period = $this->taxPeriodService->findOrCreatePeriod($masaPajak);

        if ($period->status === 'locked') {
            return redirect()->back()->with('error', 'Masa pajak terkunci; jalankan buka kunci terlebih dahulu.');
        }

        try {
            $result = $this->reconciliationService->reconcile($masaPajak);
            $this->taxPeriodService->saveReconciliationSnapshot($period, $result);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Rekonsiliasi gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('accounting.tax.ppn.index', ['masa_pajak' => $masaPajak])
            ->with('success', 'Rekonsiliasi selesai; snapshot disimpan.');
    }

    public function preparePeriod(TaxPeriod $period): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_tax_monitoring'), 403);

        try {
            $this->taxPeriodService->prepare($period, auth()->user());
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Masa pajak ditandai disiapkan.');
    }

    public function approvePeriod(TaxPeriod $period): RedirectResponse
    {
        abort_unless(auth()->user()?->can('approve_tax_period'), 403);

        try {
            $this->taxPeriodService->approve($period, auth()->user());
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Masa pajak disetujui.');
    }

    public function closePeriod(TaxPeriod $period): RedirectResponse
    {
        abort_unless(auth()->user()?->can('approve_tax_period'), 403);

        try {
            $updated = $this->taxPeriodService->close($period, auth()->user());
            $message = $updated->status === 'filed'
                ? 'Masa pajak ditandai sudah dilaporkan (SPT).'
                : 'Masa pajak terkunci.';
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', $message);
    }

    public function reopenPeriod(Request $request, TaxPeriod $period): RedirectResponse
    {
        abort_unless(auth()->user()?->can('approve_tax_period'), 403);

        $request->validate([
            'reason' => 'required|string|min:5|max:2000',
        ]);

        try {
            $this->taxPeriodService->reopen($period, auth()->user(), $request->input('reason'));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Masa pajak dibuka kembali.');
    }

    public function exportMasa(string $masa): BinaryFileResponse
    {
        if (preg_match('/^\d{4}-\d{2}$/', $masa) !== 1) {
            abort(404);
        }

        $period = TaxPeriod::query()
            ->where('masa_pajak', $masa)
            ->where('tax_type', 'ppn')
            ->first();

        $filename = 'ppn-ringkasan-'.$masa.'.xlsx';

        return Excel::download(new PpnMasaPajakExport($masa, $period), $filename);
    }

    public function cetak(string $masa): View
    {
        if (preg_match('/^\d{4}-\d{2}$/', $masa) !== 1) {
            abort(404);
        }

        $period = $this->taxPeriodService->findOrCreatePeriod($masa);
        $period->load(['preparedBy', 'approvedBy', 'closedBy']);

        $appPk = (float) Faktur::query()->where('type', 'sales')->where('masa_pajak', $masa)->sum('ppn');
        $appPm = (float) Faktur::query()->where('type', 'purchase')->where('masa_pajak', $masa)->sum('ppn');

        return view('accounting.tax.ppn.cetak', compact('masa', 'period', 'appPk', 'appPm'));
    }

    /**
     * @return list<string>
     */
    private function masaPajakOptions(): array
    {
        $fromFaktur = Faktur::query()
            ->whereNotNull('masa_pajak')
            ->distinct()
            ->orderByDesc('masa_pajak')
            ->pluck('masa_pajak')
            ->all();

        $fromPeriods = TaxPeriod::query()
            ->where('tax_type', 'ppn')
            ->orderByDesc('masa_pajak')
            ->pluck('masa_pajak')
            ->all();

        $merged = array_values(array_unique(array_merge($fromPeriods, $fromFaktur)));
        rsort($merged);

        if ($merged === []) {
            $merged[] = now()->format('Y-m');
        }

        return $merged;
    }

    private function resolveMasaPajak(?string $masa): string
    {
        if ($masa && preg_match('/^\d{4}-\d{2}$/', $masa) === 1) {
            return $masa;
        }

        $options = $this->masaPajakOptions();

        return $options[0];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function groupedExposure(?TaxPeriod $period): array
    {
        $rows = $period?->snapshot_json['missing_faktur_exposure'] ?? [];
        if ($rows === []) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $code = (string) ($row['card_code'] ?? '');
            if (! isset($grouped[$code])) {
                $grouped[$code] = [
                    'card_code' => $code,
                    'card_name' => $row['card_name'] ?? '',
                    'invoice_count' => 0,
                    'total_vat' => 0.0,
                    'max_age_days' => 0,
                    'rows' => [],
                ];
            }
            $age = 0;
            if (! empty($row['doc_date'])) {
                $age = (int) Carbon::parse($row['doc_date'])->diffInDays(now());
            }
            $grouped[$code]['invoice_count']++;
            $grouped[$code]['total_vat'] += (float) ($row['vat_sum'] ?? 0);
            $grouped[$code]['max_age_days'] = max($grouped[$code]['max_age_days'], $age);
            $grouped[$code]['rows'][] = $row;
        }

        usort($grouped, fn (array $a, array $b): int => $b['total_vat'] <=> $a['total_vat']);

        return array_values($grouped);
    }

    private function formatMoney(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format((float) $value, 0, ',', '.');
    }

    private function statusBadge(string $status): string
    {
        $labels = [
            'open' => ['Terbuka', 'secondary'],
            'prepared' => ['Disiapkan', 'info'],
            'approved' => ['Disetujui', 'primary'],
            'filed' => ['Dilaporkan', 'warning'],
            'locked' => ['Terkunci', 'dark'],
        ];
        [$text, $color] = $labels[$status] ?? [$status, 'secondary'];

        return '<span class="badge badge-'.$color.'">'.$text.'</span>';
    }

    private function validationBadge(?string $status): string
    {
        $map = [
            'belum_diperiksa' => ['Belum diperiksa', 'secondary'],
            'valid' => ['Valid', 'success'],
            'tidak_valid' => ['Tidak valid', 'danger'],
            'diganti' => ['Diganti', 'warning'],
        ];
        [$text, $color] = $map[$status] ?? [$status ?? '—', 'secondary'];

        return '<span class="badge badge-'.$color.'">'.$text.'</span>';
    }

    private function coretaxBadge(?string $status): string
    {
        $map = [
            'belum_diketahui' => ['Belum diketahui', 'secondary'],
            'approved' => ['Approved', 'success'],
            'reject' => ['Reject', 'danger'],
            'diganti' => ['Diganti', 'warning'],
        ];
        [$text, $color] = $map[$status] ?? [$status ?? '—', 'secondary'];

        return '<span class="badge badge-'.$color.'">'.$text.'</span>';
    }

    private function sapMatchBadge(Faktur $f): string
    {
        if ($f->matched_doc_num) {
            return '<span class="badge badge-success" title="Doc '.$f->matched_doc_num.'">Cocok SAP</span>';
        }
        if ($f->doc_num) {
            return '<span class="badge badge-warning">Sebagian</span>';
        }

        return '<span class="badge badge-danger">Belum cocok</span>';
    }

    private function sapSubmissionBadge(Faktur $f): string
    {
        $status = $f->sap_submission_status ?? 'pending';
        $map = [
            'pending' => ['Pending', 'secondary'],
            'ar_created' => ['AR dibuat', 'info'],
            'je_created' => ['JE dibuat', 'info'],
            'completed' => ['Selesai', 'success'],
            'failed' => ['Gagal', 'danger'],
        ];
        [$text, $color] = $map[$status] ?? [$status, 'secondary'];
        $extra = $f->sap_ar_doc_num ? '<br><small>'.e($f->sap_ar_doc_num).'</small>' : '';

        return '<span class="badge badge-'.$color.'">'.$text.'</span>'.$extra;
    }
}
