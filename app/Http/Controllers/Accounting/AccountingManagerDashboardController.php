<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Payreq;
use App\Models\RealizationDetail;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountingManagerDashboardController extends Controller
{
    private const CACHE_TTL = 300;

    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $data = Cache::remember(
            "accounting_manager_dashboard_{$year}_{$month}",
            self::CACHE_TTL,
            fn () => $this->buildDashboardData($year, $month),
        );

        // Unit yang punya data realisasi (untuk dropdown chart Section C)
        $units = RealizationDetail::query()
            ->leftJoin('equipments', 'equipments.unit_code', '=', 'realization_details.unit_no')
            ->whereNotNull('realization_details.unit_no')
            ->where('realization_details.unit_no', '!=', '')
            ->groupBy('realization_details.unit_no', 'equipments.model', 'equipments.nomor_polisi')
            ->orderBy('realization_details.unit_no')
            ->selectRaw('realization_details.unit_no, equipments.model as unit_model, equipments.nomor_polisi as unit_nopol')
            ->get();

        return view('accounting.manager-dashboard.index', array_merge($data, [
            'month' => $month,
            'year' => $year,
            'units' => $units,
        ]));
    }

    public function advances(string $project): JsonResponse
    {
        $rows = Payreq::query()
            ->with('requestor')
            ->join('outgoings as o', 'o.payreq_id', '=', 'payreqs.id')
            ->where('payreqs.project', $project)
            ->where('payreqs.type', 'advance')
            ->whereNotIn('payreqs.status', ['canceled', 'rejected'])
            ->where('payreqs.approved_at', '>=', '2025-01-01')
            ->whereDoesntHave('realization')
            ->groupBy(
                'payreqs.id',
                'payreqs.nomor',
                'payreqs.user_id',
                'payreqs.amount',
            )
            ->selectRaw('payreqs.id, payreqs.nomor, payreqs.user_id, payreqs.amount, MIN(o.outgoing_date) as first_outgoing')
            ->get();

        return datatables()->of($rows)
            ->addColumn('requestor_name', fn ($row) => $row->requestor->name ?? 'n/a')
            ->editColumn('amount', fn ($row) => number_format($row->amount, 2))
            ->addColumn('aging_days', function ($row) {
                $age = Carbon::parse($row->first_outgoing)->diffInDays(now());

                $color = $age <= 30 ? 'success' : ($age <= 60 ? 'warning' : 'danger');

                return '<span class="badge badge-'.$color.'">'.$age.'</span>';
            })
            ->editColumn('first_outgoing', fn ($row) => Carbon::parse($row->first_outgoing)->format('d-M-Y'))
            ->rawColumns(['aging_days'])
            ->addIndexColumn()
            ->toJson();
    }

    public function unpaid(string $project): JsonResponse
    {
        $rows = Payreq::query()
            ->with('requestor')
            ->where('project', $project)
            ->whereIn('status', ['submitted', 'approved', 'draft', 'revise'])
            ->whereDoesntHave('outgoings')
            ->orderBy('due_date')
            ->get();

        return datatables()->of($rows)
            ->addColumn('requestor_name', fn ($payreq) => $payreq->requestor->name ?? 'n/a')
            ->editColumn('amount', fn ($payreq) => number_format($payreq->amount, 2))
            ->editColumn('status', fn ($payreq) => '<span class="badge badge-secondary">'.e($payreq->status).'</span>')
            ->editColumn('due_date', function ($payreq) {
                if (! $payreq->due_date) {
                    return '-';
                }

                return Carbon::parse($payreq->due_date)->format('d-M-Y');
            })
            ->rawColumns(['status'])
            ->addIndexColumn()
            ->toJson();
    }

    public function realizations(Request $request, string $project): JsonResponse
    {
        [$month, $year] = $this->resolvePeriod($request);

        $rows = RealizationDetail::query()
            ->with('account')
            ->where('project', $project)
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->orderByDesc('expense_date')
            ->get();

        return datatables()->of($rows)
            ->addColumn('account_info', function ($detail) {
                $account = $detail->account;

                if (! $account) {
                    return 'n/a';
                }

                return '<small>'.$account->account_number.'</small><br><small>'.e($account->account_name).'</small>';
            })
            ->editColumn('amount', fn ($detail) => number_format($detail->amount, 2))
            ->editColumn('expense_date', fn ($detail) => $detail->expense_date
                ? Carbon::parse($detail->expense_date)->format('d-M-Y')
                : '-')
            ->rawColumns(['account_info'])
            ->addIndexColumn()
            ->toJson();
    }

    public function unitExpense(string $unit): JsonResponse
    {
        return response()->json(Cache::remember(
            'accounting_manager_dashboard_unit_expense_'.$unit,
            self::CACHE_TTL,
            fn () => $this->buildUnitExpenseData($unit),
        ));
    }

    /**
     * Monthly sum per expense type (12 bulan terakhir) untuk 1 unit.
     *
     * @return array{labels: array<int,string>, datasets: array<int,array<string,mixed>>}
     */
    private function buildUnitExpenseData(string $unit): array
    {
        // hitung dari tanggal 1 bulan berjalan, hindari overflow (mis. 30-Feb-2026 -> Mar)
        $current = now()->startOfMonth();
        $start = $current->copy()->subMonths(11);

        $rows = RealizationDetail::query()
            ->where('unit_no', $unit)
            ->whereNotNull('expense_date')
            ->where('expense_date', '>=', $start)
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as month, COALESCE(NULLIF(type, ''), 'lainnya') as type, SUM(amount) as total")
            ->groupBy('month', 'type')
            ->get();

        $monthKeys = [];
        $labels = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthKeys[] = $current->copy()->subMonths($i)->format('Y-m');
            $labels[] = $current->copy()->subMonths($i)->format('M Y');
        }

        $typeConfig = [
            'fuel' => ['label' => 'Fuel', 'color' => '#0d9488'],
            'service' => ['label' => 'Service', 'color' => '#10b981'],
            'tax' => ['label' => 'Tax', 'color' => '#f59e0b'],
            'other' => ['label' => 'Other', 'color' => '#6366f1'],
            'lainnya' => ['label' => 'Lainnya', 'color' => '#94a3b8'],
        ];

        $datasets = $rows->pluck('type')
            ->unique()
            ->values()
            ->map(function (string $type) use ($rows, $monthKeys, $typeConfig) {
                $data = array_fill(0, count($monthKeys), 0);

                foreach ($rows->where('type', $type) as $row) {
                    $idx = array_search($row->month, $monthKeys, true);

                    if ($idx !== false) {
                        $data[$idx] = round((float) $row->total, 2);
                    }
                }

                $cfg = $typeConfig[$type] ?? ['label' => ucfirst((string) $type), 'color' => '#94a3b8'];

                return ['type' => $type, 'label' => $cfg['label'], 'color' => $cfg['color'], 'data' => $data];
            })
            ->values();

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    /**
     * @return array{month: int, year: int}
     */
    private function resolvePeriod(Request $request): array
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $month = max(1, min(12, $month));
        $year = max(2000, min(2100, $year));

        return [$month, $year];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardData(int $year, int $month): array
    {
        $outstandingByProject = $this->outstandingAdvanceByProject();
        $agingByProject = $this->outstandingAdvanceAging();
        $kebutuhanByProject = $this->kebutuhanDanaByProject();
        $realisasiBulan = $this->realisasiByProject($year, $month);
        $realisasiYtd = $this->realisasiYtdByProject($year);
        $topAccounts = $this->topAccountsByProject($year, $month);

        $saldoKas = Account::where('type', 'cash')->sum('app_balance');
        $outstandingAdvanceCnt = (int) $outstandingByProject->sum('cnt');
        $outstandingAdvanceTotal = round((float) $outstandingByProject->sum('total'), 2);
        $kebutuhanDanaCnt = (int) $kebutuhanByProject->sum('cnt');
        $kebutuhanDanaTotal = round((float) $kebutuhanByProject->sum('total'), 2);
        $realisasiBulanTotal = round((float) $realisasiBulan->sum('total'), 2);

        $fundingRows = $this->buildFundingRows(
            $outstandingByProject,
            $agingByProject,
            $kebutuhanByProject,
        );

        $expenseRows = $this->buildExpenseRows(
            $realisasiBulan,
            $realisasiYtd,
            $topAccounts,
        );

        return [
            'saldo_kas' => $saldoKas,
            'outstanding_advance_cnt' => $outstandingAdvanceCnt,
            'outstanding_advance_total' => $outstandingAdvanceTotal,
            'kebutuhan_dana_cnt' => $kebutuhanDanaCnt,
            'kebutuhan_dana_total' => $kebutuhanDanaTotal,
            'realisasi_bulan_total' => $realisasiBulanTotal,
            'funding_rows' => $fundingRows,
            'expense_rows' => $expenseRows,
        ];
    }

    /**
     * @return Collection<int, object{project: string, cnt: int, total: int}>
     */
    private function outstandingAdvanceByProject(): Collection
    {
        return Payreq::query()
            ->where('type', 'advance')
            ->whereHas('outgoings')
            ->whereDoesntHave('realization')
            ->whereNotIn('status', ['canceled', 'rejected'])
            ->where('approved_at', '>=', '2025-01-01')
            ->groupBy('project')
            ->selectRaw('project, COUNT(*) as cnt, SUM(amount) as total')
            ->get();
    }

    /**
     * @return Collection<string, Collection<int, object>>
     */
    private function outstandingAdvanceAging(): Collection
    {
        return DB::table('payreqs as p')
            ->join('outgoings as o', 'o.payreq_id', '=', 'p.id')
            ->where('p.type', 'advance')
            ->whereNotIn('p.status', ['canceled', 'rejected'])
            ->where('p.approved_at', '>=', '2025-01-01')
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)->from('realizations as r')->whereColumn('r.payreq_id', 'p.id');
            })
            ->groupBy('p.id', 'p.project', 'p.amount')
            ->selectRaw('p.project, p.id, p.amount, MIN(o.outgoing_date) as first_outgoing')
            ->get()
            ->map(function ($row) {
                $age = Carbon::parse($row->first_outgoing)->diffInDays(now());
                $row->aging_bucket = $age <= 30 ? '0-30' : ($age <= 60 ? '31-60' : ($age <= 90 ? '61-90' : '>90'));

                return $row;
            })
            ->groupBy('project');
    }

    /**
     * @return Collection<int, object{project: string, cnt: int, total: int}>
     */
    private function kebutuhanDanaByProject(): Collection
    {
        return Payreq::query()
            ->whereIn('status', ['submitted', 'approved', 'draft', 'revise'])
            ->whereDoesntHave('outgoings')
            ->groupBy('project')
            ->selectRaw('project, COUNT(*) as cnt, SUM(amount) as total')
            ->get();
    }

    /**
     * @return Collection<int, object{project: string, cnt: int, total: int}>
     */
    private function realisasiByProject(int $year, int $month): Collection
    {
        return RealizationDetail::query()
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->groupBy('project')
            ->selectRaw('project, COUNT(*) as cnt, SUM(amount) as total')
            ->get();
    }

    /**
     * @return Collection<int, object{project: string, total: int}>
     */
    private function realisasiYtdByProject(int $year): Collection
    {
        return RealizationDetail::query()
            ->whereYear('expense_date', $year)
            ->groupBy('project')
            ->selectRaw('project, SUM(amount) as total')
            ->get();
    }

    /**
     * @return Collection<string, Collection<int, object>>
     */
    private function topAccountsByProject(int $year, int $month, int $limit = 3): Collection
    {
        return RealizationDetail::query()
            ->join('accounts', 'accounts.id', '=', 'realization_details.account_id')
            ->whereYear('realization_details.expense_date', $year)
            ->whereMonth('realization_details.expense_date', $month)
            ->groupBy(
                'realization_details.project',
                'realization_details.account_id',
                'accounts.account_number',
                'accounts.account_name',
            )
            ->selectRaw('realization_details.project, accounts.account_number, accounts.account_name, SUM(realization_details.amount) as total')
            ->orderByDesc('total')
            ->get()
            ->groupBy('project')
            ->map(fn (Collection $items) => $items->take($limit)->values());
    }

    /**
     * @param  Collection<int, object{project: string, cnt: int, total: int}>  $outstandingByProject
     * @param  Collection<string, Collection<int, object>>  $agingByProject
     * @param  Collection<int, object{project: string, cnt: int, total: int}>  $kebutuhanByProject
     * @return array<int, array<string, mixed>>
     */
    private function buildFundingRows(
        Collection $outstandingByProject,
        Collection $agingByProject,
        Collection $kebutuhanByProject,
    ): array {
        $outstandingMap = $outstandingByProject->keyBy('project');
        $kebutuhanMap = $kebutuhanByProject->keyBy('project');

        $projects = $outstandingByProject->pluck('project')
            ->merge($kebutuhanByProject->pluck('project'))
            ->unique()
            ->sort()
            ->values();

        return $projects->map(function (string $project) use ($outstandingMap, $agingByProject, $kebutuhanMap) {
            $outstanding = $outstandingMap->get($project);
            $kebutuhan = $kebutuhanMap->get($project);
            $agingItems = $agingByProject->get($project, collect());

            $agingSummary = $agingItems
                ->groupBy('aging_bucket')
                ->map(fn (Collection $items) => $items->count())
                ->sortKeys();

            return [
                'project' => $project,
                'outstanding_cnt' => (int) ($outstanding->cnt ?? 0),
                'outstanding_total' => round((float) ($outstanding->total ?? 0), 2),
                'aging_summary' => $agingSummary,
                'kebutuhan_cnt' => (int) ($kebutuhan->cnt ?? 0),
                'kebutuhan_total' => round((float) ($kebutuhan->total ?? 0), 2),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, object{project: string, cnt: int, total: int}>  $realisasiBulan
     * @param  Collection<int, object{project: string, total: int}>  $realisasiYtd
     * @param  Collection<string, Collection<int, object>>  $topAccounts
     * @return array<int, array<string, mixed>>
     */
    private function buildExpenseRows(
        Collection $realisasiBulan,
        Collection $realisasiYtd,
        Collection $topAccounts,
    ): array {
        $bulanMap = $realisasiBulan->keyBy('project');
        $ytdMap = $realisasiYtd->keyBy('project');

        $projects = $realisasiBulan->pluck('project')
            ->merge($realisasiYtd->pluck('project'))
            ->unique()
            ->sort()
            ->values();

        return $projects->map(function (string $project) use ($bulanMap, $ytdMap, $topAccounts) {
            $bulan = $bulanMap->get($project);
            $ytd = $ytdMap->get($project);
            $accounts = $topAccounts->get($project, collect());

            return [
                'project' => $project,
                'bulan_cnt' => (int) ($bulan->cnt ?? 0),
                'bulan_total' => round((float) ($bulan->total ?? 0), 2),
                'ytd_total' => round((float) ($ytd->total ?? 0), 2),
                'top_accounts' => $accounts,
            ];
        })->values()->all();
    }
}
