<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBpjsApInvoiceRequest;
use App\Models\BpjsApInvoice;
use App\Models\Project;
use App\Models\SapSubmissionLog;
use App\Services\SapBpjsApInvoiceBuilder;
use App\Services\SapService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class BpjsApInvoiceController extends Controller
{
    public function index(): View
    {
        return view('bpjs.ap-invoices.index', [
            'jenisLabels' => BpjsApInvoice::JENIS_LABELS,
            'projects' => Project::query()->orderBy('code')->get(),
            'canSubmit' => auth()->user()?->can('submit_sap_ap_invoice_bpjs') ?? false,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = BpjsApInvoice::query()
            ->with('submittedBy')
            ->orderByDesc('id');

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->filled('unit')) {
            $query->where('unit', $request->unit);
        }

        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('jenis_badge', function (BpjsApInvoice $invoice) {
                $label = BpjsApInvoice::JENIS_LABELS[$invoice->jenis] ?? strtoupper($invoice->jenis);
                $chip = $invoice->jenis === BpjsApInvoice::JENIS_KESEHATAN ? 'vj-chip-info' : 'vj-chip-neutral';

                return '<span class="vj-chip '.$chip.'">'.e($label).'</span>';
            })
            ->addColumn('unit_label', fn (BpjsApInvoice $invoice) => e(BpjsApInvoice::unitLabel($invoice->unit)).' <small class="text-muted d-block">'.e($invoice->unit).'</small>')
            ->editColumn('amount', fn (BpjsApInvoice $invoice) => number_format((float) $invoice->amount, 0, ',', '.'))
            ->addColumn('dates', function (BpjsApInvoice $invoice) {
                return $invoice->doc_date?->format('d-M-Y')
                    .'<small class="d-block text-muted">JT: '.$invoice->due_date?->format('d-M-Y').'</small>';
            })
            ->addColumn('status_chip', function (BpjsApInvoice $invoice) {
                $chipMap = [
                    BpjsApInvoice::STATUS_POSTED => 'success',
                    BpjsApInvoice::STATUS_FAILED => 'danger',
                    BpjsApInvoice::STATUS_PAID => 'info',
                    BpjsApInvoice::STATUS_PENDING => 'warning',
                ];
                $chip = $chipMap[$invoice->status] ?? 'neutral';

                return '<span class="vj-chip vj-chip-'.$chip.'">'.strtoupper($invoice->status).'</span>';
            })
            ->addColumn('sap_doc', function (BpjsApInvoice $invoice) {
                if ($invoice->sap_doc_num) {
                    return '<span class="vj-chip vj-chip-info">'.e($invoice->sap_doc_num).'</span>';
                }

                return '<span class="text-muted">-</span>';
            })
            ->addColumn('submitted_info', function (BpjsApInvoice $invoice) {
                if (! $invoice->submitted_at) {
                    return '<span class="text-muted">-</span>';
                }

                return $invoice->submitted_at->format('d-M-Y H:i')
                    .'<small class="d-block text-muted">'.e($invoice->submittedBy?->name ?? '-').'</small>';
            })
            ->addColumn('action', function (BpjsApInvoice $invoice) {
                return view('bpjs.ap-invoices.partials.action', [
                    'invoice' => $invoice,
                    'canSubmit' => auth()->user()?->can('submit_sap_ap_invoice_bpjs') ?? false,
                ])->render();
            })
            ->rawColumns(['jenis_badge', 'unit_label', 'dates', 'status_chip', 'sap_doc', 'submitted_info', 'action'])
            ->make(true);
    }

    public function store(StoreBpjsApInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (BpjsApInvoice::hasActiveDuplicate($validated['jenis'], $validated['unit'], $validated['periode'])) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->withInput()
                ->with('error', 'AP Invoice BPJS untuk jenis, unit, dan periode ini sudah ada (pending/posted/paid).');
        }

        $numAtCard = SapBpjsApInvoiceBuilder::buildNumAtCard($validated['periode']);
        $label = SapBpjsApInvoiceBuilder::buildLabel($validated['jenis'], $validated['unit'], $validated['periode']);

        $invoice = BpjsApInvoice::create([
            'jenis' => $validated['jenis'],
            'unit' => $validated['unit'],
            'periode' => $validated['periode'],
            'amount' => $validated['amount'],
            'doc_date' => $validated['doc_date'],
            'due_date' => $validated['due_date'],
            'num_at_card' => $numAtCard,
            'label' => $label,
            'status' => BpjsApInvoice::STATUS_PENDING,
            'submitted_by' => auth()->id(),
        ]);

        return redirect()
            ->route('bpjs-ap-invoices.preview', $invoice)
            ->with('success', 'Data AP Invoice BPJS disimpan. Silakan preview dan submit ke SAP.');
    }

    public function preview(BpjsApInvoice $bpjsApInvoice): View|RedirectResponse
    {
        $builder = new SapBpjsApInvoiceBuilder($bpjsApInvoice);
        $errors = $builder->validate();

        if ($errors !== []) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', implode(' ', $errors));
        }

        return view('bpjs.ap-invoices.preview', [
            'invoice' => $bpjsApInvoice,
            'preview' => $builder->getPreviewData(),
            'payload' => $builder->build(),
            'canSubmit' => auth()->user()?->can('submit_sap_ap_invoice_bpjs') ?? false,
        ]);
    }

    public function submit(BpjsApInvoice $bpjsApInvoice, SapService $sapService): RedirectResponse
    {
        if (! in_array($bpjsApInvoice->status, [BpjsApInvoice::STATUS_PENDING, BpjsApInvoice::STATUS_FAILED], true)) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Hanya invoice berstatus pending atau failed yang bisa disubmit.');
        }

        $builder = new SapBpjsApInvoiceBuilder($bpjsApInvoice);
        $errors = $builder->validate();

        if ($errors !== []) {
            return redirect()
                ->route('bpjs-ap-invoices.preview', $bpjsApInvoice)
                ->with('error', implode(' ', $errors));
        }

        $payload = $builder->build();
        $attemptNumber = ($bpjsApInvoice->submissionLogs()->count() + 1);
        $sapError = null;

        try {
            DB::transaction(function () use ($bpjsApInvoice, $payload, $sapService, $attemptNumber) {
                $sapResult = $sapService->createApInvoice($payload);

                if (! ($sapResult['success'] ?? false)) {
                    throw new \RuntimeException($sapResult['message'] ?? 'Gagal membuat AP Invoice di SAP B1.');
                }

                $bpjsApInvoice->update([
                    'status' => BpjsApInvoice::STATUS_POSTED,
                    'sap_doc_num' => $sapResult['doc_num'] ?? null,
                    'sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                    'sap_error_message' => null,
                    'submitted_at' => now(),
                    'submitted_by' => auth()->id(),
                ]);

                SapSubmissionLog::create([
                    'bpjs_ap_invoice_id' => $bpjsApInvoice->id,
                    'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE,
                    'status' => 'success',
                    'action' => 'submission',
                    'sap_doc_num' => $sapResult['doc_num'] ?? null,
                    'sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                    'sap_response' => $sapResult['data'] ?? $sapResult,
                    'attempt_number' => $attemptNumber,
                    'submitted_by' => auth()->id(),
                    'user_id' => auth()->id(),
                ]);
            });
        } catch (Throwable $exception) {
            $sapError = $exception->getMessage();

            $bpjsApInvoice->update([
                'status' => BpjsApInvoice::STATUS_FAILED,
                'sap_error_message' => $sapError,
            ]);

            SapSubmissionLog::create([
                'bpjs_ap_invoice_id' => $bpjsApInvoice->id,
                'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE,
                'status' => 'failed',
                'action' => 'submission',
                'error_message' => $sapError,
                'sap_error' => $sapError,
                'attempt_number' => $attemptNumber,
                'submitted_by' => auth()->id(),
                'user_id' => auth()->id(),
            ]);
        }

        if ($sapError !== null) {
            return redirect()
                ->route('bpjs-ap-invoices.preview', $bpjsApInvoice)
                ->with('error', 'Gagal submit ke SAP B1: '.$sapError);
        }

        return redirect()
            ->route('bpjs-ap-invoices.index')
            ->with('success', 'AP Invoice BPJS berhasil dibuat di SAP. DocNum: '.($bpjsApInvoice->sap_doc_num ?? '-'));
    }

    public function retry(BpjsApInvoice $bpjsApInvoice, SapService $sapService): RedirectResponse
    {
        if ($bpjsApInvoice->status !== BpjsApInvoice::STATUS_FAILED) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Retry hanya tersedia untuk invoice berstatus failed.');
        }

        return $this->submit($bpjsApInvoice, $sapService);
    }

    public function lastAmount(Request $request): JsonResponse
    {
        $request->validate([
            'jenis' => ['required', 'in:kesehatan,ketenagakerjaan'],
            'unit' => ['required', 'string'],
            'periode' => ['required', 'date_format:Y-m'],
        ]);

        $previousPeriode = Carbon::createFromFormat('Y-m', $request->periode)
            ->startOfMonth()
            ->subMonth()
            ->format('Y-m');

        $previous = BpjsApInvoice::query()
            ->where('jenis', $request->jenis)
            ->where('unit', $request->unit)
            ->where('periode', $previousPeriode)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'amount' => $previous ? (float) $previous->amount : null,
            'periode_sumber' => $previousPeriode,
            'found' => $previous !== null,
        ]);
    }
}
