<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UtilityDashboardController extends Controller
{
    public function index(): View
    {
        $periodeBulanIni = now()->format('Y-m');
        $today = now()->toDateString();

        $totalPerJenis = UtilityBill::query()
            ->join('utility_customers', 'utility_customers.id', '=', 'utility_bills.utility_customer_id')
            ->where('utility_bills.periode', $periodeBulanIni)
            ->select('utility_customers.jenis_utilitas', DB::raw('SUM(utility_bills.jumlah_tagihan) as total'))
            ->groupBy('utility_customers.jenis_utilitas')
            ->pluck('total', 'jenis_utilitas');

        $telat = UtilityBill::query()
            ->whereNull('tanggal_bayar')
            ->where('tanggal_jatuh_tempo', '<', $today)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(jumlah_tagihan), 0) as total')
            ->first();

        $belum = UtilityBill::query()
            ->whereNull('tanggal_bayar')
            ->where('tanggal_jatuh_tempo', '>=', $today)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(jumlah_tagihan), 0) as total')
            ->first();

        $lunas = UtilityBill::query()
            ->whereNotNull('tanggal_bayar')
            ->where('periode', $periodeBulanIni)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(jumlah_tagihan), 0) as total')
            ->first();

        $breakdown = [];
        foreach (UtilityCustomer::JENIS_UTILITAS as $key => $label) {
            $breakdown[$key] = [
                'label' => $label,
                'total' => (float) ($totalPerJenis[$key] ?? 0),
            ];
        }

        $totalBulanIni = array_sum(array_column($breakdown, 'total'));

        $chart = $this->buildMonthlyChart();

        return view('utilities.dashboard', [
            'periode_bulan_ini' => $periodeBulanIni,
            'total_bulan_ini' => $totalBulanIni,
            'breakdown' => $breakdown,
            'telat_count' => (int) $telat->count,
            'telat_total' => (float) $telat->total,
            'belum_count' => (int) $belum->count,
            'belum_total' => (float) $belum->total,
            'lunas_count' => (int) $lunas->count,
            'lunas_total' => (float) $lunas->total,
            'chart_labels' => $chart['labels'],
            'chart_category' => $chart['category'],
            'customers' => $chart['customers'],
            'chart_customer' => $chart['customer'],
        ]);
    }

    /**
     * Build 12-month line chart data: per category + per customer.
     *
     * @return array{labels: array<int,string>, category: array<string,array<int,float>>, customers: array<int,array<string,mixed>>, customer: array<int,array<string,mixed>>}
     */
    private function buildMonthlyChart(): array
    {
        $months = [];
        $labels = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $months[] = $m->format('Y-m');
            $labels[] = $m->format('M Y');
        }

        // Per-category totals (single grouped query)
        $rows = UtilityBill::query()
            ->join('utility_customers', 'utility_customers.id', '=', 'utility_bills.utility_customer_id')
            ->whereIn('utility_bills.periode', $months)
            ->selectRaw('utility_bills.periode as periode, utility_customers.jenis_utilitas as jenis, SUM(utility_bills.jumlah_tagihan) as total')
            ->groupBy('utility_bills.periode', 'utility_customers.jenis_utilitas')
            ->get();

        $pivot = [];
        foreach ($rows as $row) {
            $pivot[$row->periode][$row->jenis] = (float) $row->total;
        }

        $category = ['pln' => [], 'pdam' => [], 'telkom' => []];
        foreach ($months as $m) {
            foreach (array_keys($category) as $jenis) {
                $category[$jenis][] = $pivot[$m][$jenis] ?? 0;
            }
        }

        // Per-customer totals (single grouped query)
        $customerRows = UtilityBill::query()
            ->join('utility_customers', 'utility_customers.id', '=', 'utility_bills.utility_customer_id')
            ->whereIn('utility_bills.periode', $months)
            ->selectRaw('utility_customers.id as customer_id, utility_customers.id_pelanggan, utility_customers.nama, utility_customers.jenis_utilitas, utility_bills.periode, SUM(utility_bills.jumlah_tagihan) as total')
            ->groupBy('utility_customers.id', 'utility_customers.id_pelanggan', 'utility_customers.nama', 'utility_customers.jenis_utilitas', 'utility_bills.periode')
            ->get();

        $customerMonthly = [];
        foreach ($customerRows as $row) {
            $cid = (int) $row->customer_id;
            if (! isset($customerMonthly[$cid])) {
                $customerMonthly[$cid] = [
                    'id' => $cid,
                    'idpel' => $row->id_pelanggan,
                    'nama' => $row->nama,
                    'jenis' => $row->jenis_utilitas,
                    'data' => array_fill(0, 12, 0),
                ];
            }
            $idx = array_search($row->periode, $months, true);
            if ($idx !== false) {
                $customerMonthly[$cid]['data'][$idx] = (float) $row->total;
            }
        }

        $customerMonthly = array_values($customerMonthly);

        $customers = UtilityCustomer::query()
            ->select(['id', 'id_pelanggan', 'nama', 'jenis_utilitas'])
            ->orderBy('jenis_utilitas')
            ->orderBy('id_pelanggan')
            ->get()
            ->map(fn (UtilityCustomer $c) => [
                'id' => $c->id,
                'idpel' => $c->id_pelanggan,
                'nama' => $c->nama,
                'jenis' => $c->jenis_utilitas,
                'jenis_label' => UtilityCustomer::JENIS_UTILITAS[$c->jenis_utilitas] ?? $c->jenis_utilitas,
            ])
            ->values()
            ->all();

        return [
            'labels' => $labels,
            'category' => $category,
            'customers' => $customers,
            'customer' => $customerMonthly,
        ];
    }
}
