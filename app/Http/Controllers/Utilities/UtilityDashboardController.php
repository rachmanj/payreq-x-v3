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
        ]);
    }
}
