<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExchangeRatesExport;
use App\Exports\ExchangeRateTemplateExport;
use App\Imports\ExchangeRatesImport;

class ExchangeRateController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Basic access permission for all methods
        $this->middleware('permission:akses_exchange_rates');

        // Granular permissions for specific actions
        $this->middleware('permission:create_exchange_rates')->only(['create', 'store']);
        $this->middleware('permission:edit_exchange_rates')->only(['edit', 'update', 'bulkUpdate']);
        $this->middleware('permission:delete_exchange_rates')->only(['destroy', 'bulkDelete']);
        $this->middleware('permission:import_exchange_rates')->only(['import', 'downloadTemplate']);
        $this->middleware('permission:export_exchange_rates')->only(['export']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ExchangeRate::with(['currencyFromRelation', 'currencyToRelation', 'creator']);

        // Apply filters
        if ($request->filled('currency_from')) {
            $query->where('currency_from', $request->currency_from);
        }

        if ($request->filled('currency_to')) {
            $query->where('currency_to', $request->currency_to);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        $exchangeRates = $query->orderBy('effective_date', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        $currencies = Currency::active()->orderBy('currency_code')->get();

        return view('exchange-rates.index', compact('exchangeRates', 'currencies'));
    }

    /**
     * AJAX method to get data for DataTables
     */
    public function data(Request $request)
    {
        $query = ExchangeRate::with(['currencyFromRelation', 'currencyToRelation', 'creator']);

        // Apply filters from request
        if ($request->filled('currency_from')) {
            $query->where('currency_from', $request->currency_from);
        }

        if ($request->filled('currency_to')) {
            $query->where('currency_to', $request->currency_to);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        $exchangeRates = $query->orderBy('effective_date', 'desc')->get();

        return response()->json([
            'data' => $exchangeRates->map(function ($rate) {
                return [
                    'id' => $rate->id,
                    'currency_from' => $rate->currency_from,
                    'currency_to' => $rate->currency_to,
                    'currency_pair' => $rate->currency_pair,
                    'exchange_rate' => $rate->formatted_rate,
                    'effective_date' => $rate->effective_date->format('Y-m-d'),
                    'created_by' => $rate->creator->name ?? 'N/A',
                    'created_at' => $rate->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $rate->updated_at->format('Y-m-d H:i:s'),
                ];
            })
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currencies = Currency::active()->orderBy('currency_code')->get();
        return view('exchange-rates.create', compact('currencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'currency_from' => 'required|string|size:3|exists:currencies,currency_code',
            'currency_to' => 'required|string|size:3|exists:currencies,currency_code|different:currency_from',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        try {
            DB::beginTransaction();

            $dateFrom = Carbon::parse($validated['date_from']);
            $dateTo = Carbon::parse($validated['date_to']);
            $createdCount = 0;

            // Generate records for each date in range
            while ($dateFrom->lte($dateTo)) {
                // Check if record already exists
                $existing = ExchangeRate::byCurrencyPair($validated['currency_from'], $validated['currency_to'])
                    ->byDate($dateFrom->format('Y-m-d'))
                    ->first();

                if (!$existing) {
                    ExchangeRate::create([
                        'currency_from' => $validated['currency_from'],
                        'currency_to' => $validated['currency_to'],
                        'exchange_rate' => $validated['exchange_rate'],
                        'effective_date' => $dateFrom->format('Y-m-d'),
                        'created_by' => Auth::id(),
                    ]);
                    $createdCount++;
                }

                $dateFrom->addDay();
            }

            DB::commit();

            return redirect()->route('accounting.exchange-rates.index')
                ->with('success', "Successfully created {$createdCount} exchange rate records.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create exchange rates: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $exchangeRate = ExchangeRate::with(['currencyFromRelation', 'currencyToRelation', 'creator', 'updater'])
            ->findOrFail($id);

        // Get related rates for the same currency pair
        $relatedRates = ExchangeRate::with(['creator'])
            ->byCurrencyPair($exchangeRate->currency_from, $exchangeRate->currency_to)
            ->orderBy('effective_date', 'desc')
            ->limit(10)
            ->get();

        return view('exchange-rates.show', compact('exchangeRate', 'relatedRates'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $exchangeRate = ExchangeRate::findOrFail($id);
        $currencies = Currency::active()->orderBy('currency_code')->get();

        return view('exchange-rates.edit', compact('exchangeRate', 'currencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $exchangeRate = ExchangeRate::findOrFail($id);

        $validated = $request->validate([
            'currency_from' => 'required|string|size:3|exists:currencies,currency_code',
            'currency_to' => 'required|string|size:3|exists:currencies,currency_code|different:currency_from',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'effective_date' => 'required|date',
        ]);

        try {
            $exchangeRate->update(array_merge($validated, [
                'updated_by' => Auth::id(),
            ]));

            return redirect()->route('accounting.exchange-rates.index')
                ->with('success', 'Exchange rate updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update exchange rate: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $exchangeRate = ExchangeRate::findOrFail($id);
            $exchangeRate->delete();

            return redirect()->route('accounting.exchange-rates.index')
                ->with('success', 'Exchange rate deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete exchange rate: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk update exchange rates
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:exchange_rates,id',
            'exchange_rate' => 'required|numeric|min:0.000001',
        ]);

        try {
            DB::beginTransaction();

            $updatedCount = ExchangeRate::whereIn('id', $validated['ids'])
                ->update([
                    'exchange_rate' => $validated['exchange_rate'],
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} exchange rate records.",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update exchange rates: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Bulk delete exchange rates
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:exchange_rates,id',
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = ExchangeRate::whereIn('id', $validated['ids'])->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} exchange rate records.",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exchange rates: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Import exchange rates from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ], [
            'excel_file.required' => 'Please select a file to import',
            'excel_file.mimes' => 'The file must be a file of type: xlsx, xls, csv',
            'excel_file.max' => 'The file size must not exceed 10MB',
        ]);

        try {
            DB::beginTransaction();

            $import = new ExchangeRatesImport();
            Excel::import($import, $request->file('excel_file'));

            // Get import statistics
            $importedCount = $import->getImportedCount();
            $skippedCount = $import->getSkippedCount();
            $failures = collect();

            // Collect validation failures
            foreach ($import->failures() as $failure) {
                $failures->push([
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'value' => $failure->values()[$failure->attribute()] ?? null,
                    'errors' => implode(', ', $failure->errors()),
                ]);
            }

            // Collect errors
            foreach ($import->errors() as $error) {
                $failures->push([
                    'row' => $error->row ?? 'Unknown',
                    'attribute' => 'System Error',
                    'value' => null,
                    'errors' => $error->getMessage() ?? 'Unknown error',
                ]);
            }

            DB::commit();

            // If there are failures, return with failures for display
            if ($failures->isNotEmpty()) {
                $message = "Import completed with errors. ";
                $message .= "Imported: {$importedCount}, ";
                $message .= "Skipped/Failed: " . ($skippedCount + $failures->count());

                return redirect()->route('accounting.exchange-rates.index')
                    ->with('failures', $failures)
                    ->with('warning', $message);
            }

            $message = "Import completed successfully. ";
            $message .= "Imported: {$importedCount}";
            if ($skippedCount > 0) {
                $message .= ", Skipped: {$skippedCount}";
            }

            return redirect()->route('accounting.exchange-rates.index')
                ->with('success', $message);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack();

            $failures = collect();
            foreach ($e->failures() as $failure) {
                $failures->push([
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'value' => $failure->values()[$failure->attribute()] ?? null,
                    'errors' => implode(', ', $failure->errors()),
                ]);
            }

            return redirect()->back()->with('failures', $failures);
        } catch (\Exception $e) {
            DB::rollBack();

            $failures = collect([
                [
                    'row' => '-',
                    'attribute' => 'System Error',
                    'value' => null,
                    'errors' => 'An error occurred during import: ' . $e->getMessage()
                ]
            ]);

            return redirect()->back()->with('failures', $failures);
        }
    }

    /**
     * Export exchange rates to Excel
     */
    public function export(Request $request)
    {
        try {
            // Build query with filters
            $query = ExchangeRate::with(['currencyFromRelation', 'currencyToRelation', 'creator']);

            // Apply filters from request
            if ($request->filled('currency_from')) {
                $query->where('currency_from', $request->currency_from);
            }

            if ($request->filled('currency_to')) {
                $query->where('currency_to', $request->currency_to);
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->byDateRange($request->date_from, $request->date_to);
            }

            if ($request->filled('created_by')) {
                $query->where('created_by', $request->created_by);
            }

            // Generate filename with timestamp
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "exchange_rates_{$timestamp}.xlsx";

            return Excel::download(new ExchangeRatesExport($query), $filename);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to export Excel file: ' . $e->getMessage()]);
        }
    }

    /**
     * Download Excel template
     */
    public function downloadTemplate()
    {
        try {
            $filename = 'exchange_rates_template.xlsx';
            return Excel::download(new ExchangeRateTemplateExport(), $filename);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to download template: ' . $e->getMessage()]);
        }
    }
}
