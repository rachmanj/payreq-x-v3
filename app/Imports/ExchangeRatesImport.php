<?php

namespace App\Imports;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;

class ExchangeRatesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithChunkReading, WithBatchInserts
{
    use Importable, SkipsErrors, SkipsFailures;

    private $imported = 0;
    private $skipped = 0;
    private $rowNumber = 0;
    private $currencies;

    public function __construct()
    {
        $this->currencies = Currency::where('is_active', true)
            ->select('currency_code', 'currency_name')
            ->get()
            ->keyBy('currency_code');
    }

    public function model(array $row)
    {
        $this->rowNumber++;

        // Skip if any required field is empty
        if (
            empty($row['currency_from']) || empty($row['currency_to']) ||
            empty($row['exchange_rate']) || empty($row['effective_date'])
        ) {
            $this->skipped++;
            return null;
        }

        try {
            // Normalize currency codes
            $currencyFrom = strtoupper(trim($row['currency_from']));
            $currencyTo = strtoupper(trim($row['currency_to']));

            // Parse and validate date
            if (is_numeric($row['effective_date'])) {
                // Excel date format
                $effectiveDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['effective_date'])->format('Y-m-d');
            } else {
                $effectiveDate = Carbon::parse($row['effective_date'])->format('Y-m-d');
            }

            // Check if currencies exist in our database
            if (!$this->currencies->has($currencyFrom)) {
                $this->onFailure(new Failure(
                    $this->rowNumber + 1, // Add 1 because header row
                    'currency_from',
                    ["Currency '{$currencyFrom}' does not exist or is inactive in our system."],
                    $row
                ));
                $this->skipped++;
                return null;
            }

            if (!$this->currencies->has($currencyTo)) {
                $this->onFailure(new Failure(
                    $this->rowNumber + 1,
                    'currency_to',
                    ["Currency '{$currencyTo}' does not exist or is inactive in our system."],
                    $row
                ));
                $this->skipped++;
                return null;
            }

            // Check if currencies are the same
            if ($currencyFrom === $currencyTo) {
                $this->onFailure(new Failure(
                    $this->rowNumber + 1,
                    'currency_to',
                    ["Currency From and Currency To cannot be the same."],
                    $row
                ));
                $this->skipped++;
                return null;
            }

            // Check for existing record
            $existing = ExchangeRate::byCurrencyPair($currencyFrom, $currencyTo)
                ->byDate($effectiveDate)
                ->first();

            if ($existing) {
                $this->onFailure(new Failure(
                    $this->rowNumber + 1,
                    'effective_date',
                    ["Exchange rate for {$currencyFrom}/{$currencyTo} on {$effectiveDate} already exists."],
                    $row
                ));
                $this->skipped++;
                return null;
            }

            // Validate exchange rate
            $exchangeRate = (float) $row['exchange_rate'];
            if ($exchangeRate <= 0) {
                $this->onFailure(new Failure(
                    $this->rowNumber + 1,
                    'exchange_rate',
                    ["Exchange rate must be greater than 0."],
                    $row
                ));
                $this->skipped++;
                return null;
            }

            $this->imported++;

            return new ExchangeRate([
                'currency_from' => $currencyFrom,
                'currency_to' => $currencyTo,
                'exchange_rate' => $exchangeRate,
                'effective_date' => $effectiveDate,
                'created_by' => Auth::id(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->onFailure(new Failure(
                $this->rowNumber + 1,
                'database_error',
                ['Database error: ' . $e->getMessage()],
                $row
            ));
            $this->skipped++;
            return null;
        } catch (\Exception $e) {
            $this->onFailure(new Failure(
                $this->rowNumber + 1,
                'system_error',
                ['Error: ' . $e->getMessage()],
                $row
            ));
            $this->skipped++;
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'currency_from' => 'required|string|min:3|max:3',
            'currency_to' => 'required|string|min:3|max:3|different:currency_from',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'effective_date' => 'required',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'currency_from.required' => 'Currency From is required',
            'currency_from.min' => 'Currency From must be exactly 3 characters',
            'currency_from.max' => 'Currency From must be exactly 3 characters',
            'currency_to.required' => 'Currency To is required',
            'currency_to.min' => 'Currency To must be exactly 3 characters',
            'currency_to.max' => 'Currency To must be exactly 3 characters',
            'currency_to.different' => 'Currency To must be different from Currency From',
            'exchange_rate.required' => 'Exchange Rate is required',
            'exchange_rate.numeric' => 'Exchange Rate must be a number',
            'exchange_rate.min' => 'Exchange Rate must be greater than 0',
            'effective_date.required' => 'Effective Date is required',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function getRowNumber(): int
    {
        return $this->rowNumber;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 500;
    }
}
