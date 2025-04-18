<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Incoming;
use App\Models\Outgoing;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CashStatementExport;
use App\Models\Payreq;

class CashOnHandTransactionController extends Controller
{
    public function index()
    {
        $cash_account = Account::where('project', auth()->user()->project)
            ->where('type', 'cash')
            ->select('id', 'account_number', 'account_name')
            ->first();

        return view('cashier.cashonhand.index', compact('cash_account'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $account = Account::findOrFail($request->account_id);
        
        $statementData = $this->generateStatement($account->id, $request->start_date, $request->end_date);

        return view('cashier.cashonhand.statement', $statementData);
    }

    /**
     * Get incomings data for the user's project
     */
    public function getIncomings(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
            'year' => 'required|string'
        ]);

        // Convert month and year to integers
        $month = (int) $request->month;
        $year = (int) $request->year;
        
        $project = auth()->user()->project;
        
        // Calculate first and last day of the month
        $startDate = "{$year}-{$month}-01";
        $lastDay = date('t', strtotime($startDate)); // Get number of days in month
        $endDate = "{$year}-{$month}-{$lastDay}";
        
        $incomings = Incoming::with(['cashier', 'realization.requestor'])
            ->whereBetween('receive_date', [$startDate, $endDate])
            ->where('project', $project)
            ->whereNotNull('receive_date') // Only include received incomings
            ->get()
            ->map(function($incoming) {
                return [
                    'id' => $incoming->id,
                    'receive_date' => $incoming->receive_date,
                    'nomor' => $incoming->nomor,
                    'description' => $incoming->description,
                    'project' => $incoming->project,
                    'amount' => $incoming->amount,
                    'cashier' => $incoming->cashier->name ?? 'N/A',
                    'from_user' => $incoming->realization && $incoming->realization->requestor 
                        ? $incoming->realization->requestor->name 
                        : 'N/A'
                ];
            });
            
        return response()->json([
            'data' => $incomings
        ]);
    }

    /**
     * Get outgoings data for the user's project
     */
    public function getOutgoings(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
            'year' => 'required|string'
        ]);

        // Convert month and year to integers
        $month = (int) $request->month;
        $year = (int) $request->year;
        
        $project = auth()->user()->project;
        
        // Calculate first and last day of the month
        $startDate = "{$year}-{$month}-01";
        $lastDay = date('t', strtotime($startDate)); // Get number of days in month
        $endDate = "{$year}-{$month}-{$lastDay}";
        
        $outgoings = Outgoing::with(['payreq.requestor', 'cashier'])
            ->whereBetween('outgoing_date', [$startDate, $endDate])
            ->where('project', $project)
            ->whereNotNull('outgoing_date') // Only include paid outgoings
            ->get()
            ->map(function($outgoing) {
                return [
                    'id' => $outgoing->id,
                    'outgoing_date' => $outgoing->outgoing_date,
                    'payreq_id' => $outgoing->payreq_id,
                    'payreq_nomor' => $outgoing->payreq->nomor ?? 'N/A',
                    'description' => $outgoing->full_description,
                    'project' => $outgoing->project,
                    'amount' => $outgoing->amount,
                    'sap_journal_no' => $outgoing->sap_journal_no,
                    'cashier' => $outgoing->cashier->name ?? 'N/A',
                    'to_user' => $outgoing->payreq && $outgoing->payreq->requestor 
                        ? $outgoing->payreq->requestor->name 
                        : 'N/A'
                ];
            });
            
        return response()->json([
            'data' => $outgoings
        ]);
    }

    /**
     * Export cash statement to Excel
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $account = Account::findOrFail($request->account_id);
        $statementData = $this->generateStatement($account->id, $request->start_date, $request->end_date);
        
        $filename = 'Cash_Statement_' . $account->account_number . '_' . 
                    date('Ymd', strtotime($request->start_date)) . '_' . 
                    date('Ymd', strtotime($request->end_date)) . '.xlsx';
        
        return Excel::download(new CashStatementExport($statementData), $filename);
    }

    /**
     * Fetch cash statement data via AJAX
     */
    public function getData(Request $request)
    {
        $request->validate([
            'account_number' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        // Extract account ID from the input value (format: 'account_number - account_name')
        $accountParts = explode(' - ', $request->account_number);
        $accountNumber = trim($accountParts[0]);

        $account = Account::where('account_number', $accountNumber)->first();

        if (!$account) {
            return response()->json([
                'error' => 'Account not found'
            ], 404);
        }

        // Get opening balance
        $openingBalance = $this->calculateOpeningBalance($account, $request->start_date);
        
        // Get combined cash transactions
        $transactions = $this->getCombinedCashTransactions($request->start_date, $request->end_date);
        
        // Calculate running balance
        $runningBalance = $openingBalance;
        $formattedTransactions = [];
        
        // Add opening balance line
        $formattedTransactions[] = [
            'date' => $this->formatDate($request->start_date),
            'description' => 'Opening Balance',
            'doc_num' => '',
            'doc_type' => '',
            'project_code' => '',
            'debit' => $this->formatNumber(0),
            'credit' => $this->formatNumber(0),
            'balance' => $this->formatNumber($openingBalance)
        ];

        // Process each transaction
        foreach ($transactions as $transaction) {
            $amount = floatval($transaction['amount']);
            $debitAmount = 0;
            $creditAmount = 0;
            
            // For cash accounts: incoming = debit, outgoing = credit
            if ($transaction['transaction_type'] === 'incoming') {
                $debitAmount = $amount;
                $runningBalance += $amount; // Incomings increase the balance
            } else {
                $creditAmount = $amount;
                $runningBalance -= $amount; // Outgoings decrease the balance
            }
            
            // Format the transaction for display
            $formattedTransactions[] = [
                'date' => $this->formatDate($transaction['transaction_date']),
                'description' => $transaction['description'],
                'doc_num' => $transaction['document_number'],
                'doc_type' => $transaction['transaction_type'],
                'project_code' => $transaction['project'],
                'debit' => $this->formatNumber($debitAmount),
                'credit' => $this->formatNumber($creditAmount),
                'balance' => $this->formatNumber($runningBalance)
            ];
        }
        
        return response()->json([
            'account' => [
                'account_number' => $account->account_number,
                'name' => $account->account_name
            ],
            'data' => $formattedTransactions
        ]);
    }

    /**
     * Format a number using Indonesian locale
     * 
     * @param float $number 
     * @return string
     */
    private function formatNumber($number)
    {
        // Format number with 2 decimal places using Indonesian locale
        return number_format($number, 2, ',', '.');
    }

    /**
     * Format a date in the format "23-Jun-2025"
     * 
     * @param string $date The date string in Y-m-d format
     * @return string
     */
    private function formatDate($date)
    {
        return Carbon::parse($date)->format('d-M-Y');
    }

    /**
     * Generate account statement data
     * 
     * @param int $accountId
     * @param string $startDate
     * @param string $endDate
     * @param string|null $projectCode
     * @return array
     */
    private function generateStatement($accountId, $startDate, $endDate)
    {
        $account = Account::findOrFail($accountId);
        
        // Get opening balance (account opening balance + transactions before start date)
        $openingBalance = $this->calculateOpeningBalance($account, $startDate);
        
        // Get transactions within the period
        $combinedTransactions = $this->getCombinedCashTransactions($startDate, $endDate);
        
        // Calculate running balance
        $runningBalance = $openingBalance;
        $statementLines = collect();
        
        // Prepare and add opening balance line
        $openingLine = [
            'date' => $startDate,
            'description' => 'Opening Balance',
            'doc_num' => '',
            'doc_type' => '',
            'project_code' => '',
        ];
        
        // For view, use formatted values with Indonesian locale
        $openingLine['debit'] = $this->formatNumber(0);
        $openingLine['credit'] = $this->formatNumber(0);
        $openingLine['balance'] = $this->formatNumber($openingBalance);
        
        
        $statementLines->push($openingLine);

        // Process each transaction
        foreach ($combinedTransactions as $transaction) {
            $amount = floatval($transaction['amount']);
            $debitAmount = 0;
            $creditAmount = 0;
            
            // For cash accounts: incoming = debit, outgoing = credit
            if ($transaction['transaction_type'] === 'incoming') {
                $debitAmount = $amount;
                $runningBalance += $amount; // Incomings increase the balance
            } else {
                $creditAmount = $amount;
                $runningBalance -= $amount; // Outgoings decrease the balance
            }

            $line = [
                'date' => $transaction['transaction_date'],
                'description' => $transaction['description'],
                'doc_num' => $transaction['document_number'],
                'doc_type' => $transaction['transaction_type'],
                'project_code' => $transaction['project'],
            ];
            
            // For view, use formatted values with Indonesian locale
            $line['debit'] = $this->formatNumber($debitAmount);
            $line['credit'] = $this->formatNumber($creditAmount);
            $line['balance'] = $this->formatNumber($runningBalance);
            
            
            $statementLines->push($line);
        }

        // Create the final response
        $response = [
            'account' => [
                'account_number' => $account->account_number,
                'name' => $account->account_name,
                'id' => $account->id
            ],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statementLines' => $statementLines
        ];
        
        return $response;
    }

    private function calculateOpeningBalance($account, $startDate)
    {
        // Start with 0 balance
        $openingBalance = 0;
        
        // Add account's opening balance if set and the opening balance date is before the start date
        if (!is_null($account->opening_balance) && !is_null($account->opening_balance_date)) {
            $openingBalanceDate = Carbon::parse($account->opening_balance_date);
            $statementStartDate = Carbon::parse($startDate);
            
            if ($openingBalanceDate->lt($statementStartDate)) {
                // Only include opening balance if it's before the start date
                $openingBalance = floatval($account->opening_balance);
            }
        }
        
        $project = auth()->user()->project;
        
        // Get incomings before start date
        $incomingsQuery = Incoming::where('account_id', $account->id)
            ->where('project', $project)
            ->where('receive_date', '<', $startDate)
            ->whereNotNull('receive_date'); // Ensure receive_date is not null
            
        // Get outgoings before start date
        $outgoingsQuery = Outgoing::where('account_id', $account->id)
            ->where('project', $project)
            ->where('outgoing_date', '<', $startDate)
            ->whereNotNull('outgoing_date'); // Ensure outgoing_date is not null

        // If we have account opening balance date, only include transactions after that date
        if (!is_null($account->opening_balance_date)) {
            $incomingsQuery->where('receive_date', '>=', $account->opening_balance_date);
            $outgoingsQuery->where('outgoing_date', '>=', $account->opening_balance_date);
        }

        // Sum incomings
        $totalIncomings = $incomingsQuery->sum('amount') ?? 0;
        
        // Sum outgoings
        $totalOutgoings = $outgoingsQuery->sum('amount') ?? 0;
        
        // Calculate transaction balance based on account type
        // For cash accounts, normal balance is debit (incomings increase, outgoings decrease)
        if ($account->type === 'cash') {
            $transactionBalance = $totalIncomings - $totalOutgoings;
        } else {
            // For other accounts like bank, we may need to check other criteria
            // For now, assume the same logic
            $transactionBalance = $totalIncomings - $totalOutgoings;
        }
        
        $openingBalance += $transactionBalance;
        
        return $openingBalance;
    }

    /**
     * Get combined incomings and outgoings data based on date range and project
     * 
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param string|null $project Project code (uses authenticated user's project if null)
     * @return array
     */
    public function getCombinedCashTransactions($startDate, $endDate, $project = null)
    {
        // Use authenticated user's project if none provided
        if (!$project) {
            $project = auth()->user()->project;
        }
        
        // Get incomings data - only include those with non-null receive_date
        $incomings = Incoming::whereBetween('receive_date', [$startDate, $endDate])
            ->where('project', $project)
            ->whereNotNull('receive_date') // Ensure receive_date is not null
            ->select(
                'id',
                'nomor as document_number',
                'receive_date as transaction_date',
                'amount',
                'description',
                'project',
                'sap_journal_no',
                DB::raw("'incoming' as transaction_type")
            )
            ->get()
            ->toArray();
            
        // Get outgoings data - only include those with non-null outgoing_date
        $outgoings = Outgoing::with('payreq')
            ->whereBetween('outgoing_date', [$startDate, $endDate])
            ->where('project', $project)
            ->whereNotNull('outgoing_date') // Ensure outgoing_date is not null
            ->get()
            ->map(function($outgoing) {
                // Safely access payreq.nomor with fallback
                $documentNumber = 'N/A';
                if ($outgoing->payreq) {
                    $documentNumber = $outgoing->payreq->nomor ?? (string)$outgoing->payreq_id;
                } else if ($outgoing->payreq_id) {
                    $documentNumber = (string)$outgoing->payreq_id;
                }
                
                return [
                    'id' => $outgoing->id,
                    'document_number' => $documentNumber,
                    'transaction_date' => $outgoing->outgoing_date,
                    'amount' => $outgoing->amount,
                    'description' => $outgoing->full_description,
                    'project' => $outgoing->project,
                    'sap_journal_no' => $outgoing->sap_journal_no,
                    'transaction_type' => 'outgoing'
                ];
            })
            ->toArray();
            
        // Combine and sort by transaction_date
        $combinedTransactions = array_merge($incomings, $outgoings);
        
        usort($combinedTransactions, function($a, $b) {
            return strtotime($a['transaction_date']) - strtotime($b['transaction_date']);
        });
        
        return $combinedTransactions;
    }
}
