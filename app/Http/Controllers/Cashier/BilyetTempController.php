<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Imports\BilyetTempImport;
use App\Models\BilyetTemp;
use App\Models\Bilyet;
use App\Models\Loan;
use App\Services\BilyetValidationService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class BilyetTempController extends Controller
{
    public function index()
    {
        // count giro_id that is null
        $giro_id_null = BilyetTemp::where('giro_id', null)->where('created_by', auth()->user()->id)->count();

        // cek data exist atau ngga
        $exist = BilyetTemp::where('created_by', auth()->user()->id)->exists();

        // cek duplikasi dan duplikasi tabel tujuan
        $duplikasi = $this->cekDuplikasi();
        $duplikasi_bilyet = $this->cekDuplikasiTabelTujuan();

        // jika ada giro_id yang null atau duplikasi, disable button import
        $import_button = !$exist || $giro_id_null > 0 || !empty($duplikasi) || !empty($duplikasi_bilyet) ? 'disabled' : null;
        $empty_button = $exist ? null : 'disabled';

        return view('cashier.bilyets.index', compact('import_button', 'empty_button'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file_upload' => 'required|mimes:xls,xlsx',
        ]);

        try {
            // get the file
            $file = $request->file('file_upload');

            // rename the file name to prevent duplication
            $filename = 'bilyet_' . rand() . '_' . $file->getClientOriginalName();

            // move the file to the folder
            $file->move(public_path('file_upload'), $filename);

            // import data from the file to the database
            $import = new BilyetTempImport();
            Excel::import($import, public_path('file_upload/' . $filename));

            // delete the file after importing
            unlink(public_path('file_upload/' . $filename));

            // Check for failures and provide detailed feedback
            if ($import->failures()->count() > 0) {
                $failures = $import->failures();
                $errorReport = $this->generateDetailedErrorReport($failures);

                return redirect()->route('cashier.bilyets.index', ['page' => 'upload'])
                    ->with('warning', $errorReport['summary'])
                    ->with('error_details', $errorReport['details']);
            }

            // Get import statistics
            $importedCount = BilyetTemp::where('created_by', auth()->id())->count();

            // return to the index page with success message
            return redirect()->route('cashier.bilyets.index', ['page' => 'upload'])
                ->with('success', "Bilyet uploaded successfully! {$importedCount} records imported.");
        } catch (\Exception $e) {
            // Clean up file if it exists
            if (isset($filename) && file_exists(public_path('file_upload/' . $filename))) {
                unlink(public_path('file_upload/' . $filename));
            }

            return redirect()->route('cashier.bilyets.index', ['page' => 'upload'])
                ->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function truncate()
    {
        BilyetTemp::where('created_by', auth()->user()->id)->delete();

        return redirect()->route('cashier.bilyets.index', ['page' => 'upload'])->with('success', 'Bilyet truncated successfully.');
    }

    public function update(Request $request, $id)
    {
        // return $request->all();
        $bilyet = BilyetTemp::find($id);

        $bilyet->update([
            'prefix' => $request->prefix,
            'nomor' => $request->nomor,
            'bilyet_date' => $request->bilyet_date,
            'cair_date' => $request->cair_date,
            'amount' => $request->amount,
            'remarks' => $request->remarks,
        ]);

        return redirect()->route('cashier.bilyets.index', ['page' => 'upload'])->with('success', 'Bilyet updated successfully.');
    }

    public function destroy($id)
    {
        BilyetTemp::destroy($id);

        return redirect()->route('cashier.bilyets.index', ['page' => 'upload'])->with('success', 'Bilyet deleted successfully.');
    }

    public function data()
    {
        $bilyets = BilyetTemp::where('created_by', auth()->user()->id)->get();

        return datatables()->of($bilyets)
            ->editColumn('nomor', function ($bilyet) {
                return $bilyet->prefix . $bilyet->nomor;
            })
            ->editColumn('giro_id', function ($bilyet) {
                // if giro_id is not null, get the bank name and account number
                return $bilyet->giro_id == null ? '<span style="color: red;"><small><strong>NOT FOUND</strong></small></span>' : $bilyet->giro_id;
            })
            ->editColumn('acc_no', function ($bilyet) {
                // if giro_id is not null, get the bank name and account number
                return $bilyet->giro_id == null ? '<span style="color: red;"><strong><small>' . $bilyet->acc_no . ' Not Found</small></strong></span>' : $bilyet->acc_no;
            })
            ->addColumn('status_duplikasi', function ($bilyet) {
                $duplikasi = $this->cekDuplikasi();
                $duplikasi_warning = in_array($bilyet->prefix . $bilyet->nomor, $duplikasi) ? '<span style="color: red;"><small><strong>Duplicate</strong></small></span>' : null;

                $duplikasi_bilyet = $this->cekDuplikasiTabelTujuan();
                $duplikasi_bilyet_warning = in_array($bilyet->prefix . $bilyet->nomor, $duplikasi_bilyet) ? '<span style="color: red;"><small><strong>Exist</strong></small></span>' : null;

                return $duplikasi_warning && $duplikasi_bilyet_warning ? $duplikasi_warning . '<br>' . $duplikasi_bilyet_warning : ($duplikasi_warning ?: $duplikasi_bilyet_warning ?: '<span style="color: green;"><small><strong>OK</strong></small></span>');
            })
            ->addColumn('loan', function ($bilyet) {
                return $this->cekLoanId($bilyet->loan_id);
            })
            ->addIndexColumn()
            ->addColumn('action', 'cashier.bilyets.upload_action')
            ->rawColumns(['action', 'giro_id', 'acc_no', 'status_duplikasi', 'loan'])
            ->toJson();
    }

    public function cekDuplikasi()
    {
        $bilyet_temps = $this->buatArrayNomor();

        // cek duplikasi dari array bilyet_temp
        $duplikasi = array_unique(array_diff_assoc($bilyet_temps, array_unique($bilyet_temps)));

        return $duplikasi;
    }

    public function cekDuplikasiTabelTujuan()
    {
        $bilyet_temps = $this->buatArrayNomor();
        $duplikasi_bilyet = [];

        foreach ($bilyet_temps as $bilyet_temp) {
            $bilyet = Bilyet::where('prefix', substr($bilyet_temp, 0, 2))
                ->where('nomor', substr($bilyet_temp, 2))
                ->first();

            if ($bilyet) {
                $duplikasi_bilyet[] = $bilyet->prefix . $bilyet->nomor;
            }
        }

        return $duplikasi_bilyet;
    }

    /**
     * Generate detailed error report for import failures
     */
    private function generateDetailedErrorReport($failures)
    {
        $errorCount = $failures->count();
        $errors = [];
        $errorCategories = [
            'required_fields' => 0,
            'account_issues' => 0,
            'format_issues' => 0,
            'duplication_issues' => 0,
            'other' => 0
        ];

        foreach ($failures as $failure) {
            $rowNumber = $failure->row();
            $rowErrors = $failure->errors();

            foreach ($rowErrors as $error) {
                $errors[] = "Row {$rowNumber}: {$error}";

                // Categorize errors
                if (strpos($error, 'required') !== false || strpos($error, 'cannot be empty') !== false) {
                    $errorCategories['required_fields']++;
                } elseif (strpos($error, 'Account') !== false || strpos($error, 'not found') !== false) {
                    $errorCategories['account_issues']++;
                } elseif (strpos($error, 'format') !== false || strpos($error, 'Invalid') !== false) {
                    $errorCategories['format_issues']++;
                } elseif (strpos($error, 'Duplicate') !== false || strpos($error, 'already exists') !== false) {
                    $errorCategories['duplication_issues']++;
                } else {
                    $errorCategories['other']++;
                }
            }
        }

        // Generate summary message
        $summary = "Import completed with {$errorCount} errors. ";
        if ($errorCategories['required_fields'] > 0) {
            $summary .= "{$errorCategories['required_fields']} required field errors. ";
        }
        if ($errorCategories['account_issues'] > 0) {
            $summary .= "{$errorCategories['account_issues']} account issues. ";
        }
        if ($errorCategories['format_issues'] > 0) {
            $summary .= "{$errorCategories['format_issues']} format issues. ";
        }
        if ($errorCategories['duplication_issues'] > 0) {
            $summary .= "{$errorCategories['duplication_issues']} duplication issues. ";
        }

        // Generate recommendations
        $recommendations = [];
        if ($errorCategories['account_issues'] > 0) {
            $recommendations[] = "Download the latest account list and verify account numbers";
        }
        if ($errorCategories['format_issues'] > 0) {
            $recommendations[] = "Check date formats - use DD-MM-YYYY format";
        }
        if ($errorCategories['duplication_issues'] > 0) {
            $recommendations[] = "Remove duplicate bilyet numbers from your Excel file";
        }

        return [
            'summary' => $summary,
            'details' => [
                'errors' => $errors,
                'categories' => $errorCategories,
                'recommendations' => $recommendations,
                'total_errors' => $errorCount
            ]
        ];
    }

    public function buatArrayNomor()
    {
        // gabungkan prefix dan nomor dari table bilyet_temps
        $bilyet_nomors = BilyetTemp::selectRaw('prefix, nomor')
            ->where('created_by', auth()->user()->id)
            ->get();

        if ($bilyet_nomors->isEmpty()) {
            return [];
        }

        foreach ($bilyet_nomors as $bilyet_nomor) {
            $bilyet_temp[] = $bilyet_nomor->prefix . $bilyet_nomor->nomor;
        }

        return $bilyet_temp;
    }

    private function cekLoanId($loan_id)
    {
        // if loan_id is not null, return loan_code. if not found, return 'NOT FOUND' with red text. if null, return null
        return $loan_id == null ? null : (Loan::where('id', $loan_id)->exists() ? Loan::find($loan_id)->loan_code : '<span style="color: red;"><small><strong>NOT FOUND</strong></small></span>');
    }
}
