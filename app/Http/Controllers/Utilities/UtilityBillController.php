<?php

namespace App\Http\Controllers\Utilities;

use App\Exceptions\OpenRouterException;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use App\Services\OpenRouterService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->addColumn('tipe_badge', function (UtilityBill $bill) {
                $tipe = $bill->customer->tipe ?? 'postpaid';
                if ($tipe === 'prepaid') {
                    return '<span class="badge badge-info">Token</span>';
                }

                return '<span class="badge badge-secondary">Pascabayar</span>';
            })
            ->addColumn('nomor_token_display', fn (UtilityBill $bill) => $bill->nomor_token
                ? '<small>'.e($bill->nomor_token).'</small>'
                : '-')
            ->editColumn('jumlah_tagihan', fn (UtilityBill $bill) => number_format($bill->jumlah_tagihan, 2))
            ->editColumn('tanggal_jatuh_tempo', fn (UtilityBill $bill) => $bill->tanggal_jatuh_tempo
                ? $bill->tanggal_jatuh_tempo->format('d-M-Y')
                : '-')
            ->addColumn('status_badge', function (UtilityBill $bill) {
                return '<span class="badge badge-'.$bill->status_color.'">'.$bill->status_label.'</span>';
            })
            ->addColumn('action', 'utilities.bills.action')
            ->rawColumns(['status_badge', 'tipe_badge', 'nomor_token_display', 'action'])
            ->toJson();
    }

    public function create(): View
    {
        return view('utilities.bills.create', [
            'customers' => UtilityCustomer::active()->with('account')->orderBy('nama')->get(),
            'tipeList' => UtilityCustomer::TIPE,
            'periodeDefault' => now()->format('Y-m'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'utility_customer_id' => 'required|exists:utility_customers,id',
            'periode' => 'required|date_format:Y-m',
            'jumlah_tagihan' => 'required|numeric|min:0',
            'tipe' => 'required|in:postpaid,prepaid',
            'tanggal_jatuh_tempo' => 'nullable|required_if:tipe,postpaid|date',
            'tanggal_bayar' => 'nullable|date',
            'nomor_token' => 'nullable|string|max:255',
            'nomor_tagihan' => 'nullable|string|max:255',
            'meter_awal' => 'nullable|integer|min:0',
            'meter_akhir' => 'nullable|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $customer = UtilityCustomer::query()->findOrFail($validated['utility_customer_id']);

        if ($customer->tipe !== $validated['tipe']) {
            return back()->withInput()->withErrors([
                'tipe' => 'Tipe tagihan tidak sesuai dengan tipe ID pelanggan.',
            ]);
        }

        if ($customer->tipe === 'postpaid') {
            $exists = UtilityBill::query()
                ->where('utility_customer_id', $validated['utility_customer_id'])
                ->where('periode', $validated['periode'])
                ->exists();

            if ($exists) {
                return back()->withInput()->withErrors([
                    'periode' => 'Tagihan bulan ini sudah ada.',
                ]);
            }
        }

        $isPrepaid = $customer->tipe === 'prepaid';

        UtilityBill::create([
            'utility_customer_id' => $validated['utility_customer_id'],
            'periode' => $validated['periode'],
            'jumlah_tagihan' => $validated['jumlah_tagihan'],
            'nomor_tagihan' => $validated['nomor_tagihan'] ?? null,
            'nomor_token' => $isPrepaid ? ($validated['nomor_token'] ?? null) : null,
            'tanggal_jatuh_tempo' => $isPrepaid ? null : $validated['tanggal_jatuh_tempo'],
            'tanggal_bayar' => $isPrepaid
                ? ($validated['tanggal_bayar'] ?? now()->toDateString())
                : null,
            'meter_awal' => $validated['meter_awal'] ?? null,
            'meter_akhir' => $validated['meter_akhir'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

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
            ->with('customer')
            ->where('periode', $validated['periode_sumber'])
            ->get();

        $existingCustomerIds = UtilityBill::query()
            ->where('periode', $validated['periode_target'])
            ->pluck('utility_customer_id')
            ->all();

        $copied = 0;
        foreach ($sourceBills as $sourceBill) {
            if ($sourceBill->customer?->tipe === 'prepaid') {
                continue;
            }

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

    public function upload(): View
    {
        return view('utilities.bills.upload', [
            'jenisList' => UtilityCustomer::JENIS_UTILITAS,
            'tipeList' => UtilityCustomer::TIPE,
            'projects' => Project::orderBy('code')->get(),
            'periodeDefault' => now()->format('Y-m'),
        ]);
    }

    public function parseUpload(Request $request, OpenRouterService $openRouter): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_utilitas' => 'required|in:pln,pdam,telkom',
            'tipe' => 'required|in:postpaid,prepaid',
            'project' => 'required|string|max:20',
            'periode' => 'required|date_format:Y-m',
            'file' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $file = $request->file('file');
        $mimeType = $file->getMimeType() ?: 'image/jpeg';
        $base64 = base64_encode((string) file_get_contents($file->getRealPath()));

        $uploadDir = public_path('uploads/utilities');
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $file->move($uploadDir, uniqid('struk_', true).'.'.$file->getClientOriginalExtension());

        try {
            $extractedBills = $openRouter->extractUtilityBillsFromImageBase64($base64, $mimeType);
        } catch (OpenRouterException $exception) {
            return back()
                ->withInput()
                ->with('alert_type', 'error')
                ->with('alert_title', 'Gagal Membaca Struk')
                ->with('alert_message', $exception->getMessage());
        }

        if (count($extractedBills) === 0) {
            return back()
                ->withInput()
                ->with('alert_type', 'error')
                ->with('alert_title', 'Gagal Membaca Struk')
                ->with('alert_message', 'Tidak ada tagihan terdeteksi pada gambar.');
        }

        $jenis = $validated['jenis_utilitas'];
        $rows = [];
        foreach ($extractedBills as $bill) {
            $idpel = preg_replace('/\s+/', '', $bill['idpel']);
            $customer = UtilityCustomer::query()
                ->where('jenis_utilitas', $jenis)
                ->where('id_pelanggan', $idpel)
                ->first();

            $rows[] = [
                'idpel' => $idpel,
                'nama' => $bill['nama'],
                'jumlah' => $bill['jumlah'],
                'confidence' => $bill['confidence'],
                'matched' => $customer !== null,
            ];
        }

        session([
            'utility_upload_preview' => $rows,
            'utility_upload_meta' => [
                'jenis_utilitas' => $jenis,
                'tipe' => $validated['tipe'],
                'project' => $validated['project'],
                'periode' => $validated['periode'],
            ],
        ]);

        return redirect()->route('utilities.bills.preview');
    }

    public function preview(): View|RedirectResponse
    {
        $rows = session('utility_upload_preview');
        $meta = session('utility_upload_meta');

        if (! is_array($rows) || ! is_array($meta) || count($rows) === 0) {
            return redirect()
                ->route('utilities.bills.upload')
                ->with('error', 'Tidak ada data preview. Silakan upload struk terlebih dahulu.');
        }

        $periode = $meta['periode'];
        $tipe = $meta['tipe'] ?? 'postpaid';
        $tanggalJatuhTempo = $tipe === 'postpaid'
            ? Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->toDateString()
            : null;

        return view('utilities.bills.preview', [
            'rows' => $rows,
            'jenis_utilitas' => $meta['jenis_utilitas'],
            'jenis_label' => UtilityCustomer::JENIS_UTILITAS[$meta['jenis_utilitas']] ?? $meta['jenis_utilitas'],
            'tipe' => $tipe,
            'tipe_label' => UtilityCustomer::TIPE[$tipe] ?? $tipe,
            'project' => $meta['project'],
            'periode' => $periode,
            'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
        ]);
    }

    public function storeUpload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_utilitas' => 'required|in:pln,pdam,telkom',
            'tipe' => 'required|in:postpaid,prepaid',
            'project' => 'required|string|max:20',
            'periode' => 'required|date_format:Y-m',
            'tanggal_jatuh_tempo' => 'nullable|required_if:tipe,postpaid|date',
            'rows' => 'required|array|min:1',
            'rows.*.idpel' => 'nullable|string|max:50',
            'rows.*.nama' => 'nullable|string|max:255',
            'rows.*.jumlah' => 'nullable|integer|min:0',
        ]);

        $isPrepaid = $validated['tipe'] === 'prepaid';
        $saved = 0;
        $skipped = 0;
        $skippedInvalid = 0;
        $skippedDuplicate = 0;

        DB::transaction(function () use ($validated, $isPrepaid, &$saved, &$skipped, &$skippedInvalid, &$skippedDuplicate) {
            foreach ($validated['rows'] as $row) {
                $idpel = preg_replace('/\s+/', '', (string) ($row['idpel'] ?? ''));
                $jumlah = (int) ($row['jumlah'] ?? 0);

                if ($idpel === '' || $jumlah <= 0) {
                    $skipped++;
                    $skippedInvalid++;

                    continue;
                }

                $customer = UtilityCustomer::query()->firstOrCreate(
                    [
                        'jenis_utilitas' => $validated['jenis_utilitas'],
                        'id_pelanggan' => $idpel,
                    ],
                    [
                        'tipe' => $validated['tipe'],
                        'nama' => (string) ($row['nama'] ?? $idpel),
                        'project' => $validated['project'],
                        'is_active' => true,
                    ]
                );

                if (! $isPrepaid) {
                    $exists = UtilityBill::query()
                        ->where('utility_customer_id', $customer->id)
                        ->where('periode', $validated['periode'])
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        $skippedDuplicate++;

                        continue;
                    }
                }

                UtilityBill::create([
                    'utility_customer_id' => $customer->id,
                    'periode' => $validated['periode'],
                    'jumlah_tagihan' => $jumlah,
                    'tanggal_jatuh_tempo' => $isPrepaid ? null : $validated['tanggal_jatuh_tempo'],
                    'tanggal_bayar' => $isPrepaid ? now()->toDateString() : null,
                    'nomor_token' => null,
                ]);

                $saved++;
            }
        });

        session()->forget(['utility_upload_preview', 'utility_upload_meta']);

        $message = "Berhasil menyimpan {$saved} tagihan.";
        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati ({$skippedInvalid} tidak valid, {$skippedDuplicate} duplikat).";
        }

        return redirect()->route('utilities.bills.index')->with('success', $message);
    }
}
