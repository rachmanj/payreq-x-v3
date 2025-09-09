<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvoicePaymentController extends Controller
{
    protected $apiUrl;
    protected $apiKey;
    protected $departmentCode;

    public function __construct()
    {
        $this->apiUrl = env('DDS_API_URL');
        $this->apiKey = env('DDS_API_KEY');

        // First try to get department code from authenticated user
        if (auth()->check() && auth()->user()->dds_department_code) {
            $this->departmentCode = auth()->user()->dds_department_code;
        } else {
            // Fall back to environment variable
            $this->departmentCode = env('DDS_DEPARTMENT_CODE');
        }
    }

    public function index()
    {
        return view('invoice-payment.index');
    }

    public function dashboard()
    {
        try {
            // Debug logging
            $debugInfo = [
                'api_url' => $this->apiUrl,
                'api_key' => $this->apiKey ? '***' . substr($this->apiKey, -4) : 'NOT_SET',
                'department_code' => $this->departmentCode,
                'department_code_source' => auth()->check() && auth()->user()->dds_department_code ? 'user' : 'env',
                'full_url' => "{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/invoices",
                'headers' => [
                    'X-API-Key' => $this->apiKey ? '***' . substr($this->apiKey, -4) : 'NOT_SET',
                    'Accept' => 'application/json'
                ]
            ];

            Log::info('Invoice Payment Dashboard Debug Info:', $debugInfo);

            if (!$this->apiUrl || !$this->apiKey || !$this->departmentCode) {
                $error = 'Missing configuration: ' .
                    (!$this->apiUrl ? 'DDS_API_URL ' : '') .
                    (!$this->apiKey ? 'DDS_API_KEY ' : '') .
                    (!$this->departmentCode ? 'Department Code' : '');
                Log::error('Invoice Payment Environment Error: ' . $error);
                return response()->json([
                    'error' => 'Configuration error',
                    'message' => $error,
                    'debug_info' => $debugInfo
                ], 500);
            }

            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json'
            ])->get("{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/invoices");

            // Log response details
            Log::info('Invoice Payment API Response:', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'url' => "{$this->apiUrl}/departments/{$this->departmentCode}/invoices"
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $invoices = $data['data']['invoices'] ?? [];

                $dashboardData = $this->calculateDashboardData($invoices);
                return response()->json($dashboardData);
            }

            return response()->json([
                'error' => 'Failed to fetch data',
                'status' => $response->status(),
                'response_body' => $response->body(),
                'debug_info' => $debugInfo
            ], 500);
        } catch (\Exception $e) {
            Log::error('Invoice Payment Dashboard Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'debug_info' => $debugInfo ?? []
            ], 500);
        }
    }

    public function waitingPayment(Request $request)
    {
        try {
            // Debug logging
            $debugInfo = [
                'api_url' => $this->apiUrl,
                'api_key' => $this->apiKey ? '***' . substr($this->apiKey, -4) : 'NOT_SET',
                'department_code' => $this->departmentCode,
                'department_code_source' => auth()->check() && auth()->user()->dds_department_code ? 'user' : 'env',
                'full_url' => "{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/wait-payment-invoices",
                'query_params' => $request->all(),
                'headers' => [
                    'X-API-Key' => $this->apiKey ? '***' . substr($this->apiKey, -4) : 'NOT_SET',
                    'Accept' => 'application/json'
                ]
            ];

            Log::info('Invoice Payment Waiting Payment Debug Info:', $debugInfo);

            if (!$this->apiUrl || !$this->apiKey || !$this->departmentCode) {
                $error = 'Missing configuration: ' .
                    (!$this->apiUrl ? 'DDS_API_URL ' : '') .
                    (!$this->apiKey ? 'DDS_API_KEY ' : '') .
                    (!$this->departmentCode ? 'Department Code' : '');
                Log::error('Invoice Payment Environment Error: ' . $error);
                return response()->json([
                    'error' => 'Configuration error',
                    'message' => $error,
                    'debug_info' => $debugInfo
                ], 500);
            }

            $queryParams = [];

            if ($request->filled('status')) {
                $queryParams['status'] = $request->status;
            }
            if ($request->filled('date_from')) {
                $queryParams['date_from'] = $request->date_from;
            }
            if ($request->filled('date_to')) {
                $queryParams['date_to'] = $request->date_to;
            }

            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json'
            ])->get("{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/wait-payment-invoices", $queryParams);

            // Log response details
            Log::info('Invoice Payment Waiting Payment API Response:', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'url' => "{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/wait-payment-invoices",
                'query_params' => $queryParams
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $invoices = $data['data']['invoices'] ?? [];

                // No need to filter since the API already returns waiting payment invoices
                $waitingInvoices = $this->addDaysCalculation($invoices);

                // Apply search filter if provided
                if ($request->filled('search')) {
                    $search = strtolower($request->search);
                    $waitingInvoices = array_filter($waitingInvoices, function ($invoice) use ($search) {
                        return str_contains(strtolower($invoice['invoice_number']), $search) ||
                            str_contains(strtolower($invoice['supplier_name']), $search) ||
                            str_contains(strtolower($invoice['receive_project']), $search) ||
                            str_contains(strtolower($invoice['invoice_project']), $search) ||
                            str_contains(strtolower($invoice['payment_project']), $search);
                    });
                }

                return response()->json(['invoices' => array_values($waitingInvoices)]);
            }

            return response()->json([
                'error' => 'Failed to fetch data',
                'status' => $response->status(),
                'response_body' => $response->body(),
                'debug_info' => $debugInfo
            ], 500);
        } catch (\Exception $e) {
            Log::error('Invoice Payment Waiting Payment Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'debug_info' => $debugInfo ?? []
            ], 500);
        }
    }

    public function paidInvoices(Request $request)
    {
        try {
            // Debug logging
            $debugInfo = [
                'api_url' => $this->apiUrl,
                'api_key' => $this->apiKey ? '***' . substr($this->apiKey, -4) : 'NOT_SET',
                'department_code' => $this->departmentCode,
                'department_code_source' => auth()->check() && auth()->user()->dds_department_code ? 'user' : 'env',
                'full_url' => "{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/invoices",
                'query_params' => $request->all(),
                'headers' => [
                    'X-API-Key' => $this->apiKey ? '***' . substr($this->apiKey, -4) : 'NOT_SET',
                    'Accept' => 'application/json'
                ]
            ];

            Log::info('Invoice Payment Paid Invoices Debug Info:', $debugInfo);

            if (!$this->apiUrl || !$this->apiKey || !$this->departmentCode) {
                $error = 'Missing configuration: ' .
                    (!$this->apiUrl ? 'DDS_API_URL ' : '') .
                    (!$this->apiKey ? 'DDS_API_KEY ' : '') .
                    (!$this->departmentCode ? 'Department Code' : '');
                Log::error('Invoice Payment Environment Error: ' . $error);
                return response()->json([
                    'error' => 'Configuration error',
                    'message' => $error,
                    'debug_info' => $debugInfo
                ], 500);
            }

            $queryParams = [];

            if ($request->filled('status')) {
                $queryParams['status'] = $request->status;
            }
            if ($request->filled('date_from')) {
                $queryParams['date_from'] = $request->date_from;
            }
            if ($request->filled('date_to')) {
                $queryParams['date_to'] = $request->date_to;
            }

            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json'
            ])->get("{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/paid-invoices", $queryParams);

            // Log response details
            Log::info('Invoice Payment Paid Invoices API Response:', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'url' => "{$this->apiUrl}/api/v1/departments/{$this->departmentCode}/paid-invoices",
                'query_params' => $queryParams
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $invoices = $data['data']['invoices'] ?? [];

                // No need to filter since the API already returns paid invoices
                $paidInvoices = $this->addDaysCalculation($invoices);

                // Apply search filter if provided
                if ($request->filled('search')) {
                    $search = strtolower($request->search);
                    $paidInvoices = array_filter($paidInvoices, function ($invoice) use ($search) {
                        return str_contains(strtolower($invoice['invoice_number']), $search) ||
                            str_contains(strtolower($invoice['supplier_name']), $search) ||
                            str_contains(strtolower($invoice['receive_project']), $search) ||
                            str_contains(strtolower($invoice['invoice_project']), $search) ||
                            str_contains(strtolower($invoice['payment_project']), $search);
                    });
                }

                return response()->json(['invoices' => array_values($paidInvoices)]);
            }

            return response()->json([
                'error' => 'Failed to fetch data',
                'status' => $response->status(),
                'response_body' => $response->body(),
                'debug_info' => $debugInfo
            ], 500);
        } catch (\Exception $e) {
            Log::error('Invoice Payment Paid Invoices Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'debug_info' => $debugInfo ?? []
            ], 500);
        }
    }

    private function calculateDashboardData($invoices)
    {
        $totalInvoices = count($invoices);
        $waitingInvoices = 0;
        $paidInvoices = 0;
        $totalWaitingAmount = 0;
        $totalPaidAmount = 0;
        $overdueInvoices = 0;
        $totalOverdueAmount = 0;

        foreach ($invoices as $invoice) {
            $daysDiff = $this->calculateDaysDifference($invoice['receive_date']);

            if ($invoice['status'] === 'open' || $invoice['status'] === 'pending') {
                $waitingInvoices++;
                $totalWaitingAmount += $invoice['amount'];

                if ($daysDiff > 30) { // Consider overdue after 30 days
                    $overdueInvoices++;
                    $totalOverdueAmount += $invoice['amount'];
                }
            } elseif ($invoice['status'] === 'closed' || $invoice['status'] === 'paid') {
                $paidInvoices++;
                $totalPaidAmount += $invoice['amount'];
            }
        }

        return [
            'total_invoices' => $totalInvoices,
            'waiting_invoices' => $waitingInvoices,
            'paid_invoices' => $paidInvoices,
            'total_waiting_amount' => number_format($totalWaitingAmount, 2),
            'total_paid_amount' => number_format($totalPaidAmount, 2),
            'overdue_invoices' => $overdueInvoices,
            'total_overdue_amount' => number_format($totalOverdueAmount, 2),
            'currency' => 'IDR' // Assuming IDR based on your system
        ];
    }

    private function addDaysCalculation($invoices)
    {
        $processedInvoices = [];

        foreach ($invoices as $invoice) {
            $daysDiff = $this->calculateDaysDifference($invoice['receive_date']);
            $processedInvoices[] = array_merge($invoice, ['days_diff' => $daysDiff]);
        }

        // Sort by days difference (oldest first for waiting, most recent first for paid)
        usort($processedInvoices, function ($a, $b) {
            return $b['days_diff'] <=> $a['days_diff']; // Oldest first
        });

        return $processedInvoices;
    }

    private function calculateDaysDifference($receiveDate)
    {
        $receive = Carbon::parse($receiveDate);
        $now = Carbon::now();
        return round($receive->diffInDays($now));
    }

    public function updatePayment(Request $request, $invoiceId)
    {
        try {
            // Debug logging
            $debugInfo = [
                'api_url' => $this->apiUrl,
                'api_key' => $this->apiKey ? '***' . substr($this->apiKey, -4) : 'NOT_SET',
                'invoice_id' => $invoiceId,
                'full_url' => "{$this->apiUrl}/api/v1/invoices/{$invoiceId}/payment",
                'request_data' => $request->all(),
                'headers' => [
                    'X-API-Key' => $this->apiKey ? '***' . substr($this->apiKey, -4) : 'NOT_SET',
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ]
            ];

            Log::info('Invoice Payment Update Debug Info:', $debugInfo);

            if (!$this->apiUrl || !$this->apiKey) {
                $error = 'Missing environment variables: ' .
                    (!$this->apiUrl ? 'DDS_API_URL ' : '') .
                    (!$this->apiKey ? 'DDS_API_KEY' : '');
                Log::error('Invoice Payment Environment Error: ' . $error);
                return response()->json([
                    'error' => 'Configuration error',
                    'message' => $error,
                    'debug_info' => $debugInfo
                ], 500);
            }

            // Validate required fields
            $request->validate([
                'payment_date' => 'required|date|date_format:Y-m-d',
                'status' => 'nullable|in:open,closed,overdue,cancelled,paid',
                'remarks' => 'nullable|string|max:500',
                'payment_project' => 'nullable|string|max:50',
                'sap_doc' => 'nullable|string|max:100'
            ]);

            $paymentData = [
                'payment_date' => $request->payment_date,
                'payment_status' => $request->status ?? 'paid',
                'remarks' => $request->remarks,
                'payment_project' => $request->payment_project,
                'sap_doc' => $request->sap_doc
            ];

            // Remove null values
            $paymentData = array_filter($paymentData, function ($value) {
                return $value !== null;
            });

            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->put("{$this->apiUrl}/api/v1/invoices/{$invoiceId}/payment", $paymentData);

            // Log response details
            Log::info('Invoice Payment Update API Response:', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'url' => "{$this->apiUrl}/api/v1/invoices/{$invoiceId}/payment",
                'request_data' => $paymentData
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => 'Payment updated successfully',
                    'data' => $data['data'] ?? []
                ]);
            }

            return response()->json([
                'error' => 'Failed to update payment',
                'status' => $response->status(),
                'response_body' => $response->body(),
                'debug_info' => $debugInfo
            ], 500);
        } catch (\Exception $e) {
            Log::error('Invoice Payment Update Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'debug_info' => $debugInfo ?? []
            ], 500);
        }
    }
}
