<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBpjsApInvoiceRequest;
use App\Http\Requests\StoreBpjsApInvoiceRequest;
use App\Models\BpjsApInvoice;
use App\Models\Project;
use App\Models\SapSubmissionLog;
use App\Services\BpjsApInvoiceSapStatusService;
use App\Services\BpjsTkAccrualJournalService;
use App\Services\JournalEntrySubmissionService;
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

    public function data(Request $request, BpjsApInvoiceSapStatusService $sapStatusService): JsonResponse
    {
        try {
            $sapStatusService->refreshStale();
        } catch (Throwable) {
            // SAP sync must not break the listing.
        }

        $query = BpjsApInvoice::query()
            ->with(['submittedBy', 'journalEntry', 'cancelledBy'])
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
                    BpjsApInvoice::STATUS_CANCELLED => 'neutral',
                ];
                $labelMap = [
                    BpjsApInvoice::STATUS_POSTED => 'Posted',
                    BpjsApInvoice::STATUS_FAILED => 'Failed',
                    BpjsApInvoice::STATUS_PAID => 'Paid',
                    BpjsApInvoice::STATUS_PENDING => 'Pending',
                    BpjsApInvoice::STATUS_CANCELLED => 'Dibatalkan',
                ];
                $chip = $chipMap[$invoice->status] ?? 'neutral';
                $label = $labelMap[$invoice->status] ?? strtoupper($invoice->status);

                $html = '<span class="vj-chip vj-chip-'.$chip.'">'.$label.'</span>';

                if ($invoice->status === BpjsApInvoice::STATUS_CANCELLED) {
                    if ($invoice->cancelled_at) {
                        $html .= '<small class="d-block text-muted">'.$invoice->cancelled_at->format('d-M-Y H:i').'</small>';
                    }
                    if ($invoice->cancelledBy) {
                        $html .= '<small class="d-block text-muted">'.e($invoice->cancelledBy->name).'</small>';
                    }
                    if ($invoice->cancel_reason) {
                        $html .= '<small class="d-block text-muted">'.e($invoice->cancel_reason).'</small>';
                    }
                }

                return $html;
            })
            ->addColumn('sap_status', fn (BpjsApInvoice $invoice) => $this->renderSapStatusColumn($invoice))
            ->addColumn('sap_doc', function (BpjsApInvoice $invoice) {
                if ($invoice->sap_doc_num) {
                    $html = '<span class="vj-chip vj-chip-info">'.e($invoice->sap_doc_num).'</span>';
                    if ($invoice->sap_previous_doc_num) {
                        $html .= '<small class="d-block text-muted">Sebelumnya: '.e($invoice->sap_previous_doc_num).'</small>';
                    }

                    return $html;
                }

                return '<span class="text-muted">-</span>';
            })
            ->addColumn('accrual_je', function (BpjsApInvoice $invoice) {
                if ($invoice->jenis !== BpjsApInvoice::JENIS_KETENAGAKERJAAN) {
                    return '<span class="text-muted">—</span>';
                }

                $parts = [];

                if ($invoice->je_posting_date) {
                    $parts[] = $invoice->je_posting_date->format('d-M-Y');
                } else {
                    $parts[] = '<span class="text-muted">—</span>';
                }

                $sapJournalNo = $invoice->journalEntry?->sap_journal_no;
                if ($sapJournalNo) {
                    $parts[] = '<small class="d-block text-muted">'.e($sapJournalNo).'</small>';
                }

                if ($invoice->je_status) {
                    $chipMap = [
                        BpjsApInvoice::JE_STATUS_PENDING => 'warning',
                        BpjsApInvoice::JE_STATUS_SUCCESS => 'success',
                        BpjsApInvoice::JE_STATUS_FAILED => 'danger',
                        BpjsApInvoice::JE_STATUS_SKIPPED => 'neutral',
                        BpjsApInvoice::JE_STATUS_REVERSED => 'info',
                    ];
                    $labelMap = [
                        BpjsApInvoice::JE_STATUS_PENDING => 'Pending',
                        BpjsApInvoice::JE_STATUS_SUCCESS => 'Berhasil',
                        BpjsApInvoice::JE_STATUS_FAILED => 'Gagal',
                        BpjsApInvoice::JE_STATUS_SKIPPED => 'Dilewati',
                        BpjsApInvoice::JE_STATUS_REVERSED => 'Reversed',
                    ];
                    $chip = $chipMap[$invoice->je_status] ?? 'neutral';
                    $label = $labelMap[$invoice->je_status] ?? strtoupper($invoice->je_status);
                    $parts[] = '<span class="vj-chip vj-chip-'.$chip.'">'.$label.'</span>';
                }

                if ($invoice->je_status === BpjsApInvoice::JE_STATUS_SUCCESS && $invoice->journal_entry_id) {
                    $parts[] = '<a href="'.route('accounting.journal-entries.show', $invoice->journal_entry_id).'" class="small">Detail JE</a>';
                }

                return implode('', $parts);
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
            ->rawColumns(['jenis_badge', 'unit_label', 'dates', 'status_chip', 'sap_status', 'sap_doc', 'accrual_je', 'submitted_info', 'action'])
            ->make(true);
    }

    public function syncSapStatus(
        BpjsApInvoice $bpjsApInvoice,
        BpjsApInvoiceSapStatusService $sapStatusService
    ): RedirectResponse {
        $result = $sapStatusService->refresh($bpjsApInvoice);

        if ($result === null && empty($bpjsApInvoice->sap_doc_entry)) {
            return redirect()
                ->back()
                ->with('error', 'Invoice belum memiliki SAP Doc Entry.');
        }

        if ($result === null) {
            return redirect()
                ->back()
                ->with('error', 'Gagal memperbarui status SAP untuk invoice ini.');
        }

        return redirect()
            ->back()
            ->with('success', 'Status SAP diperbarui untuk invoice #'.$bpjsApInvoice->id.'.');
    }

    public function syncSapStatusAll(BpjsApInvoiceSapStatusService $sapStatusService): RedirectResponse
    {
        $summary = $sapStatusService->refreshAll();

        return redirect()
            ->back()
            ->with(
                'success',
                'Status SAP diperbarui: '.$summary['updated'].' baris, gagal '.$summary['failed'].' baris.'
            );
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

        $isTk = $validated['jenis'] === BpjsApInvoice::JENIS_KETENAGAKERJAAN;

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
            'auto_je' => $isTk ? ($validated['auto_je'] ?? true) : false,
            'je_posting_date' => $isTk
                ? ($validated['je_posting_date'] ?? BpjsApInvoice::defaultJePostingDate($validated['periode']))
                : null,
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

    public function submit(
        BpjsApInvoice $bpjsApInvoice,
        SapService $sapService,
        BpjsTkAccrualJournalService $accrualJournalService,
        BpjsApInvoiceSapStatusService $sapStatusService
    ): RedirectResponse {
        if (! in_array($bpjsApInvoice->status, [BpjsApInvoice::STATUS_PENDING, BpjsApInvoice::STATUS_FAILED], true)) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Hanya invoice berstatus pending atau failed yang bisa disubmit.');
        }

        try {
            $payload = $this->validatedSapSubmitPayload($bpjsApInvoice);
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('bpjs-ap-invoices.preview', $bpjsApInvoice)
                ->with('error', $exception->getMessage());
        }
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

        $bpjsApInvoice->refresh();
        try {
            $sapStatusService->refresh($bpjsApInvoice);
        } catch (Throwable) {
            // Non-blocking SAP status sync after submit.
        }

        $jeResult = $accrualJournalService->createAndSubmit($bpjsApInvoice, auth()->user());

        $successMessage = 'AP Invoice BPJS berhasil dibuat di SAP. DocNum: '.($bpjsApInvoice->sap_doc_num ?? '-');

        if ($bpjsApInvoice->jenis === BpjsApInvoice::JENIS_KETENAGAKERJAAN && $bpjsApInvoice->auto_je) {
            if (($jeResult['success'] ?? false) && ! ($jeResult['skipped'] ?? false)) {
                $successMessage .= ' Jurnal akrual berhasil diposting.';
            } elseif (! ($jeResult['success'] ?? false)) {
                $successMessage .= ' Jurnal akrual gagal: '.($jeResult['message'] ?? 'Unknown error').'. AP Invoice tetap posted.';
            }
        }

        return redirect()
            ->route('bpjs-ap-invoices.index')
            ->with('success', $successMessage);
    }

    public function retryJe(
        BpjsApInvoice $bpjsApInvoice,
        BpjsTkAccrualJournalService $accrualJournalService
    ): RedirectResponse {
        if ($bpjsApInvoice->je_status !== BpjsApInvoice::JE_STATUS_FAILED) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Retry jurnal akrual hanya tersedia untuk invoice dengan je_status failed.');
        }

        $result = $accrualJournalService->createAndSubmit($bpjsApInvoice, auth()->user());

        if ($result['success'] ?? false) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('success', 'Jurnal akrual BPJS TK berhasil diposting.');
        }

        return redirect()
            ->route('bpjs-ap-invoices.index')
            ->with('error', 'Gagal posting jurnal akrual: '.($result['message'] ?? 'Unknown error'));
    }

    public function retry(BpjsApInvoice $bpjsApInvoice, SapService $sapService, BpjsTkAccrualJournalService $accrualJournalService, BpjsApInvoiceSapStatusService $sapStatusService): RedirectResponse
    {
        if ($bpjsApInvoice->status !== BpjsApInvoice::STATUS_FAILED) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Retry hanya tersedia untuk invoice berstatus failed.');
        }

        return $this->submit($bpjsApInvoice, $sapService, $accrualJournalService, $sapStatusService);
    }

    public function cancel(
        CancelBpjsApInvoiceRequest $request,
        BpjsApInvoice $bpjsApInvoice,
        SapService $sapService,
        JournalEntrySubmissionService $journalEntrySubmissionService,
        BpjsApInvoiceSapStatusService $sapStatusService
    ): RedirectResponse {
        $user = $request->user();
        $reason = $request->validated('cancel_reason');

        if ($bpjsApInvoice->status !== BpjsApInvoice::STATUS_POSTED) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Hanya invoice berstatus posted yang bisa dibatalkan.');
        }

        if ((float) $bpjsApInvoice->paid_amount > 0) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Invoice yang sudah dibayar (paid_amount > 0) tidak bisa dibatalkan.');
        }

        if (empty($bpjsApInvoice->sap_doc_entry)) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Invoice belum memiliki SAP Doc Entry.');
        }

        try {
            $sapInvoice = $sapService->getPurchaseInvoiceByDocEntry($bpjsApInvoice->sap_doc_entry);
        } catch (Throwable $exception) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Gagal memverifikasi AP Invoice di SAP B1: '.$exception->getMessage());
        }

        if ($sapInvoice === null) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'AP Invoice tidak ditemukan di SAP B1.');
        }

        if ($this->isSapPurchaseInvoiceCancelled($sapInvoice)) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'AP Invoice sudah dibatalkan di SAP B1.');
        }

        $documentStatus = $sapInvoice['DocumentStatus'] ?? null;
        if ($documentStatus !== 'bost_Open') {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'AP Invoice di SAP B1 tidak dalam status open (status: '.($documentStatus ?? '-').').');
        }

        $paidToDate = (float) ($sapInvoice['PaidToDate'] ?? 0);
        if ($paidToDate > 0) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'AP Invoice di SAP B1 sudah memiliki pembayaran (PaidToDate > 0).');
        }

        $cancelResult = $sapService->cancelPurchaseInvoice($bpjsApInvoice->sap_doc_entry);

        if (! ($cancelResult['success'] ?? false)) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', $cancelResult['message'] ?? 'Gagal membatalkan AP Invoice di SAP B1.');
        }

        DB::transaction(function () use ($bpjsApInvoice, $user, $reason, $cancelResult) {
            $bpjsApInvoice->update([
                'status' => BpjsApInvoice::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancel_reason' => $reason,
            ]);

            SapSubmissionLog::create([
                'bpjs_ap_invoice_id' => $bpjsApInvoice->id,
                'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_CANCELLATION,
                'status' => 'success',
                'action' => SapSubmissionLog::ACTION_CANCELLATION,
                'sap_doc_num' => $bpjsApInvoice->sap_doc_num,
                'sap_doc_entry' => $bpjsApInvoice->sap_doc_entry,
                'sap_response' => $cancelResult['data'] ?? null,
                'attempt_number' => ($bpjsApInvoice->submissionLogs()->count() + 1),
                'submitted_by' => $user->id,
                'user_id' => $user->id,
                'error_message' => $reason,
            ]);
        });

        $jeMessage = null;

        if ($bpjsApInvoice->jenis === BpjsApInvoice::JENIS_KETENAGAKERJAAN && $bpjsApInvoice->journal_entry_id) {
            $bpjsApInvoice->load('journalEntry');
            $jeResult = $journalEntrySubmissionService->reverse($bpjsApInvoice->journalEntry, $user, $reason);

            if ($jeResult['success'] ?? false) {
                $bpjsApInvoice->update([
                    'je_status' => BpjsApInvoice::JE_STATUS_REVERSED,
                    'je_error' => null,
                ]);
            } else {
                $bpjsApInvoice->update([
                    'je_status' => BpjsApInvoice::JE_STATUS_FAILED,
                    'je_error' => $jeResult['message'] ?? 'Unknown error',
                ]);
                $jeMessage = $jeResult['message'] ?? 'Unknown error';
            }
        }

        $successMessage = 'AP Invoice BPJS berhasil dibatalkan di SAP B1.';

        if ($jeMessage !== null) {
            $successMessage .= ' Jurnal akrual gagal di-reverse: '.$jeMessage.'. AP Invoice tetap cancelled.';
        } elseif ($bpjsApInvoice->jenis === BpjsApInvoice::JENIS_KETENAGAKERJAAN && $bpjsApInvoice->journal_entry_id) {
            $successMessage .= ' Jurnal akrual berhasil di-reverse.';
        }

        $bpjsApInvoice->refresh();
        try {
            $sapStatusService->refresh($bpjsApInvoice);
        } catch (Throwable) {
            // Non-blocking SAP status sync after cancel.
        }

        return redirect()
            ->route('bpjs-ap-invoices.index')
            ->with('success', $successMessage);
    }

    public function cancelJe(
        BpjsApInvoice $bpjsApInvoice,
        JournalEntrySubmissionService $journalEntrySubmissionService,
        BpjsApInvoiceSapStatusService $sapStatusService
    ): RedirectResponse {
        if ($bpjsApInvoice->status !== BpjsApInvoice::STATUS_CANCELLED) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Retry reversal jurnal akrual hanya tersedia untuk invoice yang sudah dibatalkan.');
        }

        if ($bpjsApInvoice->je_status === BpjsApInvoice::JE_STATUS_REVERSED) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('success', 'Jurnal akrual sudah di-reverse.');
        }

        if ($bpjsApInvoice->je_status !== BpjsApInvoice::JE_STATUS_FAILED) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Retry reversal jurnal akrual hanya tersedia untuk je_status failed.');
        }

        if (! $bpjsApInvoice->journal_entry_id) {
            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('error', 'Invoice tidak memiliki jurnal akrual.');
        }

        $bpjsApInvoice->load('journalEntry');
        $reason = $bpjsApInvoice->cancel_reason ?? 'Retry reversal after AP cancellation';

        $jeResult = $journalEntrySubmissionService->reverse(
            $bpjsApInvoice->journalEntry,
            auth()->user(),
            $reason
        );

        if ($jeResult['success'] ?? false) {
            $bpjsApInvoice->update([
                'je_status' => BpjsApInvoice::JE_STATUS_REVERSED,
                'je_error' => null,
            ]);

            $bpjsApInvoice->refresh();
            try {
                $sapStatusService->refresh($bpjsApInvoice);
            } catch (Throwable) {
                // Non-blocking SAP status sync after JE cancel retry.
            }

            return redirect()
                ->route('bpjs-ap-invoices.index')
                ->with('success', 'Jurnal akrual berhasil di-reverse.');
        }

        $bpjsApInvoice->update([
            'je_error' => $jeResult['message'] ?? 'Unknown error',
        ]);

        return redirect()
            ->route('bpjs-ap-invoices.index')
            ->with('error', 'Gagal reverse jurnal akrual: '.($jeResult['message'] ?? 'Unknown error'));
    }

    /**
     * @param  array<string, mixed>  $sapInvoice
     */
    protected function isSapPurchaseInvoiceCancelled(array $sapInvoice): bool
    {
        $cancelled = strtoupper((string) ($sapInvoice['Cancelled'] ?? ''));

        return in_array($cancelled, ['TYES', 'Y'], true);
    }

    protected function renderSapStatusColumn(BpjsApInvoice $invoice): string
    {
        if (empty($invoice->sap_doc_entry)) {
            return '<span class="text-muted">Belum diposting ke SAP</span>';
        }

        if ($invoice->sap_cancelled === true) {
            $html = '<span class="vj-chip vj-chip-danger">Cancelled in SAP</span>';
            $html .= '<small class="text-muted d-block">dokumen SAP dibatalkan</small>';
        } elseif ($invoice->sap_document_status === 'bost_Close' && $invoice->sap_cancelled !== true) {
            $html = '<span class="vj-chip vj-chip-neutral">Closed</span>';
            $html .= '<small class="text-muted d-block">lunas/tertutup di SAP</small>';
        } elseif ($invoice->sap_document_status === 'bost_Open') {
            $html = '<span class="vj-chip vj-chip-success">Open</span>';
            $html .= '<small class="text-muted d-block">bisa dibayar</small>';
        } elseif ($invoice->sap_status_synced_at === null) {
            return '<span class="vj-chip vj-chip-warning">Belum dicek</span>';
        } else {
            $html = '<span class="vj-chip vj-chip-warning">Belum dicek</span>';
        }

        if ($invoice->sap_status_synced_at !== null) {
            $html .= '<small class="text-muted d-block">Dicek '.$invoice->sap_status_synced_at->format('d-M-Y H:i').'</small>';
        }

        if ($invoice->sap_previous_doc_num) {
            $html .= '<small class="text-muted d-block">Doc. lama: '.e($invoice->sap_previous_doc_num).'</small>';
        }

        return $html;
    }

    public function repostSap(
        BpjsApInvoice $bpjsApInvoice,
        SapService $sapService,
        BpjsApInvoiceSapStatusService $statusService
    ): RedirectResponse {
        if ($bpjsApInvoice->status === BpjsApInvoice::STATUS_CANCELLED) {
            return redirect()
                ->back()
                ->with('error', 'Invoice sudah dibatalkan di aplikasi, tidak bisa diposting ulang.');
        }

        if ($bpjsApInvoice->status !== BpjsApInvoice::STATUS_POSTED || empty($bpjsApInvoice->sap_doc_entry)) {
            return redirect()
                ->back()
                ->with('error', 'Invoice belum pernah diposting ke SAP.');
        }

        if ((float) $bpjsApInvoice->paid_amount > 0) {
            return redirect()
                ->back()
                ->with('error', 'Invoice sudah memiliki pembayaran tercatat (paid_amount > 0).');
        }

        try {
            $sapStatus = $sapService->getPurchaseInvoiceStatus($bpjsApInvoice->sap_doc_entry);
        } catch (Throwable $exception) {
            return redirect()
                ->back()
                ->with('error', 'Gagal memverifikasi AP Invoice di SAP B1: '.$exception->getMessage());
        }

        if ($sapStatus !== null && ! $this->isSapPurchaseInvoiceCancelled($sapStatus)) {
            try {
                $statusService->refresh($bpjsApInvoice, $sapService);
            } catch (Throwable) {
                // Non-blocking status sync before rejection.
            }

            return redirect()
                ->back()
                ->with('error', 'AP Invoice di SAP masih aktif (belum dibatalkan) — posting ulang tidak diperlukan.');
        }

        try {
            $payload = $this->validatedSapSubmitPayload($bpjsApInvoice);
        } catch (\RuntimeException $exception) {
            return redirect()
                ->back()
                ->with('error', $exception->getMessage());
        }

        $attemptNumber = ($bpjsApInvoice->submissionLogs()->count() + 1);
        $sapError = null;
        $newDocNum = null;
        $newDocEntry = null;

        try {
            DB::transaction(function () use ($bpjsApInvoice, $payload, $sapService, $statusService, $attemptNumber, &$newDocNum, &$newDocEntry) {
                $invoice = BpjsApInvoice::query()
                    ->whereKey($bpjsApInvoice->id)
                    ->lockForUpdate()
                    ->first();

                if ($invoice === null) {
                    throw new \RuntimeException('Invoice tidak ditemukan.');
                }

                if ($invoice->status === BpjsApInvoice::STATUS_CANCELLED) {
                    throw new \RuntimeException('Invoice sudah dibatalkan di aplikasi, tidak bisa diposting ulang.');
                }

                if ($invoice->status !== BpjsApInvoice::STATUS_POSTED || empty($invoice->sap_doc_entry)) {
                    throw new \RuntimeException('Invoice belum pernah diposting ke SAP.');
                }

                if ((float) $invoice->paid_amount > 0) {
                    throw new \RuntimeException('Invoice sudah memiliki pembayaran tercatat (paid_amount > 0).');
                }

                $sapStatus = $sapService->getPurchaseInvoiceStatus($invoice->sap_doc_entry);

                if ($sapStatus !== null && ! $this->isSapPurchaseInvoiceCancelled($sapStatus)) {
                    try {
                        $statusService->refresh($invoice, $sapService);
                    } catch (Throwable) {
                        // Non-blocking status sync before rejection.
                    }

                    throw new \RuntimeException('AP Invoice di SAP masih aktif (belum dibatalkan) — posting ulang tidak diperlukan.');
                }

                $oldDocNum = (string) ($invoice->sap_doc_num ?? '');
                $sapResult = $sapService->createApInvoice($payload);

                if (! ($sapResult['success'] ?? false)) {
                    throw new \RuntimeException($sapResult['message'] ?? 'Gagal membuat AP Invoice di SAP B1.');
                }

                $newDocNum = $sapResult['doc_num'] ?? null;
                $newDocEntry = $sapResult['doc_entry'] ?? null;

                $previousDocNums = $invoice->sap_previous_doc_num;
                if ($oldDocNum !== '') {
                    $previousDocNums = $previousDocNums
                        ? $previousDocNums.','.$oldDocNum
                        : $oldDocNum;
                }

                $invoice->update([
                    'status' => BpjsApInvoice::STATUS_POSTED,
                    'sap_doc_num' => $newDocNum,
                    'sap_doc_entry' => $newDocEntry,
                    'sap_previous_doc_num' => $previousDocNums,
                    'sap_error_message' => null,
                    'submitted_at' => now(),
                    'submitted_by' => auth()->id(),
                ]);

                SapSubmissionLog::create([
                    'bpjs_ap_invoice_id' => $invoice->id,
                    'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE,
                    'status' => 'success',
                    'action' => 'submission',
                    'sap_doc_num' => $newDocNum,
                    'sap_doc_entry' => $newDocEntry,
                    'sap_response' => $sapResult['data'] ?? $sapResult,
                    'attempt_number' => $attemptNumber,
                    'submitted_by' => auth()->id(),
                    'user_id' => auth()->id(),
                ]);
            });
        } catch (Throwable $exception) {
            $sapError = $exception->getMessage();

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
                ->back()
                ->with('error', $sapError);
        }

        $bpjsApInvoice->refresh();

        try {
            $statusService->refresh($bpjsApInvoice, $sapService);
        } catch (Throwable) {
            // Non-blocking SAP status sync after repost.
        }

        $successMessage = 'AP Invoice BPJS berhasil diposting ulang ke SAP. DocNum baru: '.($newDocNum ?? $bpjsApInvoice->sap_doc_num ?? '-');

        if ($sapStatus === null) {
            $successMessage .= ' Dokumen SAP sebelumnya tidak ditemukan; dokumen baru telah dibuat.';
        }

        if ($bpjsApInvoice->je_status === BpjsApInvoice::JE_STATUS_FAILED) {
            $successMessage .= ' Jurnal akrual masih gagal — gunakan tombol Retry Jurnal Akrual jika perlu.';
        }

        return redirect()
            ->back()
            ->with('success', $successMessage);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedSapSubmitPayload(BpjsApInvoice $bpjsApInvoice): array
    {
        $builder = new SapBpjsApInvoiceBuilder($bpjsApInvoice);
        $errors = $builder->validate();

        if ($errors !== []) {
            throw new \RuntimeException(implode(' ', $errors));
        }

        return $builder->build();
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
