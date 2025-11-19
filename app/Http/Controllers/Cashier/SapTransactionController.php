<?php

namespace App\Http\Controllers\Cashier;

use App\Exceptions\SapBridgeException;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\SapBridge\AccountStatementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class SapTransactionController extends Controller
{
    public function __construct(protected AccountStatementService $accountStatementService) {}

    public function index()
    {
        $accounts = Account::where('project', auth()->user()->project)
            ->whereIn('type', ['cash', 'bank'])
            ->select('account_number', 'account_name')
            ->orderBy('account_number')
            ->get();

        return view('cashier.sap-transactions.index', compact('accounts'));
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_code' => ['required', 'string'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $this->ensureDateRangeLimit($validated['start_date'], $validated['end_date']);

        try {
            $statement = $this->accountStatementService->getAccountStatement(
                $validated['account_code'],
                $validated['start_date'],
                $validated['end_date']
            );
        } catch (SapBridgeException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), $exception->getStatusCode());
        } catch (\Throwable $exception) {
            report($exception);

            return $this->errorResponse($request, 'Failed to fetch data', 500);
        }

        $transactions = data_get($statement, 'transactions', []);

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => count($transactions),
            'recordsFiltered' => count($transactions),
            'data' => $transactions,
            'account' => data_get($statement, 'account'),
            'opening_balance' => data_get($statement, 'opening_balance'),
            'closing_balance' => data_get($statement, 'closing_balance'),
            'summary' => data_get($statement, 'summary'),
            'start_date' => data_get($statement, 'start_date'),
            'end_date' => data_get($statement, 'end_date'),
        ]);
    }

    protected function ensureDateRangeLimit(string $startDate, string $endDate): void
    {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        if ($start->copy()->addMonthsNoOverflow(6)->lt($end)) {
            throw ValidationException::withMessages([
                'end_date' => ['Date range cannot exceed 6 months.'],
            ]);
        }
    }

    protected function errorResponse(Request $request, string $message, int $status): JsonResponse
    {
        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $message,
        ], $status);
    }
}
