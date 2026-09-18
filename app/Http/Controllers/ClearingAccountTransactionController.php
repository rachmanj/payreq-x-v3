<?php

namespace App\Http\Controllers;

use App\Services\ClearingAccountMonitorService;
use App\Services\SapService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClearingAccountTransactionController extends Controller
{
    public function __construct(
        protected ClearingAccountMonitorService $clearingAccountMonitor,
        protected SapService $sapService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null || (! $user->can('view_accounting_manager_dashboard') && ! $user->can('cashier_dashboard'))) {
            abort(403);
        }

        $monitoredCodes = $this->clearingAccountMonitor->getMonitoredAccountCodes();

        $validated = $request->validate([
            'account_code' => [
                'required',
                'string',
                Rule::in($monitoredCodes),
            ],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ], [
            'account_code.in' => 'Akun tidak dipantau',
        ]);

        if ($monitoredCodes === []) {
            throw ValidationException::withMessages([
                'account_code' => ['Akun tidak dipantau'],
            ]);
        }

        $this->ensureDateRangeLimit($validated['start_date'], $validated['end_date']);

        try {
            $statement = $this->sapService->getAccountStatement(
                $validated['account_code'],
                $validated['start_date'],
                $validated['end_date'],
            );
        } catch (\Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                $request,
                $exception->getMessage() ?: 'Failed to fetch data from SAP B1.',
                500,
            );
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
