<?php

namespace App\Http\Controllers\Cashier;

use App\Exports\BilyetTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\BilyetController as ReportsBilyetController;
use App\Http\Controllers\UserController;
use App\Http\Requests\StoreBilyetRequest;
use App\Http\Requests\UpdateBilyetRequest;
use App\Http\Requests\SuperAdminUpdateBilyetRequest;
use App\Http\Requests\BulkUpdateBilyetRequest;
use App\Models\Bilyet;
use App\Models\BilyetTemp;
use App\Models\Giro;
use App\Models\Loan;
use App\Models\Project;
use App\Services\BilyetService;
use Carbon\Carbon;
use App\Events\BilyetCreated;
use App\Events\BilyetUpdated;
use App\Events\BilyetStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class BilyetController extends Controller
{
    protected $bilyetService;

    public function __construct(BilyetService $bilyetService)
    {
        $this->bilyetService = $bilyetService;
    }

    public function index(Request $request)
    {
        $page = $request->query('page', 'dashboard');
        $userRoles = app(UserController::class)->getUserRoles();

        if (array_intersect(['admin', 'superadmin'], $userRoles)) {
            $giros = Giro::with('bank')->orderBy('acc_no')->get();
        } else {
            $giros = Giro::with('bank')->where('project', auth()->user()->project)->orderBy('acc_no')->get();
        }

        $views = [
            'dashboard' => 'cashier.bilyets.dashboard',
            'list' => 'cashier.bilyets.list',           // New unified list page
            'upload' => 'cashier.bilyets.upload',
        ];

        // Add fallback for invalid page parameters
        $allowedPages = ['dashboard', 'list', 'upload'];
        if (!in_array($page, $allowedPages)) {
            $page = 'dashboard'; // fallback to dashboard
        }

        if ($page === 'dashboard') {
            $data = app(ReportsBilyetController::class)->dashboardData();
            return view($views[$page], compact('data'));
        } elseif ($page === 'list') {
            // Get all bilyets for filtering - will be loaded via AJAX
            // Also get onhand bilyets for update many modal
            if (array_intersect(['admin', 'superadmin'], $userRoles)) {
                $onhands = Bilyet::where('status', 'onhand')
                    ->with(['giro.bank'])
                    ->orderBy('prefix', 'asc')->orderBy('nomor', 'asc')
                    ->get();
            } else {
                $onhands = Bilyet::where('status', 'onhand')
                    ->where('project', auth()->user()->project)
                    ->with(['giro.bank'])
                    ->orderBy('prefix', 'asc')->orderBy('nomor', 'asc')
                    ->get();
            }

            return view($views[$page], compact('giros', 'onhands'));
        } elseif ($page === 'upload') {
            // count giro_id that is null
            $giro_id_null = BilyetTemp::where('giro_id', null)->where('created_by', auth()->user()->id)->count();

            // cek data exist atau ngga
            $exist = BilyetTemp::where('created_by', auth()->user()->id)->exists();

            // cek duplikasi dan duplikasi tabel tujuan
            $duplikasi = app(BilyetTempController::class)->cekDuplikasi();
            $duplikasi_bilyet = app(BilyetTempController::class)->cekDuplikasiTabelTujuan();

            // jika ada giro_id yang null atau duplikasi, disable button import
            $import_button = !$exist || $giro_id_null > 0 || !empty($duplikasi) || !empty($duplikasi_bilyet) ? 'disabled' : null;
            $empty_button = $exist ? null : 'disabled';

            return view($views[$page], compact('giros', 'import_button', 'empty_button', 'duplikasi', 'duplikasi_bilyet'));
        } else {
            return view($views[$page], compact('giros'));
        }
    }

    public function store(StoreBilyetRequest $request)
    {
        $data = $request->validated();

        // Determine status based on data completeness
        $data['status'] = $this->determineStatus($data);
        $data['created_by'] = auth()->id();

        $bilyet = Bilyet::create($data);

        // Fire event for audit trail
        event(new BilyetCreated($bilyet, auth()->user(), $data));

        // Check referer to redirect properly to list page if coming from there
        if (strpos($request->headers->get('referer'), 'page=list') !== false) {
            return redirect()->route('cashier.bilyets.index', ['page' => 'list'])->with('success', 'Bilyet created successfully.');
        }

        return redirect()->back()->with('success', 'Bilyet created successfully.');
    }

    public function edit($id)
    {
        // Check if user has superadmin role
        if (!auth()->user()->hasRole('superadmin')) {
            abort(403, 'Access denied. Superadmin role required.');
        }

        $bilyet = Bilyet::with(['giro.bank', 'loan', 'creator'])->findOrFail($id);

        // Get data for dropdowns
        $giros = Giro::with('bank')->orderBy('acc_no')->get();
        $loans = Loan::orderBy('loan_code')->get();
        $projects = Project::where('is_active', 1)->orderBy('code')->get();

        return view('cashier.bilyets.edit', compact('bilyet', 'giros', 'loans', 'projects'));
    }

    public function update(UpdateBilyetRequest $request, $id)
    {
        $bilyet = Bilyet::find($id);

        if (!$bilyet) {
            return redirect()->back()->with('error', 'Bilyet not found.');
        }

        $data = $request->validated();

        if ($request->is_void) {
            $oldValues = $bilyet->toArray();
            $bilyet->update(['status' => 'void']);

            // Fire event for audit trail
            event(new BilyetUpdated($bilyet, auth()->user(), $oldValues, ['status' => 'void'], 'voided'));
        } else {
            // Determine new status based on data completeness
            $newStatus = $this->determineStatus($data);

            // Validate status transition
            if (!$bilyet->canTransitionTo($newStatus)) {
                return redirect()->back()->with(
                    'error',
                    'Invalid status transition from ' . $bilyet->status_label . ' to ' . Bilyet::STATUS_LABELS[$newStatus]
                );
            }

            $oldValues = $bilyet->toArray();
            $data['status'] = $newStatus;
            $bilyet->update($data);

            // Fire events for audit trail
            event(new BilyetUpdated($bilyet, auth()->user(), $oldValues, $data, 'updated'));

            // Fire status change event if status changed
            if ($oldValues['status'] !== $newStatus) {
                event(new BilyetStatusChanged($bilyet, auth()->user(), $oldValues['status'], $newStatus));
            }
        }

        return redirect()->back()->with('success', 'Bilyet updated successfully.');
    }

    public function superAdminUpdate(SuperAdminUpdateBilyetRequest $request, $id)
    {
        // Check if user has superadmin role
        if (!auth()->user()->hasRole('superadmin')) {
            abort(403, 'Access denied. Superadmin role required.');
        }

        $bilyet = Bilyet::findOrFail($id);

        $oldValues = $bilyet->toArray();
        $data = $request->validated();

        // For superadmin: Validate status transition but allow override with justification
        $requestedStatus = $data['status'];
        $isStatusTransitionValid = $bilyet->canTransitionTo($requestedStatus);

        // If status transition is invalid, check if superadmin provided justification
        if (!$isStatusTransitionValid) {
            $hasJustification = !empty($data['remarks']) && strlen(trim($data['remarks'])) > 10;

            if (!$hasJustification) {
                return redirect()->back()->with(
                    'error',
                    'Invalid status transition from ' . $bilyet->status_label . ' to ' . Bilyet::STATUS_LABELS[$requestedStatus] .
                        '. As superadmin, you can override this by providing a detailed reason in the remarks field (minimum 10 characters).'
                );
            }

            // Add superadmin override note to remarks
            $overrideNote = "\n\n[SUPERADMIN OVERRIDE: Status changed from {$bilyet->status_label} to " . Bilyet::STATUS_LABELS[$requestedStatus] . " - " . now()->format('Y-m-d H:i:s') . "]";
            $data['remarks'] = trim($data['remarks']) . $overrideNote;
        }

        // Update the bilyet with all provided data
        $bilyet->update($data);

        // Fire event for audit trail with superadmin context
        event(new BilyetUpdated($bilyet, auth()->user(), $oldValues, $data, 'superadmin_updated'));

        // Check if status changed and fire status change event
        if ($oldValues['status'] !== $data['status']) {
            event(new BilyetStatusChanged($bilyet, auth()->user(), $oldValues['status'], $data['status']));
        }

        return redirect()->route('cashier.bilyets.index', ['page' => 'list'])
            ->with('success', 'Bilyet updated successfully by superadmin.');
    }

    /**
     * Determine the appropriate status based on request data
     */
    private function determineStatus($data)
    {
        if (isset($data['amount']) && $data['amount'] && isset($data['bilyet_date']) && $data['bilyet_date'] && isset($data['cair_date']) && $data['cair_date']) {
            return 'cair';
        } elseif ((isset($data['amount']) && $data['amount']) || (isset($data['bilyet_date']) && $data['bilyet_date'])) {
            return 'release';
        } else {
            return 'onhand';
        }
    }

    public function void($id)
    {
        $bilyet = Bilyet::find($id);

        if (!$bilyet) {
            return redirect()->back()->with('error', 'Bilyet not found.');
        }

        // Check authorization using policy
        $this->authorize('void', $bilyet);

        if ($bilyet->status === 'void') {
            return redirect()->back()->with('warning', 'Bilyet is already voided.');
        }

        $bilyet->update(['status' => 'void']);

        return redirect()->back()->with('success', 'Bilyet voided successfully. Status changed to void while preserving other data.');
    }

    public function export()
    {
        return Excel::download(new BilyetTemplateExport, 'bilyet_template.xlsx');
    }

    public function destroy($id)
    {
        $bilyet = Bilyet::find($id);

        if (!$bilyet) {
            return redirect()->back()->with('error', 'Bilyet not found.');
        }

        // Check authorization using policy
        $this->authorize('delete', $bilyet);

        $bilyet->delete();

        return redirect()->back()->with('success', 'Bilyet deleted successfully.');
    }

    public function import(Request $request)
    {
        $receive_date = $request->receive_date;
        $userId = auth()->user()->id;

        Log::info('Import request received', [
            'receive_date' => $receive_date,
            'user_id' => $userId,
            'request_data' => $request->all()
        ]);

        // Get all data from bilyet_temp
        $bilyets = BilyetTemp::where('created_by', $userId)->get();

        Log::info('Starting final import process', [
            'user_id' => $userId,
            'temp_records_count' => $bilyets->count(),
            'sample_data' => $bilyets->take(3)->map(function ($b) {
                return [
                    'prefix' => $b->prefix,
                    'nomor' => $b->nomor,
                    'giro_id' => $b->giro_id,
                    'acc_no' => $b->acc_no
                ];
            })
        ]);

        if ($bilyets->isEmpty()) {
            return redirect()->back()->with('error', 'No data found to import.');
        }

        $importedCount = 0;
        $startTime = microtime(true);

        try {
            Log::info('About to start import process', [
                'user_id' => $userId,
                'receive_date' => $receive_date,
                'bilyets_count' => $bilyets->count()
            ]);

            // Process records directly
            $data = [];
            $now = now();
            $skippedCount = 0;

            Log::info('Processing records directly', [
                'total_records' => $bilyets->count(),
                'receive_date' => $receive_date
            ]);

            foreach ($bilyets as $bilyet) {
                // Skip records with null giro_id (invalid account)
                if (!$bilyet->giro_id) {
                    $skippedCount++;
                    Log::warning('Skipping bilyet with null giro_id', [
                        'prefix' => $bilyet->prefix,
                        'nomor' => $bilyet->nomor,
                        'acc_no' => $bilyet->acc_no
                    ]);
                    continue;
                }

                // Determine status based on data completeness
                if ($bilyet->amount && $bilyet->bilyet_date && $bilyet->cair_date) {
                    $status = 'cair';
                } else {
                    $status = $bilyet->amount || $bilyet->bilyet_date ? 'release' : 'onhand';
                }

                $data[] = [
                    'giro_id' => $bilyet->giro_id,
                    'prefix' => $bilyet->prefix,
                    'nomor' => $bilyet->nomor,
                    'type' => $bilyet->type,
                    'receive_date' => $receive_date ? Carbon::parse($receive_date)->format('Y-m-d') : null,
                    'bilyet_date' => $bilyet->bilyet_date,
                    'cair_date' => $bilyet->cair_date,
                    'amount' => $bilyet->amount,
                    'remarks' => $bilyet->remarks,
                    'loan_id' => $bilyet->loan_id,
                    'status' => $status,
                    'created_by' => $bilyet->created_by,
                    'project' => $bilyet->project,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Bulk insert for better performance
            if (!empty($data)) {
                Log::info('Inserting data to bilyets table', [
                    'data_count' => count($data),
                    'sample_record' => $data[0] ?? null
                ]);

                Bilyet::insert($data);
                $importedCount += count($data);
                Log::info('Data inserted successfully', [
                    'imported_count' => count($data)
                ]);
            } else {
                Log::warning('No data to insert', [
                    'skipped_count' => $skippedCount
                ]);
            }

            // Delete temp data after successful import
            BilyetTemp::where('created_by', $userId)->delete();

            $duration = microtime(true) - $startTime;

            // Log import statistics
            Log::info('Bilyet import completed', [
                'user_id' => $userId,
                'imported_count' => $importedCount,
                'duration_seconds' => round($duration, 2),
                'receive_date' => $receive_date
            ]);

            if ($importedCount > 0) {
                return redirect()->back()->with(
                    'success',
                    "Import completed successfully! {$importedCount} records imported in " . round($duration, 2) . " seconds."
                );
            } else {
                return redirect()->back()->with(
                    'error',
                    "Import completed but no records were imported. This usually means the account numbers in your Excel file don't exist in the system. Please check the account numbers and try again."
                );
            }
        } catch (\Exception $e) {
            Log::error('Bilyet import failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with(
                'error',
                'Import failed: ' . $e->getMessage() . '. Please try again or contact support.'
            );
        }
    }

    public function update_many(BulkUpdateBilyetRequest $request)
    {
        $data = $request->validated();
        $updatedCount = $this->bilyetService->processBulkUpdate($data['bilyet_ids'], $data);

        // Check from_page to redirect properly
        if ($request->from_page === 'list') {
            return redirect()->route('cashier.bilyets.index', ['page' => 'list'])
                ->with('success', "Successfully updated {$updatedCount} bilyets.");
        }

        return redirect()->route('cashier.bilyets.index')
            ->with('success', "Successfully updated {$updatedCount} bilyets.");
    }

    public function data(Request $request)
    {
        $userRoles = app(UserController::class)->getUserRoles();

        // Check if any filter is applied
        $hasFilter = $this->hasAnyFilter($request);

        // Debug logging
        \Illuminate\Support\Facades\Log::info('Bilyet data request', [
            'status' => $request->query('status'),
            'giro_id' => $request->query('giro_id'),
            'nomor' => $request->query('nomor'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'user_roles' => $userRoles,
            'has_filter' => $hasFilter
        ]);

        // If no filter applied, return empty result
        if (!$hasFilter) {
            return datatables()->of(collect())->toJson();
        }

        // Use service to get filtered data
        $filters = [
            'status' => $request->query('status'),
            'giro_id' => $request->query('giro_id'),
            'nomor' => $request->query('nomor'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'amount_from' => $request->query('amount_from'),
            'amount_to' => $request->query('amount_to'),
        ];

        $bilyets = $this->bilyetService->getFilteredBilyets($filters, $userRoles, auth()->user()->project);

        \Illuminate\Support\Facades\Log::info('Bilyet data result count: ' . $bilyets->count());

        return datatables()->of($bilyets)
            ->editColumn('account', function ($bilyet) {
                $remarks = $bilyet->remarks ? $bilyet->remarks : '';
                $bankName = $bilyet->giro && $bilyet->giro->bank ? $bilyet->giro->bank->name : 'N/A';
                $currency = $bilyet->giro ? strtoupper($bilyet->giro->curr) : 'N/A';
                $accNo = $bilyet->giro ? $bilyet->giro->acc_no : 'N/A';
                return '<small>' . $bankName . ' ' . $currency . ' | ' . $accNo . '<br>' . $remarks . '</small>';
            })
            ->editColumn('nomor', function ($bilyet) {
                return $bilyet->prefix . $bilyet->nomor;
            })
            ->editColumn('type', function ($bilyet) {
                return strtoupper($bilyet->type);
            })
            ->editColumn('bilyet_date', function ($bilyet) {
                return $bilyet->bilyet_date ? date('d-M-Y', strtotime($bilyet->bilyet_date)) : '-';
            })
            ->editColumn('cair_date', function ($bilyet) {
                return $bilyet->cair_date ? date('d-M-Y', strtotime($bilyet->cair_date)) : '-';
            })
            ->editColumn('status', function ($bilyet) {
                $statusBadges = [
                    'onhand' => '<span class="badge badge-primary">Onhand</span>',
                    'release' => '<span class="badge badge-warning">Release</span>',
                    'cair' => '<span class="badge badge-success">Cair</span>',
                    'void' => '<span class="badge badge-danger">Void</span>',
                ];
                return $statusBadges[$bilyet->status] ?? $bilyet->status;
            })
            ->editColumn('amount', function ($bilyet) {
                if ($bilyet->amount && $bilyet->amount > 0) {
                    return '<span class="amount-value" data-amount="' . $bilyet->amount . '">'
                        . number_format($bilyet->amount, 0, ',', '.') . ',-</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('checkbox', function ($bilyet) {
                // Only show checkbox for bilyets with amount
                if ($bilyet->amount && $bilyet->amount > 0) {
                    return '<div class="text-center">
                                <input type="checkbox" class="bilyet-checkbox"
                                       data-id="' . $bilyet->id . '"
                                       data-amount="' . $bilyet->amount . '"
                                       data-status="' . $bilyet->status . '"
                                       data-type="' . strtoupper($bilyet->type) . '"
                                       value="' . $bilyet->id . '">
                            </div>';
                }
                return '<div class="text-center"><span class="text-muted">-</span></div>';
            })
            ->addColumn('action', function ($bilyet) {
                try {
                    return view('cashier.bilyets.list_action', ['model' => $bilyet])->render();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error rendering bilyet action template', [
                        'bilyet_id' => $bilyet->id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    return '<span class="text-danger">Error</span>';
                }
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'account', 'status', 'checkbox', 'amount'])
            ->toJson();
    }

    private function hasAnyFilter($request)
    {
        $status = $request->query('status');
        $giroId = $request->query('giro_id');
        $nomor = $request->query('nomor');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $amountFrom = $request->query('amount_from');
        $amountTo = $request->query('amount_to');

        return !empty($status) || !empty($giroId) || !empty($nomor) ||
            !empty($dateFrom) || !empty($dateTo) || !empty($amountFrom) || !empty($amountTo);
    }

    /**
     * Get statistics for selected bilyets
     */
    public function getStatistics(Request $request)
    {
        $bilyetIds = $request->input('bilyet_ids', []);

        if (empty($bilyetIds)) {
            return response()->json([
                'count' => 0,
                'total_amount' => 0,
                'average_amount' => 0,
                'status_breakdown' => [],
                'type_breakdown' => [],
                'amount_range' => null
            ]);
        }

        $statistics = $this->bilyetService->getSelectedStatistics($bilyetIds);

        return response()->json($statistics);
    }
}
