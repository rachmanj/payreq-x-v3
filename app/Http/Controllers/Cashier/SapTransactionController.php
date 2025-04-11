<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Account;

class SapTransactionController extends Controller
{
    public function index()
    {
        $accounts = Account::where('project', auth()->user()->project)
            ->whereIn('type', ['cash', 'bank'])
            ->select('account_number', 'account_name')
            ->orderBy('account_number')
            ->get();

        return view('cashier.sap-transactions.index', compact('accounts'));
    }

    public function data(Request $request)
    {
        $response = Http::withHeaders([
            'X-API-KEY' => config('services.gl.api_key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->post(config('services.gl.url') . '/api/v1/statements', [
            'account_number' => $request->account_number,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);

        if ($response->successful()) {
            $data = $response->json()['data'];
            $statementLines = $data['statementLines'];
            
            return response()->json([
                'draw' => (int) $request->input('draw'),
                'recordsTotal' => count($statementLines),
                'recordsFiltered' => count($statementLines),
                'data' => $statementLines,
                'account' => $data['account']
            ]);
        }

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Failed to fetch data'
        ], 500);
    }
}
