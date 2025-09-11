<?php

namespace App\Imports;

use App\Models\BilyetTemp;
use App\Models\Giro;
use App\Services\BilyetValidationService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Support\Facades\Log;

class BilyetTempImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithEvents
{
    use SkipsFailures;

    private $validationService;
    private $rowCount = 0;
    private $importStartTime;

    public function __construct()
    {
        $this->validationService = app(BilyetValidationService::class);
    }

    public function model(array $row)
    {
        $this->rowCount++;
        $rowNumber = $this->rowCount + 1; // +1 for header row

        // Auto-convert acc_no and nomor to strings before validation
        $row['acc_no'] = $this->convertToString($row['acc_no']);
        $row['nomor'] = $this->convertToString($row['nomor']);

        // Enhanced validation using validation service
        $validation = $this->validationService->validateRow($row, $rowNumber);

        // Log warnings but don't block import for warnings
        if (!empty($validation['warnings'])) {
            Log::warning('Import warnings', [
                'row' => $rowNumber,
                'warnings' => $validation['warnings']
            ]);
        }

        // Only throw exceptions for critical errors, not warnings
        if (!$validation['valid']) {
            $errorMessage = "Row {$rowNumber}: " . implode('; ', $validation['errors']);
            Log::error('Import validation failed', [
                'row' => $rowNumber,
                'data' => $row,
                'errors' => $validation['errors']
            ]);
            throw new \Exception($errorMessage);
        }

        // Handle acc_no conversion from scientific notation (additional processing)
        $accNo = $row['acc_no'];
        if (is_numeric($accNo) && strpos($accNo, 'E') !== false) {
            $accNo = number_format($accNo, 0, '', '');
        }

        return new BilyetTemp([
            'giro_id' => $this->cek_account($accNo),
            'acc_no' => $accNo,
            'prefix' => $row['prefix'],
            'nomor' => $row['nomor'], // Already converted to string
            'type' => $row['type'],
            'bilyet_date' => $this->convert_date($row['bilyet_date']),
            'cair_date' => $this->convert_date($row['cair_date']),
            'amount' => $row['amount'],
            'remarks' => $row['remarks'],
            'loan_id' => $row['loan_id'],
            'created_by' => auth()->id(),
            'project' => $this->giro_project($accNo),
        ]);
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $this->importStartTime = microtime(true);
                Log::info('Bilyet import started', [
                    'user_id' => auth()->id(),
                    'timestamp' => now()
                ]);
            },
            AfterImport::class => function (AfterImport $event) {
                $duration = microtime(true) - $this->importStartTime;
                Log::info('Bilyet import completed', [
                    'user_id' => auth()->id(),
                    'rows_processed' => $this->rowCount,
                    'duration_seconds' => round($duration, 2),
                    'timestamp' => now()
                ]);
            }
        ];
    }

    public function rules(): array
    {
        return [
            'nomor' => 'required|max:30', // Removed 'string' requirement since we auto-convert
            'acc_no' => 'nullable|max:50', // Removed 'string' requirement since we auto-convert
            'prefix' => 'nullable|string|max:10',
            'type' => 'nullable|string|max:20',
            'amount' => 'nullable|numeric',
        ];
    }

    /**
     * Convert numeric values to strings for acc_no and nomor fields
     */
    private function convertToString($value)
    {
        // Handle null/empty values
        if (empty($value) && $value !== 0) {
            return $value;
        }

        // Convert numeric values to string
        if (is_numeric($value)) {
            // Handle scientific notation first
            if (is_float($value) && strpos((string)$value, 'E') !== false) {
                // Convert scientific notation to full number, then to string
                return number_format($value, 0, '', '');
            }

            // Handle regular numeric values
            if (is_float($value)) {
                // Remove decimal point for whole numbers (e.g., 708431.0 -> 708431)
                return (string) intval($value);
            }

            // Handle integers
            return (string) $value;
        }

        // Already a string or other type - return as is
        return $value;
    }

    public function convert_date($date)
    {
        if ($date) {
            $year = substr($date, 6, 4);
            $month = substr($date, 3, 2);
            $day = substr($date, 0, 2);
            $new_date = $year . '-' . $month . '-' . $day;
            return $new_date;
        } else {
            return null;
        }
    }

    public function cek_account($acc_no)
    {
        // Handle scientific notation conversion (additional safety check)
        if (is_numeric($acc_no) && strpos($acc_no, 'E') !== false) {
            $acc_no = number_format($acc_no, 0, '', '');
        }

        // Ensure acc_no is string for database lookup
        $acc_no = (string) $acc_no;

        $cek = Giro::where('acc_no', $acc_no)->first();
        if ($cek) {
            Log::info('Account found successfully', [
                'acc_no' => $acc_no,
                'giro_id' => $cek->id
            ]);
            return $cek->id;
        } else {
            Log::error('Account not found in database', [
                'acc_no' => $acc_no,
                'acc_no_type' => gettype($acc_no),
                'acc_no_length' => strlen($acc_no)
            ]);
            return null;
        }
    }

    public function giro_project($acc_no)
    {
        // Handle scientific notation conversion (additional safety check)
        if (is_numeric($acc_no) && strpos($acc_no, 'E') !== false) {
            $acc_no = number_format($acc_no, 0, '', '');
        }

        // Ensure acc_no is string for database lookup
        $acc_no = (string) $acc_no;

        $giro = Giro::where('acc_no', $acc_no)->first();
        return $giro ? $giro->project : null;
    }
}
