# Referensi untuk import data

## File Import, contoh PersonalImport.php

<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Religion;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Validators\Failure;

class PersonalImport implements ToModel, WithHeadingRow, WithMultipleSheets, WithEvents, SkipsOnFailure, SkipsOnError, WithValidation, WithChunkReading, WithBatchInserts
{
    use Importable, SkipsErrors, SkipsFailures;

    private $sheetName;
    private $religions;
    private $rowNumber = 0;
    private $parent = null;

    public function __construct()
    {
        $this->religions = Religion::select('id', 'religion_name')->get();
    }

    /**
     * Set the parent import
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    public function sheets(): array
    {
        return [
            'personal' => $this,
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->sheetName = $event->getSheet()->getTitle();
            }
        ];
    }

    public function getSheetName()
    {
        return $this->sheetName;
    }

    public function rules(): array
    {
        return [
            'identity_card_no' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'place_of_birth' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required'],
            'blood_type' => ['nullable'],
            'religion' => ['required', 'exists:religions,religion_name'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', 'in:male,female'],
            'marital_status' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'village' => ['nullable', 'string', 'max:100'],
            'ward' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * Extends the validator to check if existing identity card belongs to same person
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $rows = $validator->getData();

            foreach ($rows as $rowIndex => $row) {
                if (empty($row['identity_card_no'])) {
                    continue;
                }

                // Store this row for other imports to access
                if ($this->parent) {
                    $this->parent->addPendingPersonalRow($row);
                }

                $existingEmployee = Employee::where('identity_card', trim($row['identity_card_no']))->first();

                if ($existingEmployee) {
                    $nameThreshold = 90; // percentage similarity threshold

                    // Calculate similarity between names
                    similar_text(
                        strtolower($existingEmployee->fullname),
                        strtolower($row['full_name'] ?? ''),
                        $percent
                    );

                    // If name similarity is below threshold, it's likely a different person
                    if ($percent < $nameThreshold) {
                        $validator->errors()->add(
                            $rowIndex . '.identity_card_no',
                            "Identity Card No '{$row['identity_card_no']}' already exists for employee '{$existingEmployee->fullname}' who appears to be a different person."
                        );
                    }
                    // If similarity is high, it's likely the same person - will update existing record
                }
            }
        });
    }

    public function customValidationMessages()
    {
        return [
            'identity_card_no.required' => 'Identity Card No is required',
            'identity_card_no.max' => 'Identity Card No cannot exceed 50 characters',
            'full_name.required' => 'Full Name is required',
            'full_name.max' => 'Full Name cannot exceed 255 characters',
            'place_of_birth.required' => 'Place of Birth is required',
            'date_of_birth.required' => 'Date of Birth is required',
            'religion.required' => 'Religion is required',
            'religion.exists' => 'Selected Religion does not exist in our database',
            'gender.required' => 'Gender is required',
            'gender.in' => 'Gender must be either male or female, case sensitive',
            'marital_status.required' => 'Marital Status is required',
            'email.email' => 'Email must be a valid email address',
        ];
    }

    public function getRowNumber()
    {
        return $this->rowNumber;
    }

    public function model(array $row)
    {
        $this->rowNumber++;

        // Skip empty rows
        if (empty($row['identity_card_no'])) {
            return null;
        }

        $religion = null;
        if (isset($row['religion']) && $row['religion']) {
            $religion = $this->religions->where('religion_name', $row['religion'])->first();
        }

        try {
            // Prepare data for employee record
            $employeeData = [
                'fullname' => $row['full_name'] ?? null,
                'emp_pob' => $row['place_of_birth'] ?? null,
                'gender' => $row['gender'] ?? NULL,
                'blood_type' => $row['blood_type'] ?? NULL,
                'religion_id' => $religion ? $religion->id : NULL,
                'nationality' => $row['nationality'] ?? NULL,
                'marital' => $row['marital_status'] ?? NULL,
                'address' => $row['address'] ?? NULL,
                'village' => $row['village'] ?? NULL,
                'ward' => $row['ward'] ?? NULL,
                'district' => $row['district'] ?? NULL,
                'city' => $row['city'] ?? NULL,
                'phone' => $row['phone'] ?? NULL,
                'email' => $row['email'] ?? NULL,
                'user_id' => auth()->user()->id
            ];

            // Process date of birth
            if (!empty($row['date_of_birth'])) {
                if (is_numeric($row['date_of_birth'])) {
                    $employeeData['emp_dob'] = Date::excelToDateTimeObject($row['date_of_birth']);
                } else {
                    $employeeData['emp_dob'] = \Carbon\Carbon::parse($row['date_of_birth']);
                }
            }

            // Use updateOrCreate to handle both insert and update scenarios
            $employee = Employee::updateOrCreate(
                ['identity_card' => trim($row['identity_card_no'])], // The attribute(s) to search by
                $employeeData // The attributes to update or create with
            );

            return $employee;
        } catch (\Illuminate\Database\QueryException $e) {
            $attribute = 'identity_card_no';
            $errorMessage = "Duplicate Identity Card No '{$row['identity_card_no']}' found. Please check if this employee already exists in the system.";

            if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                $attribute = 'system_error';
                $errorMessage = 'Database error: ' . $e->getMessage();
            }

            $this->onFailure(new Failure(
                $this->rowNumber,
                $attribute,
                [$errorMessage],
                $row
            ));

            return null;
        } catch (\Exception $e) {
            $this->onFailure(new Failure(
                $this->rowNumber,
                'system_error',
                ['Error: ' . $e->getMessage()],
                $row
            ));

            return null;
        }
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

## File Controller, contoh EmployeeController.php
public function import(Request $request)
    {
        $this->validate($request, [
            'employee' => 'required|mimes:xls,xlsx',
        ], [
            'employee.required' => 'Please select a file to import',
            'employee.mimes' => 'The file must be a file of type: xls, xlsx',
        ]);

        try {
            $import = new PersonalImport();
            Excel::import($import, $request->file('employee'));

            // Cek apakah ada validation failures manual
            $failures = collect();

            if (method_exists($import, 'sheets')) {
                foreach ($import->sheets() as $sheetName => $sheetImport) {
                    if (method_exists($sheetImport, 'failures')) {
                        foreach ($sheetImport->failures() as $failure) {
                            // Jika failure adalah objek Failure dari Laravel Excel
                            if (method_exists($failure, 'row')) {
                                $failures->push([
                                    'sheet'     => method_exists($sheetImport, 'getSheetName') ? $sheetImport->getSheetName() : $sheetName,
                                    'row'       => $failure->row(),
                                    'attribute' => $failure->attribute(),
                                    'value'     => $failure->values()[$failure->attribute()] ?? null,
                                    'errors'    => implode(', ', $failure->errors()),
                                ]);
                            } else {
                                // Jika failure adalah array dari manual failures
                                $failures->push($failure);
                            }
                        }
                    }
                }
            }

            if ($failures->isNotEmpty()) {
                return back()->with('failures', $failures);
            }

            return redirect('employees')->with('toast_success', 'Data imported successfully');
        } catch (ValidationException $e) {
            $failures = collect();
            $sheetName = 'Unknown';
            // Coba dapatkan nama sheet dari import_employee jika tersedia
            if (method_exists($import, 'getSheetName')) {
                $sheetName = $import->getSheetName();
            }

            foreach ($e->failures() as $failure) {
                $failures->push([
                    'sheet'     => $sheetName,
                    'row'       => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'value'     => $failure->values()[$failure->attribute()] ?? null,
                    'errors'    => implode(', ', $failure->errors()),
                ]);
            }

            return back()->with('failures', $failures);
        } catch (\Throwable $e) {
            $failures = collect([
                [
                    'sheet' => 'Unknown',
                    'row' => '-',
                    'attribute' => 'System Error',
                    'value' => null,
                    'errors' => 'An error occurred during import: ' . $e->getMessage()
                ]
            ]);
            return back()->with('failures', $failures);
        }
    }

## File View, contoh index.blade.php
@if (session()->has('failures'))
    <div class="card card-danger">
        <div class="card-header">
            <h3 class="card-title"><i class="icon fas fa-exclamation-triangle"></i> Import
                Validation Errors</h3>

            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
            <!-- /.card-tools -->
        </div>
        <div class="card-body" style="display: block;">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th style="width: 5%">Sheet</th>
                            <th class="text-center" style="width: 5%">Row</th>
                            <th style="width: 20%">Column</th>
                            <th style="width: 20%">Value</th>
                            <th>Error Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (session()->get('failures') as $failure)
                            <tr>
                                <td>{{ $failure['sheet'] }}</td>
                                <td class="text-center">{{ $failure['row'] }}</td>
                                <td>
                                    <strong>{{ ucwords(str_replace('_', ' ', $failure['attribute'])) }}</strong>
                                </td>
                                <td>
                                    @if (isset($failure['value']))
                                        {{ $failure['value'] }}
                                    @endif
                                </td>
                                <td>
                                    {{ $failure['errors'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-1">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Please correct these errors in your Excel file and try importing again.
                </small>
            </div>
        </div>
    </div>
@endif
