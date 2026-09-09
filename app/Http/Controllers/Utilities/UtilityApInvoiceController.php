<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiateUtilityApInvoicePreviewRequest;
use App\Http\Requests\SubmitUtilityApInvoiceRequest;
use App\Models\Account;
use App\Models\SapSubmissionLog;
use App\Models\UtilityApInvoice;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use App\Models\UtilityVendor;
use App\Services\SapService;
use App\Services\SapUtilityApInvoiceBuilder;
use App\Services\SapVendorPaymentBuilder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class UtilityApInvoiceController extends Controller
{
    public function initiatePreview(InitiateUtilityApInvoicePreviewRequest $request): RedirectResponse
    {
        $result = $this->loadEligibleSelection($request->validated()['bill_ids']);

        if ($result['error'] !== null) {
            return redirect()->route('utilities.bills.index')->with('error', $result['error']);
        }

        session(['utility_ap_invoice_bill_ids' => $result['bills']->pluck('id')->all()]);

        return redirect()->route('utilities.bills.ap-invoice.preview');
    }

    public function preview(): View|RedirectResponse
    {
        $billIds = session('utility_ap_invoice_bill_ids', []);
        $result = $this->loadEligibleSelection($billIds);

        if ($result['error'] !== null) {
            return redirect()->route('utilities.bills.index')->with('error', $result['error']);
        }

        $builder = new SapUtilityApInvoiceBuilder($result['bills'], $result['vendor']);
        $preview = $builder->getPreviewData();

        return view('utilities.bills.ap_invoice_preview', [
            'preview' => $preview,
            'billIds' => $result['bills']->pluck('id')->all(),
        ]);
    }

    public function submit(SubmitUtilityApInvoiceRequest $request, SapService $sapService): RedirectResponse
    {
        $validated = $request->validated();
        $result = $this->loadEligibleSelection($validated['bill_ids'], $validated['num_at_card']);

        if ($result['error'] !== null) {
            return redirect()->route('utilities.bills.ap-invoice.preview')->with('error', $result['error']);
        }

        $builder = $result['builder'];
        $bills = $result['bills'];
        $vendor = $result['vendor'];
        $payload = $builder->build();
        $preview = $builder->getPreviewData();

        $postedInvoice = null;
        $sapError = null;

        try {
            $postedInvoice = DB::transaction(function () use ($bills, $vendor, $payload, $preview, $sapService) {
                $invoice = UtilityApInvoice::create([
                    'jenis_utilitas' => $vendor->jenis_utilitas,
                    'sap_business_partner_id' => $vendor->sap_business_partner_id,
                    'num_at_card' => $preview['num_at_card'],
                    'tax_code' => $preview['tax_code'],
                    'periode_summary' => $preview['periode_summary'],
                    'total_amount' => $preview['total'],
                    'status' => UtilityApInvoice::STATUS_PENDING,
                    'submitted_by' => auth()->id(),
                ]);

                UtilityBill::query()->whereIn('id', $bills->pluck('id'))->update([
                    'utility_ap_invoice_id' => $invoice->id,
                ]);

                $sapResult = $sapService->createApInvoice($payload);

                if (! ($sapResult['success'] ?? false)) {
                    throw new \RuntimeException($sapResult['message'] ?? 'Gagal membuat AP Invoice di SAP B1.');
                }

                $invoice->update([
                    'status' => UtilityApInvoice::STATUS_POSTED,
                    'sap_doc_num' => $sapResult['doc_num'] ?? null,
                    'sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                    'submitted_at' => now(),
                ]);

                SapSubmissionLog::create([
                    'utility_ap_invoice_id' => $invoice->id,
                    'document_type' => 'ap_invoice_utility',
                    'status' => 'success',
                    'action' => 'submission',
                    'sap_doc_num' => $sapResult['doc_num'] ?? null,
                    'sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                    'sap_response' => $sapResult['data'] ?? $sapResult,
                    'attempt_number' => 1,
                    'submitted_by' => auth()->id(),
                    'user_id' => auth()->id(),
                ]);

                return $invoice;
            });
        } catch (Throwable $exception) {
            $sapError = $exception->getMessage();

            SapSubmissionLog::create([
                'document_type' => 'ap_invoice_utility',
                'status' => 'failed',
                'action' => 'submission',
                'error_message' => $sapError,
                'sap_error' => $sapError,
                'attempt_number' => 1,
                'submitted_by' => auth()->id(),
                'user_id' => auth()->id(),
            ]);
        }

        if ($sapError !== null) {
            return redirect()
                ->route('utilities.bills.ap-invoice.preview')
                ->withInput()
                ->with('error', 'Gagal submit ke SAP B1: '.$sapError);
        }

        session()->forget('utility_ap_invoice_bill_ids');

        return redirect()
            ->route('utilities.ap-invoices.show', $postedInvoice)
            ->with('success', 'AP Invoice SAP berhasil dibuat. DocNum: '.($postedInvoice->sap_doc_num ?? '-'));
    }

    public function index(): View
    {
        $invoices = UtilityApInvoice::query()
            ->with(['sapBusinessPartner', 'submittedBy', 'paidBy', 'bills.customer'])
            ->orderByDesc('id')
            ->get();

        return view('utilities.ap_invoices.index', [
            'invoices' => $invoices,
            'jenisLabel' => UtilityCustomer::JENIS_UTILITAS,
            'canSubmitUtilityPayment' => auth()->user()?->can('submit_sap_utility_payment') ?? false,
            'defaultPreparedBy' => auth()->user()?->name ?? '',
        ]);
    }

    public function show(UtilityApInvoice $utilityApInvoice): View
    {
        $utilityApInvoice->load(['bills.customer.account', 'sapBusinessPartner', 'submittedBy', 'paidBy']);

        return view('utilities.ap_invoices.show', [
            'invoice' => $utilityApInvoice,
            'jenisLabel' => UtilityCustomer::JENIS_UTILITAS[$utilityApInvoice->jenis_utilitas] ?? strtoupper($utilityApInvoice->jenis_utilitas),
            'canSubmitUtilityPayment' => auth()->user()?->can('submit_sap_utility_payment') ?? false,
            'defaultPreparedBy' => auth()->user()?->name ?? '',
        ]);
    }

    public function paymentAccounts(): JsonResponse
    {
        return response()->json([
            'accounts' => $this->eligiblePaymentAccounts(),
        ]);
    }

    public function previewSapPayment(Request $request, UtilityApInvoice $utilityApInvoice, SapService $sapService): JsonResponse
    {
        try {
            $validated = $this->validateSapPaymentRequest($request);
            if ($validated instanceof JsonResponse) {
                return $validated;
            }

            $guardResponse = $this->guardUtilityPaymentEligibility($utilityApInvoice);
            if ($guardResponse !== null) {
                return $guardResponse;
            }

            $partner = $utilityApInvoice->sapBusinessPartner;
            if (! $partner || ! $partner->active) {
                return response()->json([
                    'error' => 'Vendor tidak valid',
                    'message' => 'Vendor SAP tidak aktif/tidak ter-mapping.',
                ], 422);
            }

            $apInvoice = $this->resolveUtilityApInvoiceInSap($sapService, $utilityApInvoice);
            if ($apInvoice instanceof JsonResponse) {
                return $apInvoice;
            }

            $remaining = $this->remainingFromApInvoice($apInvoice);
            $paymentAmount = $validated['payment_amount'] ?? $remaining;

            $account = $this->resolvePaymentAccount($validated['account_id'] ?? null);

            $invoicePayload = $this->invoicePayloadForBuilder($utilityApInvoice, $validated);

            $builder = new SapVendorPaymentBuilder(
                $invoicePayload,
                $apInvoice,
                $partner,
                $account,
                $validated['payment_means'],
                $validated['payment_date'],
                $paymentAmount,
                $validated['prepared_by'] ?? null,
                $validated['approved_by'] ?? null,
            );

            $errors = $builder->validate(requirePaymentAccount: false);
            if ($errors !== []) {
                return response()->json([
                    'error' => 'Validasi gagal',
                    'message' => implode(' ', $errors),
                    'errors' => $errors,
                ], 422);
            }

            $preview = $builder->getPreviewData();

            return response()->json([
                'success' => true,
                'preview' => $this->formatUtilityPaymentPreview($preview, $validated['remarks'] ?? null),
                'accounts' => $this->eligiblePaymentAccounts(),
            ]);
        } catch (Throwable $e) {
            return $this->utilityPaymentExceptionResponse($e, 'Utility AP Invoice SAP Preview Error');
        }
    }

    public function submitSapPayment(Request $request, UtilityApInvoice $utilityApInvoice, SapService $sapService): JsonResponse
    {
        try {
            $validated = $this->validateSapPaymentRequest($request, requireAccount: true);
            if ($validated instanceof JsonResponse) {
                return $validated;
            }

            $guardResponse = $this->guardUtilityPaymentEligibility($utilityApInvoice);
            if ($guardResponse !== null) {
                return $guardResponse;
            }

            $partner = $utilityApInvoice->sapBusinessPartner;
            if (! $partner || ! $partner->active) {
                return response()->json([
                    'error' => 'Vendor tidak valid',
                    'message' => 'Vendor SAP tidak aktif/tidak ter-mapping.',
                ], 422);
            }

            $apInvoice = $this->resolveUtilityApInvoiceInSap($sapService, $utilityApInvoice);
            if ($apInvoice instanceof JsonResponse) {
                return $apInvoice;
            }

            $remaining = $this->remainingFromApInvoice($apInvoice);
            $paymentAmount = (float) ($validated['payment_amount'] ?? $remaining);

            $account = $this->resolvePaymentAccount($validated['account_id']);
            if (! $account) {
                return response()->json([
                    'error' => 'Akun tidak valid',
                    'message' => 'Akun kas/bank yang dipilih tidak valid atau belum memiliki mapping SAP.',
                ], 422);
            }

            $invoicePayload = $this->invoicePayloadForBuilder($utilityApInvoice, $validated);

            $builder = new SapVendorPaymentBuilder(
                $invoicePayload,
                $apInvoice,
                $partner,
                $account,
                $validated['payment_means'],
                $validated['payment_date'],
                $paymentAmount,
                $validated['prepared_by'] ?? null,
                $validated['approved_by'] ?? null,
            );

            $errors = $builder->validate(requirePaymentAccount: true);
            if ($errors !== []) {
                return response()->json([
                    'error' => 'Validasi gagal',
                    'message' => implode(' ', $errors),
                    'errors' => $errors,
                ], 422);
            }

            $payload = $builder->build();

            try {
                $sapResult = $sapService->createOutgoingPayment($payload);
            } catch (Throwable $e) {
                $this->logUtilityPaymentSubmission($utilityApInvoice, 'failed', $e->getMessage(), null, $paymentAmount);

                return response()->json([
                    'error' => 'SAP submission failed',
                    'message' => $e->getMessage(),
                ], 422);
            }

            if (! ($sapResult['success'] ?? false)) {
                $message = $sapResult['message'] ?? 'Gagal membuat outgoing payment di SAP B1.';
                $this->logUtilityPaymentSubmission($utilityApInvoice, 'failed', $message, null, $paymentAmount);

                return response()->json([
                    'error' => 'SAP submission failed',
                    'message' => $message,
                ], 422);
            }

            DB::transaction(function () use ($utilityApInvoice, $validated, $sapResult, $paymentAmount) {
                $this->logUtilityPaymentSubmission($utilityApInvoice, 'success', null, $sapResult, $paymentAmount);

                $utilityApInvoice->update([
                    'status' => UtilityApInvoice::STATUS_PAID,
                    'paid_at' => $validated['payment_date'],
                    'paid_amount' => $paymentAmount,
                    'paid_sap_doc_num' => $sapResult['doc_num'] ?? null,
                    'paid_sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                    'paid_by' => auth()->id(),
                    'payment_remarks' => $validated['remarks'] ?? null,
                ]);

                UtilityBill::query()
                    ->where('utility_ap_invoice_id', $utilityApInvoice->id)
                    ->update(['tanggal_bayar' => $validated['payment_date']]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Outgoing payment posted to SAP B1. DocNum: '.($sapResult['doc_num'] ?? '-'),
                'data' => [
                    'paid_amount' => $paymentAmount,
                    'paid_sap_doc_num' => $sapResult['doc_num'] ?? null,
                    'paid_sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                    'paid_at' => $validated['payment_date'],
                ],
            ]);
        } catch (Throwable $e) {
            return $this->utilityPaymentExceptionResponse($e, 'Utility AP Invoice SAP Submit Error');
        }
    }

    /**
     * @param  list<int|string>  $billIds
     * @return array{bills: Collection<int, UtilityBill>, vendor: UtilityVendor|null, builder: SapUtilityApInvoiceBuilder|null, error: string|null}
     */
    private function loadEligibleSelection(array $billIds, ?string $numAtCard = null): array
    {
        $bills = UtilityBill::query()
            ->with(['customer.account'])
            ->whereIn('id', $billIds)
            ->get();

        if ($bills->isEmpty()) {
            return [
                'bills' => $bills,
                'vendor' => null,
                'builder' => null,
                'error' => 'Tidak ada data preview. Silakan pilih tagihan terlebih dahulu.',
            ];
        }

        $jenis = $bills->first()?->customer?->jenis_utilitas;
        $vendor = $jenis
            ? UtilityVendor::query()->with('sapBusinessPartner')->where('jenis_utilitas', $jenis)->first()
            : null;

        if (! $vendor) {
            return [
                'bills' => $bills,
                'vendor' => null,
                'builder' => null,
                'error' => 'Vendor SAP untuk jenis utilitas ini belum di-mapping.',
            ];
        }

        $builder = new SapUtilityApInvoiceBuilder($bills, $vendor, $numAtCard);
        $errors = $builder->validate();

        if ($errors !== []) {
            return [
                'bills' => $bills,
                'vendor' => $vendor,
                'builder' => $builder,
                'error' => implode(' ', $errors),
            ];
        }

        return [
            'bills' => $bills,
            'vendor' => $vendor,
            'builder' => $builder,
            'error' => null,
        ];
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function validateSapPaymentRequest(Request $request, bool $requireAccount = false): array|JsonResponse
    {
        $eligibleAccountIds = Account::query()
            ->selectable()
            ->whereIn('type', ['cash', 'bank'])
            ->whereNotNull('sap_account')
            ->where('sap_account', '!=', '')
            ->pluck('id')
            ->all();

        $accountRule = 'required|integer';
        if ($eligibleAccountIds !== []) {
            $accountRule .= '|in:'.implode(',', $eligibleAccountIds);
        } else {
            $accountRule .= '|in:0';
        }

        $rules = [
            'payment_means' => 'nullable|in:cash,transfer',
            'account_id' => $accountRule,
            'payment_date' => 'nullable|date_format:Y-m-d',
            'payment_amount' => 'nullable|numeric|min:0.01',
            'prepared_by' => 'nullable|string|max:100',
            'approved_by' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:500',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validasi gagal',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['payment_means'] = $validated['payment_means'] ?? SapVendorPaymentBuilder::MEANS_TRANSFER;
        $validated['payment_date'] = $validated['payment_date'] ?? Carbon::today()->format('Y-m-d');

        return $validated;
    }

    private function guardUtilityPaymentEligibility(UtilityApInvoice $utilityApInvoice): ?JsonResponse
    {
        if ($utilityApInvoice->status !== UtilityApInvoice::STATUS_POSTED) {
            return response()->json([
                'error' => 'Status tidak valid',
                'message' => 'Outgoing payment hanya dapat dibuat untuk AP Invoice dengan status Posted.',
            ], 422);
        }

        if ($utilityApInvoice->isPaid()) {
            return response()->json([
                'error' => 'Sudah dibayar',
                'message' => 'AP Invoice ini sudah dibayar.'.($utilityApInvoice->paid_sap_doc_num ? ' (OP DocNum '.$utilityApInvoice->paid_sap_doc_num.')' : ''),
            ], 422);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function resolveUtilityApInvoiceInSap(SapService $sapService, UtilityApInvoice $utilityApInvoice): array|JsonResponse
    {
        if (empty($utilityApInvoice->sap_doc_entry)) {
            return response()->json([
                'error' => 'AP Invoice tidak ditemukan',
                'message' => 'AP Invoice belum memiliki SAP DocEntry.',
            ], 422);
        }

        $apInvoice = $sapService->getPurchaseInvoiceByDocEntry($utilityApInvoice->sap_doc_entry);

        if (! $apInvoice) {
            return response()->json([
                'error' => 'AP Invoice tidak ditemukan',
                'message' => 'AP Invoice tidak ditemukan di SAP B1 (DocEntry '.$utilityApInvoice->sap_doc_entry.').',
            ], 422);
        }

        $docNum = $apInvoice['DocNum'] ?? $utilityApInvoice->sap_doc_num ?? '-';
        $docTotal = (float) ($apInvoice['DocTotal'] ?? 0);
        $paidToDate = (float) ($apInvoice['PaidToDate'] ?? 0);
        $status = $apInvoice['DocumentStatus'] ?? null;

        if ($status === 'bost_Closed' || $paidToDate >= $docTotal - SapVendorPaymentBuilder::AMOUNT_TOLERANCE) {
            return response()->json([
                'error' => 'Sudah lunas di SAP',
                'message' => 'AP invoice sudah lunas di SAP (DocNum '.$docNum.').',
            ], 422);
        }

        return $apInvoice;
    }

    /**
     * @param  array<string, mixed>  $apInvoice
     */
    private function remainingFromApInvoice(array $apInvoice): float
    {
        return max(0.0, (float) ($apInvoice['DocTotal'] ?? 0) - (float) ($apInvoice['PaidToDate'] ?? 0));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{invoice_number: string, amount: float, payment_date: string, remarks: string|null}
     */
    private function invoicePayloadForBuilder(UtilityApInvoice $utilityApInvoice, array $validated): array
    {
        return [
            'invoice_number' => (string) $utilityApInvoice->num_at_card,
            'amount' => (float) $utilityApInvoice->total_amount,
            'payment_date' => $validated['payment_date'],
            'remarks' => $validated['remarks'] ?? null,
        ];
    }

    private function resolvePaymentAccount(?int $accountId): ?Account
    {
        if ($accountId === null) {
            return null;
        }

        return Account::query()
            ->selectable()
            ->whereKey($accountId)
            ->whereIn('type', ['cash', 'bank'])
            ->whereNotNull('sap_account')
            ->where('sap_account', '!=', '')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $preview
     * @return array<string, mixed>
     */
    private function formatUtilityPaymentPreview(array $preview, ?string $remarks): array
    {
        $partner = $preview['partner'] ?? [];
        $apInvoice = $preview['ap_invoice'] ?? [];
        $account = $preview['account'] ?? null;

        return [
            'vendor' => [
                'code' => $partner['code'] ?? null,
                'name' => $partner['name'] ?? null,
            ],
            'ap_doc_num' => $apInvoice['doc_num'] ?? null,
            'doc_total' => $apInvoice['doc_total'] ?? null,
            'paid_to_date' => $apInvoice['paid_to_date'] ?? null,
            'remaining' => $apInvoice['remaining_balance'] ?? null,
            'payment_amount' => $preview['payment_amount'] ?? null,
            'payment_means' => $preview['payment_means'] ?? null,
            'account' => $account,
            'remarks' => $remarks,
            'prepared_by' => $preview['prepared_by'] ?? null,
            'approved_by' => $preview['approved_by'] ?? null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function eligiblePaymentAccounts(): array
    {
        return Account::query()
            ->selectable()
            ->whereIn('type', ['cash', 'bank'])
            ->whereNotNull('sap_account')
            ->where('sap_account', '!=', '')
            ->orderBy('account_name')
            ->get(['id', 'account_number', 'account_name', 'sap_account', 'type'])
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'label' => trim($account->account_name.' ('.$account->account_number.')'),
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'sap_account' => $account->sap_account,
                'type' => $account->type,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $sapResult
     */
    private function logUtilityPaymentSubmission(
        UtilityApInvoice $utilityApInvoice,
        string $status,
        ?string $errorMessage,
        ?array $sapResult = null,
        ?float $paymentAmount = null,
    ): void {
        SapSubmissionLog::create([
            'utility_ap_invoice_id' => $utilityApInvoice->id,
            'document_type' => 'utility_ap_invoice_payment',
            'status' => $status,
            'action' => 'submission',
            'error_message' => $errorMessage,
            'sap_error' => $errorMessage,
            'sap_doc_num' => $sapResult['doc_num'] ?? null,
            'sap_doc_entry' => $sapResult['doc_entry'] ?? null,
            'amount' => $paymentAmount,
            'sap_response' => $sapResult['data'] ?? $sapResult,
            'attempt_number' => 1,
            'submitted_by' => auth()->id(),
            'user_id' => auth()->id(),
        ]);
    }

    private function utilityPaymentExceptionResponse(Throwable $e, string $context): JsonResponse
    {
        Log::error($context.': '.$e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'error' => 'Internal server error',
            'message' => $e->getMessage(),
        ], 500);
    }
}
