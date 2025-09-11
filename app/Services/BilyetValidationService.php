<?php

namespace App\Services;

use App\Models\BilyetTemp;
use App\Models\Bilyet;
use App\Models\Giro;
use Illuminate\Support\Facades\Log;

class BilyetValidationService
{
    /**
     * Validate a single row of import data
     */
    public function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];

        // Required field validation
        if (empty($row['nomor'])) {
            $errors[] = "Row {$rowNumber}: Nomor field is required";
        }

        if (empty($row['prefix'])) {
            $errors[] = "Row {$rowNumber}: Prefix field is required";
        }

        // Account number validation (temporarily disabled for debugging)
        if (!empty($row['acc_no'])) {
            $accountValidation = $this->validateAccountNumber($row['acc_no'], $rowNumber);
            // Only add warnings, not errors, to allow import to continue
            $warnings = array_merge($warnings, $accountValidation['warnings']);
            // Log errors but don't block import
            if (!empty($accountValidation['errors'])) {
                Log::warning('Account validation errors (non-blocking)', [
                    'row' => $rowNumber,
                    'errors' => $accountValidation['errors']
                ]);
            }
        }

        // Amount validation
        if (!empty($row['amount'])) {
            $amountValidation = $this->validateAmount($row['amount'], $rowNumber);
            $errors = array_merge($errors, $amountValidation['errors']);
        }

        // Date validation
        if (!empty($row['bilyet_date'])) {
            $dateValidation = $this->validateDate($row['bilyet_date'], 'bilyet_date', $rowNumber);
            $errors = array_merge($errors, $dateValidation['errors']);
        }

        if (!empty($row['cair_date'])) {
            $dateValidation = $this->validateDate($row['cair_date'], 'cair_date', $rowNumber);
            $errors = array_merge($errors, $dateValidation['errors']);
        }

        // Duplication check
        $duplicationCheck = $this->checkDuplication($row, $rowNumber);
        $warnings = array_merge($warnings, $duplicationCheck['warnings']);

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'valid' => empty($errors)
        ];
    }

    /**
     * Validate account number
     */
    private function validateAccountNumber($accNo, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];

        // Handle scientific notation (additional safety check)
        if (is_numeric($accNo) && strpos($accNo, 'E') !== false) {
            $accNo = number_format($accNo, 0, '', '');
        }

        // Ensure acc_no is string for database lookup
        $accNo = (string) $accNo;

        // Check if account exists
        $giro = Giro::where('acc_no', $accNo)->first();

        if (!$giro) {
            $errors[] = "Row {$rowNumber}: Account number '{$accNo}' not found in system";
        } else {
            // Check if account is active
            if (isset($giro->status) && $giro->status !== 'active') {
                $warnings[] = "Row {$rowNumber}: Account '{$accNo}' is not active";
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate amount
     */
    private function validateAmount($amount, int $rowNumber): array
    {
        $errors = [];

        if (!is_numeric($amount)) {
            $errors[] = "Row {$rowNumber}: Amount must be a valid number";
        } elseif ($amount < 0) {
            $errors[] = "Row {$rowNumber}: Amount cannot be negative";
        } elseif ($amount > 999999999999) {
            $errors[] = "Row {$rowNumber}: Amount exceeds maximum limit";
        }

        return ['errors' => $errors];
    }

    /**
     * Validate date format
     */
    private function validateDate($date, string $fieldName, int $rowNumber): array
    {
        $errors = [];

        // Check if date is in valid format (DD-MM-YYYY or YYYY-MM-DD)
        $formats = ['d-m-Y', 'Y-m-d', 'd/m/Y', 'Y/m/d'];
        $validDate = false;

        foreach ($formats as $format) {
            $parsedDate = \DateTime::createFromFormat($format, $date);
            if ($parsedDate && $parsedDate->format($format) === $date) {
                $validDate = true;
                break;
            }
        }

        if (!$validDate) {
            $errors[] = "Row {$rowNumber}: Invalid {$fieldName} format. Use DD-MM-YYYY or YYYY-MM-DD";
        }

        return ['errors' => $errors];
    }

    /**
     * Check for duplications
     */
    private function checkDuplication(array $row, int $rowNumber): array
    {
        $warnings = [];

        if (!empty($row['prefix']) && !empty($row['nomor'])) {
            $bilyetNumber = $row['prefix'] . $row['nomor'];

            // Check if exists in temp table
            $tempExists = BilyetTemp::where('prefix', $row['prefix'])
                ->where('nomor', $row['nomor'])
                ->where('created_by', auth()->id())
                ->exists();

            if ($tempExists) {
                $warnings[] = "Row {$rowNumber}: Duplicate bilyet number '{$bilyetNumber}' in import data";
            }

            // Check if exists in main table
            $mainExists = Bilyet::where('prefix', $row['prefix'])
                ->where('nomor', $row['nomor'])
                ->exists();

            if ($mainExists) {
                $warnings[] = "Row {$rowNumber}: Bilyet number '{$bilyetNumber}' already exists in system";
            }
        }

        return ['warnings' => $warnings];
    }

    /**
     * Validate entire import batch
     */
    public function validateBatch(array $data): array
    {
        $allErrors = [];
        $allWarnings = [];
        $validRows = 0;
        $totalRows = count($data);

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // Excel row number (accounting for header)
            $validation = $this->validateRow($row, $rowNumber);

            $allErrors = array_merge($allErrors, $validation['errors']);
            $allWarnings = array_merge($allWarnings, $validation['warnings']);

            if ($validation['valid']) {
                $validRows++;
            }
        }

        return [
            'errors' => $allErrors,
            'warnings' => $allWarnings,
            'valid_rows' => $validRows,
            'total_rows' => $totalRows,
            'error_rate' => $totalRows > 0 ? (($totalRows - $validRows) / $totalRows) * 100 : 0
        ];
    }

    /**
     * Generate detailed error report
     */
    public function generateErrorReport(array $errors, array $warnings): array
    {
        $report = [
            'summary' => [
                'total_errors' => count($errors),
                'total_warnings' => count($warnings),
                'error_categories' => $this->categorizeErrors($errors),
                'warning_categories' => $this->categorizeWarnings($warnings)
            ],
            'errors' => $errors,
            'warnings' => $warnings,
            'recommendations' => $this->generateRecommendations($errors, $warnings)
        ];

        return $report;
    }

    /**
     * Categorize errors by type
     */
    private function categorizeErrors(array $errors): array
    {
        $categories = [
            'required_fields' => 0,
            'account_issues' => 0,
            'format_issues' => 0,
            'duplication_issues' => 0,
            'business_rules' => 0
        ];

        foreach ($errors as $error) {
            if (strpos($error, 'required') !== false) {
                $categories['required_fields']++;
            } elseif (strpos($error, 'Account') !== false) {
                $categories['account_issues']++;
            } elseif (strpos($error, 'format') !== false) {
                $categories['format_issues']++;
            } elseif (strpos($error, 'Duplicate') !== false) {
                $categories['duplication_issues']++;
            } else {
                $categories['business_rules']++;
            }
        }

        return $categories;
    }

    /**
     * Categorize warnings by type
     */
    private function categorizeWarnings(array $warnings): array
    {
        $categories = [
            'account_warnings' => 0,
            'duplication_warnings' => 0,
            'data_quality' => 0
        ];

        foreach ($warnings as $warning) {
            if (strpos($warning, 'Account') !== false) {
                $categories['account_warnings']++;
            } elseif (strpos($warning, 'Duplicate') !== false) {
                $categories['duplication_warnings']++;
            } else {
                $categories['data_quality']++;
            }
        }

        return $categories;
    }

    /**
     * Generate actionable recommendations
     */
    private function generateRecommendations(array $errors, array $warnings): array
    {
        $recommendations = [];

        $errorCategories = $this->categorizeErrors($errors);
        $warningCategories = $this->categorizeWarnings($warnings);

        if ($errorCategories['account_issues'] > 0) {
            $recommendations[] = "Review account numbers. Consider downloading the latest account list from the system.";
        }

        if ($errorCategories['format_issues'] > 0) {
            $recommendations[] = "Check date formats. Use DD-MM-YYYY format for all dates.";
        }

        if ($errorCategories['duplication_issues'] > 0) {
            $recommendations[] = "Remove duplicate bilyet numbers or choose to update existing records.";
        }

        if ($warningCategories['account_warnings'] > 0) {
            $recommendations[] = "Verify account status. Some accounts may be inactive.";
        }

        return $recommendations;
    }
}
