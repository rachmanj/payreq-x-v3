<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('currencies.index');
    }

    /**
     * Get data for DataTables.
     */
    public function data()
    {
        $currencies = Currency::with(['creator', 'updater'])
            ->when(request('search'), function ($query) {
                $search = request('search');
                return $query->where('currency_code', 'like', "%{$search}%")
                    ->orWhere('currency_name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            })
            ->when(request('is_active') !== null, function ($query) {
                return $query->where('is_active', request('is_active'));
            })
            ->orderBy('currency_code')
            ->get();

        return datatables()->of($currencies)
            ->addIndexColumn()
            ->addColumn('status', function ($row) {
                return $row->is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->addColumn('creator_name', function ($row) {
                return $row->creator ? $row->creator->name : '-';
            })
            ->addColumn('updater_name', function ($row) {
                return $row->updater ? $row->updater->name : '-';
            })
            ->addColumn('action', function ($row) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('currencies.show', $row->id) . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>';
                $actions .= '<a href="' . route('currencies.edit', $row->id) . '" class="btn btn-sm btn-warning ml-2"><i class="fas fa-edit"></i></a>';
                $actions .= '<button type="button" class="btn btn-sm btn-danger ml-2" onclick="deleteItem(' . $row->id . ')"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('currencies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'currency_code' => 'required|string|max:3|unique:currencies,currency_code',
            'currency_name' => 'required|string|max:100',
            'symbol' => 'nullable|string|max:10',
            'is_active' => 'boolean'
        ]);

        Currency::create([
            'currency_code' => strtoupper($request->currency_code),
            'currency_name' => $request->currency_name,
            'symbol' => $request->symbol,
            'is_active' => $request->has('is_active'),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('currencies.index')
            ->with('success', 'Currency created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Currency $currency)
    {
        $currency->load(['creator', 'updater', 'exchangeRatesFrom', 'exchangeRatesTo']);
        return view('currencies.show', compact('currency'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Currency $currency)
    {
        return view('currencies.edit', compact('currency'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Currency $currency)
    {
        $request->validate([
            'currency_code' => [
                'required',
                'string',
                'max:3',
                Rule::unique('currencies', 'currency_code')->ignore($currency->id)
            ],
            'currency_name' => 'required|string|max:100',
            'symbol' => 'nullable|string|max:10',
            'is_active' => 'boolean'
        ]);

        $currency->update([
            'currency_code' => strtoupper($request->currency_code),
            'currency_name' => $request->currency_name,
            'symbol' => $request->symbol,
            'is_active' => $request->has('is_active'),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('currencies.index')
            ->with('success', 'Currency updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Currency $currency)
    {
        // Check if currency is being used in exchange rates
        $hasExchangeRates = $currency->exchangeRatesFrom()->exists() || $currency->exchangeRatesTo()->exists();

        if ($hasExchangeRates) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete currency that is being used in exchange rates.'
            ], 422);
        }

        $currency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Currency deleted successfully.'
        ]);
    }
}
