<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UtilityBillController extends Controller
{
    public function index(Request $request): View
    {
        $periodeDefault = now()->format('Y-m');
        $lastPeriode = UtilityBill::query()->max('periode');

        return view('utilities.bills.index', [
            'jenisList' => UtilityCustomer::JENIS_UTILITAS,
            'projects' => Project::orderBy('code')->get(),
            'periode' => $request->query('periode', $periodeDefault),
            'jenis' => $request->query('jenis'),
            'project' => $request->query('project'),
            'status' => $request->query('status'),
            'periode_sumber_default' => $lastPeriode ?? $periodeDefault,
            'periode_target_default' => $lastPeriode
                ? Carbon::createFromFormat('Y-m', $lastPeriode)->addMonth()->format('Y-m')
                : Carbon::createFromFormat('Y-m', $periodeDefault)->addMonth()->format('Y-m'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $mendekatiLimit = now()->addDays(3)->toDateString();

        $query = UtilityBill::query()
            ->with('customer.account')
            ->join('utility_customers', 'utility_customers.id', '=', 'utility_bills.utility_customer_id')
            ->select('utility_bills.*');

        if ($request->filled('periode')) {
            $query->where('utility_bills.periode', $request->periode);
        }

        if ($request->filled('jenis_utilitas')) {
            $query->where('utility_customers.jenis_utilitas', $request->jenis_utilitas);
        }

        if ($request->filled('project')) {
            $query->where('utility_customers.project', $request->project);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'lunas' => $query->whereNotNull('utility_bills.tanggal_bayar'),
                'telat' => $query->whereNull('utility_bills.tanggal_bayar')
                    ->where('utility_bills.tanggal_jatuh_tempo', '<', $today),
                'mendekati' => $query->whereNull('utility_bills.tanggal_bayar')
                    ->where('utility_bills.tanggal_jatuh_tempo', '>=', $today)
                    ->where('utility_bills.tanggal_jatuh_tempo', '<=', $mendekatiLimit),
                'belum' => $query->whereNull('utility_bills.tanggal_bayar')
                    ->where('utility_bills.tanggal_jatuh_tempo', '>', $mendekatiLimit),
                default => null,
            };
        }

        return datatables()->of($query)
            ->addColumn('id_pelanggan', fn (UtilityBill $bill) => $bill->customer->id_pelanggan ?? '-')
            ->addColumn('nama_customer', fn (UtilityBill $bill) => $bill->customer->nama ?? '-')
            ->addColumn('jenis_utilitas', fn (UtilityBill $bill) => UtilityCustomer::JENIS_UTILITAS[$bill->customer->jenis_utilitas ?? ''] ?? ($bill->customer->jenis_utilitas ?? '-'))
            ->editColumn('jumlah_tagihan', fn (UtilityBill $bill) => number_format($bill->jumlah_tagihan, 2))
            ->editColumn('tanggal_jatuh_tempo', fn (UtilityBill $bill) => $bill->tanggal_jatuh_tempo->format('d-M-Y'))
            ->addColumn('status_badge', function (UtilityBill $bill) {
                return '<span class="badge badge-'.$bill->status_color.'">'.$bill->status_label.'</span>';
            })
            ->addColumn('action', 'utilities.bills.action')
            ->rawColumns(['status_badge', 'action'])
            ->toJson();
    }

    public function create(): View
    {
        return view('utilities.bills.create', [
            'customers' => UtilityCustomer::active()->with('account')->orderBy('nama')->get(),
            'periodeDefault' => now()->format('Y-m'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'utility_customer_id' => 'required|exists:utility_customers,id',
            'periode' => 'required|date_format:Y-m',
            'jumlah_tagihan' => 'required|numeric|min:0',
            'tanggal_jatuh_tempo' => 'required|date',
            'nomor_tagihan' => 'nullable|string|max:255',
            'meter_awal' => 'nullable|integer|min:0',
            'meter_akhir' => 'nullable|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $exists = UtilityBill::query()
            ->where('utility_customer_id', $validated['utility_customer_id'])
            ->where('periode', $validated['periode'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'periode' => 'Tagihan untuk ID pelanggan dan periode ini sudah ada.',
            ]);
        }

        UtilityBill::create($validated);

        return redirect()->route('utilities.bills.index')->with('success', 'Tagihan berhasil disimpan.');
    }

    public function markPaid(Request $request, UtilityBill $bill): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_bayar' => 'nullable|date',
            'nomor_tagihan' => 'nullable|string|max:255',
        ]);

        $bill->update([
            'tanggal_bayar' => $validated['tanggal_bayar'] ?? now()->toDateString(),
            'nomor_tagihan' => $validated['nomor_tagihan'] ?? $bill->nomor_tagihan,
        ]);

        return back()->with('success', 'Tagihan ditandai lunas.');
    }

    public function unmarkPaid(UtilityBill $bill): RedirectResponse
    {
        $bill->update(['tanggal_bayar' => null]);

        return back()->with('success', 'Status lunas dibatalkan.');
    }

    public function copyLastMonth(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'periode_sumber' => 'required|date_format:Y-m',
            'periode_target' => 'required|date_format:Y-m|different:periode_sumber',
        ]);

        $sourceBills = UtilityBill::query()
            ->where('periode', $validated['periode_sumber'])
            ->get();

        $existingCustomerIds = UtilityBill::query()
            ->where('periode', $validated['periode_target'])
            ->pluck('utility_customer_id')
            ->all();

        $copied = 0;
        foreach ($sourceBills as $sourceBill) {
            if (in_array($sourceBill->utility_customer_id, $existingCustomerIds, true)) {
                continue;
            }

            UtilityBill::create([
                'utility_customer_id' => $sourceBill->utility_customer_id,
                'periode' => $validated['periode_target'],
                'jumlah_tagihan' => $sourceBill->jumlah_tagihan,
                'nomor_tagihan' => null,
                'tanggal_jatuh_tempo' => Carbon::createFromFormat('Y-m', $validated['periode_target'])
                    ->endOfMonth()
                    ->toDateString(),
                'tanggal_bayar' => null,
                'meter_awal' => $sourceBill->meter_akhir,
                'meter_akhir' => null,
                'keterangan' => $sourceBill->keterangan,
            ]);

            $copied++;
        }

        return back()->with('success', "Berhasil menyalin {$copied} tagihan ke periode {$validated['periode_target']}.");
    }
}
