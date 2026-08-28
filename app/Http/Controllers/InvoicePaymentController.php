<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewSapInvoicePaymentRequest;
use App\Http\Requests\SubmitSapInvoicePaymentRequest;
use App\Models\Account;
use App\Models\SapBusinessPartner;
use App\Models\SapSubmissionLog;
use App\Services\SapService;
use App\Services\SapVendorPaymentBuilder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InvoicePaymentController extends Controller
{
    protected ?string $apiUrl;

    protected ?string $apiKey;

    protected ?string $departmentCode;

    /** @var array<string>|null */
    private ?array $departmentCodesCache = null;

    public function __construct()
    {
        $this->apiUrl = env('DDS_API_URL');
        $this->apiKey = env('DDS_API_KEY');

        if (auth()->check() && auth()->user()->dds_department_code) {
            $this->departmentCode = auth()->user()->dds_department_code;
        } else {
            $this->departmentCode = env('DDS_DEPARTMENT_CODE');
        }
    }

    public function index()
    {
        return view('invoice-payment.index', [
            'departmentValidation' => $this->getDepartmentValidationState(),
            'canSubmitSapPayment' => auth()->user()?->can('submit_sap_invoice_payment') ?? false,
            'canMarkPaidWithoutSap' => auth()->user()?->can('mark_invoice_paid_without_sap') ?? false,
            'defaultPreparedBy' => auth()->user()?->name ?? '',
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        try {
            if ($error = $this->configurationErrorResponse()) {
                return $error;
            }

            if ($error = $this->departmentValidationErrorResponse()) {
                return $error;
            }

            $response = Http::withHeaders($this->ddsHeaders())
                ->get(
                    "{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/invoices",
                    $this->buildDdsQueryParams($request)
                );

            if ($response->successful()) {
                $invoices = $response->json()['data']['invoices'] ?? [];

                if ($request->filled('search')) {
                    $invoices = $this->filterInvoicesBySearch($invoices, $request->search);
                }

                return response()->json($this->calculateDashboardData($invoices));
            }

            return $this->ddsFailureResponse($response, 'Failed to fetch dashboard data');
        } catch (\Exception $e) {
            return $this->exceptionResponse($e, 'Invoice Payment Dashboard Error');
        }
    }

    public function waitingPayment(Request $request): JsonResponse
    {
        try {
            if ($error = $this->configurationErrorResponse()) {
                return $error;
            }

            if ($error = $this->departmentValidationErrorResponse()) {
                return $error;
            }

            $queryParams = $this->buildDdsQueryParams($request);

            $response = Http::withHeaders($this->ddsHeaders())
                ->get("{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/wait-payment-invoices", $queryParams);

            if ($response->successful()) {
                $invoices = $response->json()['data']['invoices'] ?? [];
                $waitingInvoices = $this->addDaysCalculation($invoices);

                if ($request->filled('search')) {
                    $waitingInvoices = $this->filterInvoicesBySearch($waitingInvoices, $request->search);
                }

                $waitingInvoices = $this->attachSapPaymentStatus($waitingInvoices);

                return response()->json(['invoices' => array_values($waitingInvoices)]);
            }

            return $this->ddsFailureResponse($response, 'Failed to fetch waiting payment invoices');
        } catch (\Exception $e) {
            return $this->exceptionResponse($e, 'Invoice Payment Waiting Payment Error');
        }
    }

    public function paidInvoices(Request $request): JsonResponse
    {
        try {
            if ($error = $this->configurationErrorResponse()) {
                return $error;
            }

            if ($error = $this->departmentValidationErrorResponse()) {
                return $error;
            }

            $queryParams = $this->buildDdsQueryParams($request);

            $response = Http::withHeaders($this->ddsHeaders())
                ->get("{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/paid-invoices", $queryParams);

            if ($response->successful()) {
                $invoices = $response->json()['data']['invoices'] ?? [];
                $paidInvoices = $this->addDaysCalculation($invoices);

                if ($request->filled('search')) {
                    $paidInvoices = $this->filterInvoicesBySearch($paidInvoices, $request->search);
                }

                $paidInvoices = $this->attachSapPaymentStatus($paidInvoices);

                return response()->json(['invoices' => array_values($paidInvoices)]);
            }

            return $this->ddsFailureResponse($response, 'Failed to fetch paid invoices');
        } catch (\Exception $e) {
            return $this->exceptionResponse($e, 'Invoice Payment Paid Invoices Error');
        }
    }

    public function updatePayment(Request $request, $invoiceId): JsonResponse
    {
        try {
            if (! auth()->user()?->can('mark_invoice_paid_without_sap')) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You do not have permission to mark invoices paid without SAP.',
                ], 403);
            }

            if ($error = $this->configurationErrorResponse(requireDepartment: false)) {
                return $error;
            }

            $request->validate([
                'payment_date' => 'required|date|date_format:Y-m-d',
                'remarks' => 'nullable|string|max:500',
                'payment_project' => 'nullable|string|max:50',
            ]);

            $paymentData = array_filter([
                'payment_date' => $request->payment_date,
                'payment_status' => 'paid',
                'status' => 'closed',
                'remarks' => $request->remarks,
                'payment_project' => $request->payment_project,
            ], fn ($value) => $value !== null);

            $response = Http::withHeaders($this->ddsHeaders())
                ->put("{$this->apiUrl}/api/v1/invoices/{$invoiceId}/payment", $paymentData);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment updated successfully',
                    'data' => $data['data'] ?? [],
                ]);
            }

            return $this->ddsFailureResponse($response, 'Failed to update payment');
        } catch (\Exception $e) {
            return $this->exceptionResponse($e, 'Invoice Payment Update Error');
        }
    }

    public function previewSapPayment(PreviewSapInvoicePaymentRequest $request, $invoiceId, SapService $sapService): JsonResponse
    {
        try {
            $invoice = $this->invoicePayloadFromRequest($request, $invoiceId);
            $paymentHistory = $this->paymentHistoryForInvoice($invoiceId);

            $partner = $this->resolveSupplierPartner($invoice['supplier_sap_code']);
            if (! $partner) {
                return response()->json([
                    'error' => 'Supplier not mapped',
                    'message' => "Supplier SAP code \"{$invoice['supplier_sap_code']}\" is not mapped to an active vendor in Payreq-X.",
                ], 422);
            }

            try {
                $apInvoice = $this->resolveApInvoice($sapService, $invoice);
            } catch (\RuntimeException $e) {
                return response()->json([
                    'error' => 'AP Invoice not found',
                    'message' => $e->getMessage(),
                ], 422);
            }

            $remaining = $this->remainingFromApInvoice($apInvoice);
            $latestSuccess = $this->latestSuccessfulSapPaymentLog($invoiceId);

            if ($remaining <= SapVendorPaymentBuilder::AMOUNT_TOLERANCE) {
                return response()->json([
                    'success' => true,
                    'fully_paid' => true,
                    'preview' => [
                        'invoice' => [
                            'invoice_number' => $invoice['invoice_number'],
                            'amount' => (float) ($invoice['amount'] ?? 0),
                            'payment_date' => $invoice['payment_date'] ?? Carbon::today()->format('Y-m-d'),
                            'remarks' => $invoice['remarks'] ?? null,
                        ],
                        'ap_invoice' => [
                            'doc_entry' => $apInvoice['DocEntry'] ?? null,
                            'doc_num' => $apInvoice['DocNum'] ?? null,
                            'doc_total' => isset($apInvoice['DocTotal']) ? (float) $apInvoice['DocTotal'] : null,
                            'paid_to_date' => (float) ($apInvoice['PaidToDate'] ?? 0),
                            'remaining_balance' => $remaining,
                        ],
                        'sap_payment' => $latestSuccess ? [
                            'doc_num' => $latestSuccess->sap_doc_num,
                            'doc_entry' => $latestSuccess->sap_doc_entry,
                        ] : null,
                    ],
                    'payment_history' => $paymentHistory,
                    'accounts' => [],
                ]);
            }

            $paymentAmount = $request->filled('payment_amount')
                ? (float) $request->input('payment_amount')
                : $remaining;

            $builder = new SapVendorPaymentBuilder(
                $invoice,
                $apInvoice,
                $partner,
                null,
                SapVendorPaymentBuilder::MEANS_TRANSFER,
                $request->input('payment_date'),
                $paymentAmount,
                $request->input('prepared_by'),
                $request->input('approved_by'),
            );
            $errors = $builder->validate();
            if ($errors !== []) {
                return response()->json([
                    'error' => 'Validation failed',
                    'message' => implode(' ', $errors),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'fully_paid' => false,
                'preview' => $builder->getPreviewData(),
                'payment_history' => $paymentHistory,
                'accounts' => $this->eligiblePaymentAccounts(),
            ]);
        } catch (\Exception $e) {
            return $this->exceptionResponse($e, 'Invoice Payment SAP Preview Error');
        }
    }

    public function submitSapPayment(SubmitSapInvoicePaymentRequest $request, $invoiceId, SapService $sapService): JsonResponse
    {
        try {
            $invoice = $this->invoicePayloadFromRequest($request, $invoiceId);

            if ($request->boolean('close_dds_only') && $request->boolean('close_invoice_in_dds')) {
                $existing = $this->latestSuccessfulSapPaymentLog($invoiceId);
                if ($existing) {
                    return $this->closeInvoiceInDdsAfterSap($invoice, $existing);
                }

                return response()->json([
                    'error' => 'Already posted',
                    'message' => 'No successful SAP outgoing payment was found for this invoice.',
                ], 422);
            }

            $partner = $this->resolveSupplierPartner($invoice['supplier_sap_code']);
            if (! $partner) {
                return response()->json([
                    'error' => 'Supplier not mapped',
                    'message' => "Supplier SAP code \"{$invoice['supplier_sap_code']}\" is not mapped to an active vendor in Payreq-X.",
                ], 422);
            }

            try {
                $apInvoice = $this->resolveApInvoice($sapService, $invoice);
            } catch (\RuntimeException $e) {
                return response()->json([
                    'error' => 'AP Invoice not found',
                    'message' => $e->getMessage(),
                ], 422);
            }

            $remaining = $this->remainingFromApInvoice($apInvoice);

            if ($remaining <= SapVendorPaymentBuilder::AMOUNT_TOLERANCE) {
                return response()->json([
                    'error' => 'Already posted',
                    'message' => 'This invoice is already fully paid in SAP B1. Use Close in DDS if DDS still shows it open.',
                ], 422);
            }

            $account = Account::query()
                ->selectable()
                ->whereKey($request->integer('account_id'))
                ->first();

            if (! $account || empty($account->sap_account) || ! in_array($account->type, ['cash', 'bank'], true)) {
                return response()->json([
                    'error' => 'Invalid account',
                    'message' => 'Selected account is not a valid cash/bank account with SAP mapping.',
                ], 422);
            }

            $paymentAmount = (float) $request->input('payment_amount');

            $builder = new SapVendorPaymentBuilder(
                $invoice,
                $apInvoice,
                $partner,
                $account,
                (string) $request->input('payment_means'),
                (string) $request->input('payment_date'),
                $paymentAmount,
                (string) $request->input('prepared_by'),
                (string) $request->input('approved_by'),
            );

            $errors = $builder->validate(requirePaymentAccount: true);
            if ($errors !== []) {
                return response()->json([
                    'error' => 'Validation failed',
                    'message' => implode(' ', $errors),
                ], 422);
            }

            $payload = $builder->build();

            try {
                $sapResult = $sapService->createOutgoingPayment($payload);
            } catch (\Exception $e) {
                $this->logInvoicePaymentSubmission($invoice, 'failed', $e->getMessage());

                return response()->json([
                    'error' => 'SAP submission failed',
                    'message' => $e->getMessage(),
                ], 422);
            }

            if (! ($sapResult['success'] ?? false)) {
                $message = $sapResult['message'] ?? 'Failed to create outgoing payment in SAP B1.';
                $this->logInvoicePaymentSubmission($invoice, 'failed', $message);

                return response()->json([
                    'error' => 'SAP submission failed',
                    'message' => $message,
                ], 422);
            }

            $this->logInvoicePaymentSubmission($invoice, 'success', null, $sapResult, $paymentAmount);

            $remainingAfter = $remaining - $paymentAmount;
            $shouldCloseDds = $request->boolean('close_invoice_in_dds')
                && $remainingAfter <= SapVendorPaymentBuilder::AMOUNT_TOLERANCE;

            if ($shouldCloseDds) {
                $ddsResult = $this->closeInvoiceInDds($invoice, $sapResult, $paymentAmount, $remainingAfter);
                if (! $ddsResult['success']) {
                    return response()->json([
                        'success' => true,
                        'warning' => true,
                        'message' => 'Outgoing payment posted to SAP B1 (DocNum '.($sapResult['doc_num'] ?? '-').'), but closing the invoice in DDS failed. Use PAY again to close it in DDS.',
                        'sap_doc_num' => $sapResult['doc_num'] ?? null,
                        'sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                        'payment_amount' => $paymentAmount,
                        'remaining_balance' => max(0, $remainingAfter),
                        'dds_error' => $ddsResult['message'] ?? null,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Outgoing payment posted to SAP B1 and invoice closed. DocNum: '.($sapResult['doc_num'] ?? '-'),
                    'sap_doc_num' => $sapResult['doc_num'] ?? null,
                    'sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                    'payment_amount' => $paymentAmount,
                    'remaining_balance' => max(0, $remainingAfter),
                    'fully_paid' => true,
                ]);
            }

            $this->appendSapPaymentRemarkOnly($invoice, $sapResult, $paymentAmount, $remainingAfter);

            return response()->json([
                'success' => true,
                'message' => 'Partial outgoing payment posted to SAP B1. DocNum: '.($sapResult['doc_num'] ?? '-'),
                'sap_doc_num' => $sapResult['doc_num'] ?? null,
                'sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                'payment_amount' => $paymentAmount,
                'remaining_balance' => max(0, $remainingAfter),
                'fully_paid' => false,
            ]);
        } catch (\Exception $e) {
            return $this->exceptionResponse($e, 'Invoice Payment SAP Submit Error');
        }
    }

    /**
     * @return array{status: string, message?: string, department_code?: string, valid_codes_sample?: array<string>}
     */
    private function getDepartmentValidationState(): array
    {
        if (! $this->apiUrl || ! $this->apiKey) {
            return [
                'status' => 'missing_config',
                'message' => 'DDS API URL or API key is not configured.',
            ];
        }

        if (! $this->departmentCode) {
            return [
                'status' => 'missing_department',
                'message' => 'DDS department code is not set for your user account.',
            ];
        }

        $codes = $this->fetchDepartmentLocationCodes();

        if ($codes === null) {
            return [
                'status' => 'api_error',
                'message' => 'Could not retrieve the department list from DDS. Check API URL and key.',
            ];
        }

        if (! in_array($this->departmentCode, $codes, true)) {
            return [
                'status' => 'invalid_department',
                'message' => "DDS department code \"{$this->departmentCode}\" is not recognized.",
                'department_code' => $this->departmentCode,
                'valid_codes_sample' => array_slice($codes, 0, 8),
            ];
        }

        return [
            'status' => 'ok',
            'department_code' => $this->departmentCode,
        ];
    }

    /**
     * @return array<string>
     */
    private function fetchDepartmentLocationCodes(): ?array
    {
        if ($this->departmentCodesCache !== null) {
            return $this->departmentCodesCache;
        }

        if (! $this->apiUrl || ! $this->apiKey) {
            return null;
        }

        $response = Http::withHeaders($this->ddsHeaders())
            ->get("{$this->apiUrl}/api/v1/departments");

        if (! $response->successful()) {
            Log::warning('Invoice Payment: failed to fetch DDS departments', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $departments = $response->json()['data']['departments'] ?? [];
        $this->departmentCodesCache = array_values(array_filter(
            array_column($departments, 'location_code'),
            fn ($code) => is_string($code) && $code !== ''
        ));

        return $this->departmentCodesCache;
    }

    private function departmentValidationErrorResponse(): ?JsonResponse
    {
        $state = $this->getDepartmentValidationState();

        if ($state['status'] === 'ok') {
            return null;
        }

        $statusCode = match ($state['status']) {
            'invalid_department' => 400,
            'api_error' => 502,
            default => 500,
        };

        $payload = [
            'error' => match ($state['status']) {
                'invalid_department' => 'Invalid department code',
                'api_error' => 'DDS unavailable',
                default => 'Configuration error',
            },
            'message' => $state['message'],
        ];

        if ($state['status'] === 'invalid_department') {
            $payload['department_code'] = $state['department_code'];
            $payload['valid_department_codes'] = $this->departmentCodesCache ?? [];
        }

        return response()->json($payload, $statusCode);
    }

    private function configurationErrorResponse(bool $requireDepartment = true): ?JsonResponse
    {
        $missing = [];

        if (! $this->apiUrl) {
            $missing[] = 'DDS_API_URL';
        }

        if (! $this->apiKey) {
            $missing[] = 'DDS_API_KEY';
        }

        if ($requireDepartment && ! $this->departmentCode) {
            $missing[] = 'Department Code';
        }

        if ($missing === []) {
            return null;
        }

        $message = 'Missing configuration: '.implode(', ', $missing);

        Log::error('Invoice Payment Environment Error: '.$message);

        return response()->json([
            'error' => 'Configuration error',
            'message' => $message,
        ], 500);
    }

    /**
     * @return array<string, string>
     */
    private function buildDdsQueryParams(Request $request): array
    {
        return array_filter([
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'project' => $request->input('project'),
            'supplier' => $request->input('supplier'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array<string, string>
     */
    private function ddsHeaders(): array
    {
        return [
            'X-API-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ];
    }

    private function ddsFailureResponse($response, string $message): JsonResponse
    {
        Log::warning('Invoice Payment DDS request failed', [
            'message' => $message,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return response()->json([
            'error' => $message,
            'status' => $response->status(),
            'response_body' => $response->body(),
        ], $response->status() >= 400 && $response->status() < 600 ? $response->status() : 500);
    }

    private function exceptionResponse(\Exception $e, string $context): JsonResponse
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

    /**
     * @param  array<int, array<string, mixed>>  $invoices
     * @return array<string, mixed>
     */
    private function calculateDashboardData(array $invoices): array
    {
        $waitingInvoices = 0;
        $paidInvoices = 0;
        $totalWaitingAmount = 0;
        $totalPaidAmount = 0;
        $overdueInvoices = 0;
        $totalOverdueAmount = 0;

        foreach ($invoices as $invoice) {
            $amount = (float) ($invoice['amount'] ?? 0);

            if ($this->isInvoicePaid($invoice)) {
                $paidInvoices++;
                $totalPaidAmount += $amount;

                continue;
            }

            $waitingInvoices++;
            $totalWaitingAmount += $amount;

            $daysDiff = $this->calculateDaysDifference($invoice['receive_date'] ?? null);

            if (($invoice['status'] ?? '') === 'overdue' || $daysDiff > 30) {
                $overdueInvoices++;
                $totalOverdueAmount += $amount;
            }
        }

        return [
            'total_invoices' => count($invoices),
            'waiting_invoices' => $waitingInvoices,
            'paid_invoices' => $paidInvoices,
            'total_waiting_amount' => number_format($totalWaitingAmount, 2),
            'total_paid_amount' => number_format($totalPaidAmount, 2),
            'overdue_invoices' => $overdueInvoices,
            'total_overdue_amount' => number_format($totalOverdueAmount, 2),
            'currency' => 'IDR',
        ];
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function isInvoicePaid(array $invoice): bool
    {
        return ! empty($invoice['payment_date']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $invoices
     * @return array<int, array<string, mixed>>
     */
    private function addDaysCalculation(array $invoices): array
    {
        $processedInvoices = [];

        foreach ($invoices as $invoice) {
            $daysDiff = $this->calculateDaysDifference($invoice['receive_date'] ?? null);
            $processedInvoices[] = array_merge($invoice, ['days_diff' => $daysDiff]);
        }

        usort($processedInvoices, fn ($a, $b) => $b['days_diff'] <=> $a['days_diff']);

        return $processedInvoices;
    }

    /**
     * @param  array<int, array<string, mixed>>  $invoices
     * @return array<int, array<string, mixed>>
     */
    private function filterInvoicesBySearch(array $invoices, string $search): array
    {
        $search = strtolower($search);

        return array_filter($invoices, function ($invoice) use ($search) {
            $fields = [
                $invoice['invoice_number'] ?? '',
                $invoice['supplier_name'] ?? '',
                $invoice['receive_project'] ?? '',
                $invoice['invoice_project'] ?? '',
                $invoice['payment_project'] ?? '',
            ];

            foreach ($fields as $field) {
                if (str_contains(strtolower((string) $field), $search)) {
                    return true;
                }
            }

            return false;
        });
    }

    private function calculateDaysDifference(?string $receiveDate): int
    {
        if (! $receiveDate) {
            return 0;
        }

        return (int) round(Carbon::parse($receiveDate)->diffInDays(Carbon::now()));
    }

    /**
     * @param  array<int, array<string, mixed>>  $invoices
     * @return array<int, array<string, mixed>>
     */
    private function attachSapPaymentStatus(array $invoices): array
    {
        $ids = array_values(array_filter(array_map(
            fn ($invoice) => $invoice['id'] ?? null,
            $invoices
        )));

        if ($ids === []) {
            return $invoices;
        }

        $logs = SapSubmissionLog::query()
            ->where('document_type', SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT)
            ->whereIn('dds_invoice_id', $ids)
            ->where('status', 'success')
            ->orderByDesc('id')
            ->get()
            ->groupBy('dds_invoice_id');

        return array_map(function ($invoice) use ($logs) {
            $invoiceLogs = $logs->get($invoice['id'] ?? null);
            $invoice['sap_payment'] = $this->buildSapPaymentSummary($invoice, $invoiceLogs);

            return $invoice;
        }, $invoices);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SapSubmissionLog>|null  $logs
     * @return array<string, mixed>|null
     */
    private function buildSapPaymentSummary(array $invoice, $logs): ?array
    {
        if ($logs === null || $logs->isEmpty()) {
            return null;
        }

        $latest = $logs->first();
        $totalPaid = (float) $logs->sum(fn (SapSubmissionLog $log) => (float) ($log->amount ?? 0));
        $invoiceAmount = (float) ($invoice['amount'] ?? 0);
        $paymentCount = $logs->count();
        $tolerance = SapVendorPaymentBuilder::AMOUNT_TOLERANCE;

        return [
            'status' => $latest->status,
            'doc_num' => $latest->sap_doc_num,
            'doc_entry' => $latest->sap_doc_entry,
            'error_message' => $latest->error_message,
            'total_paid' => $totalPaid,
            'payment_count' => $paymentCount,
            'invoice_amount' => $invoiceAmount,
            'is_fully_paid' => $invoiceAmount > 0 && $totalPaid >= $invoiceAmount - $tolerance,
            'is_partial' => $paymentCount > 0 && ($invoiceAmount <= 0 || $totalPaid < $invoiceAmount - $tolerance),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePayloadFromRequest(Request $request, $invoiceId): array
    {
        return [
            'id' => (int) $invoiceId,
            'invoice_number' => (string) $request->input('invoice_number'),
            'supplier_sap_code' => (string) $request->input('supplier_sap_code'),
            'amount' => $request->input('amount'),
            'payment_date' => $request->input('payment_date'),
            'remarks' => $request->input('remarks'),
            'payment_project' => $request->input('payment_project'),
            'sap_doc' => $request->input('sap_doc'),
        ];
    }

    private function resolveSupplierPartner(string $supplierSapCode): ?SapBusinessPartner
    {
        return SapBusinessPartner::query()
            ->suppliers()
            ->where('code', $supplierSapCode)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @return array{DocEntry: int, DocNum?: int|string, CardCode?: string, DocumentStatus?: string, Cancelled?: string, NumAtCard?: string, DocTotal?: float, PaidToDate?: float}
     */
    private function resolveApInvoice(SapService $sapService, array $invoice): array
    {
        $invoiceNumber = trim((string) ($invoice['invoice_number'] ?? ''));
        $sapDoc = trim((string) ($invoice['sap_doc'] ?? ''));

        $candidates = [];
        if ($sapDoc !== '') {
            $candidates[] = fn () => $sapService->getPurchaseInvoiceByDocNum($sapDoc);
        }
        if ($invoiceNumber !== '') {
            $candidates[] = fn () => $sapService->getPurchaseInvoiceByNumAtCard($invoiceNumber);
        }

        foreach ($candidates as $lookup) {
            $document = $lookup();
            if ($document) {
                return $document;
            }
        }

        throw new \RuntimeException(
            'Linked SAP AP Invoice could not be found for invoice '.$invoiceNumber.'. Re-post the AP Invoice to SAP or verify the document still exists in SAP B1.'
        );
    }

    private function latestSuccessfulSapPaymentLog(int|string $invoiceId): ?SapSubmissionLog
    {
        return SapSubmissionLog::query()
            ->where('document_type', SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT)
            ->where('dds_invoice_id', $invoiceId)
            ->where('status', 'success')
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $apInvoice
     */
    private function remainingFromApInvoice(array $apInvoice): float
    {
        $docTotal = (float) ($apInvoice['DocTotal'] ?? 0);
        $paidToDate = (float) ($apInvoice['PaidToDate'] ?? 0);

        return max(0.0, $docTotal - $paidToDate);
    }

    /**
     * @return list<array{date: string|null, amount: float|null, doc_num: string|null, doc_entry: int|null}>
     */
    private function paymentHistoryForInvoice(int|string $invoiceId): array
    {
        return SapSubmissionLog::query()
            ->where('document_type', SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT)
            ->where('dds_invoice_id', $invoiceId)
            ->where('status', 'success')
            ->orderBy('id')
            ->get(['created_at', 'amount', 'sap_doc_num', 'sap_doc_entry'])
            ->map(fn (SapSubmissionLog $log) => [
                'date' => $log->created_at?->format('Y-m-d'),
                'amount' => $log->amount !== null ? (float) $log->amount : null,
                'doc_num' => $log->sap_doc_num,
                'doc_entry' => $log->sap_doc_entry,
            ])
            ->values()
            ->all();
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
     * @param  array<string, mixed>  $invoice
     * @param  array<string, mixed>|null  $sapResult
     */
    private function logInvoicePaymentSubmission(
        array $invoice,
        string $status,
        ?string $errorMessage,
        ?array $sapResult = null,
        ?float $paymentAmount = null,
    ): void {
        SapSubmissionLog::create([
            'dds_invoice_id' => $invoice['id'] ?? null,
            'dds_invoice_number' => $invoice['invoice_number'] ?? null,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_INVOICE_PAYMENT,
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

    /**
     * @param  array<string, mixed>  $invoice
     * @param  array<string, mixed>  $sapResult
     * @return array{success: bool, message?: string}
     */
    private function closeInvoiceInDds(
        array $invoice,
        array $sapResult,
        ?float $paymentAmount = null,
        ?float $remainingAfter = null,
    ): array {
        if (! $this->apiUrl || ! $this->apiKey) {
            return [
                'success' => false,
                'message' => 'DDS API is not configured.',
            ];
        }

        $invoiceId = $invoice['id'] ?? null;
        $remarks = $this->buildDdsRemarksWithSapNote($invoice, $sapResult, $paymentAmount, $remainingAfter);

        try {
            $response = Http::withHeaders($this->ddsHeaders())
                ->put("{$this->apiUrl}/api/v1/invoices/{$invoiceId}/payment", array_filter([
                    'payment_date' => $invoice['payment_date'] ?? Carbon::today()->format('Y-m-d'),
                    'payment_status' => 'paid',
                    'status' => 'closed',
                    'remarks' => $remarks,
                    'payment_project' => $invoice['payment_project'] ?? null,
                ], fn ($value) => $value !== null && $value !== ''));

            if (! $response->successful()) {
                Log::warning('Invoice Payment: DDS close failed after SAP posting', [
                    'invoice_id' => $invoiceId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to close invoice in DDS.',
                ];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::warning('Invoice Payment: DDS close exception after SAP posting', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function closeInvoiceInDdsAfterSap(array $invoice, SapSubmissionLog $existing): JsonResponse
    {
        $sapResult = [
            'doc_num' => $existing->sap_doc_num,
            'doc_entry' => $existing->sap_doc_entry,
        ];

        $ddsResult = $this->closeInvoiceInDds($invoice, $sapResult);
        if (! $ddsResult['success']) {
            return response()->json([
                'error' => 'DDS close failed',
                'message' => $ddsResult['message'] ?? 'Failed to close invoice in DDS.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice closed in DDS. SAP OP #'.($existing->sap_doc_num ?? '-').' was already posted.',
            'sap_doc_num' => $existing->sap_doc_num,
            'sap_doc_entry' => $existing->sap_doc_entry,
        ]);
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @param  array<string, mixed>  $sapResult
     */
    private function appendSapPaymentRemarkOnly(
        array $invoice,
        array $sapResult,
        ?float $paymentAmount = null,
        ?float $remainingAfter = null,
    ): void {
        if (! $this->apiUrl || ! $this->apiKey) {
            return;
        }

        $invoiceId = $invoice['id'] ?? null;
        $remarks = $this->buildDdsRemarksWithSapNote($invoice, $sapResult, $paymentAmount, $remainingAfter);

        try {
            $response = Http::withHeaders($this->ddsHeaders())
                ->put("{$this->apiUrl}/api/v1/invoices/{$invoiceId}/payment", array_filter([
                    'remarks' => $remarks,
                ], fn ($value) => $value !== null && $value !== ''));

            if (! $response->successful()) {
                Log::warning('Invoice Payment: DDS remarks write-back failed after SAP posting', [
                    'invoice_id' => $invoiceId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Invoice Payment: DDS remarks write-back exception after SAP posting', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @param  array<string, mixed>  $sapResult
     */
    private function buildDdsRemarksWithSapNote(
        array $invoice,
        array $sapResult,
        ?float $paymentAmount = null,
        ?float $remainingAfter = null,
    ): string {
        $docNum = (string) ($sapResult['doc_num'] ?? '');
        $docEntry = $sapResult['doc_entry'] ?? null;
        $note = 'SAP OP #'.($docNum !== '' ? $docNum : '-');
        if ($docEntry !== null && $docEntry !== '') {
            $note .= ' (DocEntry '.$docEntry.')';
        }

        if ($paymentAmount !== null) {
            $note .= ' | Paid Rp '.number_format($paymentAmount, 0, ',', '.');
        }

        if ($remainingAfter !== null && $remainingAfter > SapVendorPaymentBuilder::AMOUNT_TOLERANCE) {
            $note .= ' | Remaining Rp '.number_format($remainingAfter, 0, ',', '.');
        }

        $existing = trim((string) ($invoice['remarks'] ?? ''));

        if ($existing === '' || str_contains($existing, $note)) {
            return $existing !== '' ? $existing : $note;
        }

        return $existing.' | '.$note;
    }
}
