<?php

namespace App\Http\Controllers;

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
}
