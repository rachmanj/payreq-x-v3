<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\PayreqController;
use App\Http\Requests\Api\StoreAdvancePayreqRequest;
use App\Http\Requests\Api\StoreReimbursePayreqRequest;
use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayreqApiController extends Controller
{
    /**
     * Display a listing of payment requests
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payreq::with(['requestor', 'department', 'anggaran']);

        // Apply filters
        if ($request->has('employee_id')) {
            $query->where('user_id', $request->employee_id);
        }

        if ($request->has('project')) {
            $query->where('project', $request->project);
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('amount_from')) {
            $query->where('amount', '>=', $request->amount_from);
        }

        if ($request->has('amount_to')) {
            $query->where('amount', '<=', $request->amount_to);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $payreqs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $payreqs->items(),
            'pagination' => [
                'current_page' => $payreqs->currentPage(),
                'last_page' => $payreqs->lastPage(),
                'per_page' => $payreqs->perPage(),
                'total' => $payreqs->total(),
                'from' => $payreqs->firstItem(),
                'to' => $payreqs->lastItem(),
            ],
        ]);
    }

    /**
     * Display the specified payment request
     */
    public function show($id): JsonResponse
    {
        $payreq = Payreq::with([
            'requestor',
            'department',
            'anggaran', // Use 'anggaran' instead of 'rab' (correct relationship)
            'approval_plans.approver',
            'realization.realizationDetails',
        ])->find($id);

        if (!$payreq) {
            return response()->json([
                'success' => false,
                'message' => 'Payment request not found',
            ], 404);
        }

        // Format the response data
        $data = $payreq->toArray();

        // Replace user_id with user name
        if ($payreq->requestor) {
            $data['user_name'] = $payreq->requestor->name;
            $data['user_email'] = $payreq->requestor->email ?? null;
        }

        // Replace department_id with department name
        if ($payreq->department) {
            $data['department_name'] = $payreq->department->department_name;
        }

        // Format dates to dd-mmm-yyyy hh:mm
        if ($payreq->submit_at) {
            $data['submit_at'] = $payreq->submit_at instanceof \Carbon\Carbon
                ? $payreq->submit_at->format('d-M-Y H:i')
                : Carbon::parse($payreq->submit_at)->format('d-M-Y H:i');
        }
        if ($payreq->created_at) {
            $data['created_at'] = $payreq->created_at instanceof \Carbon\Carbon
                ? $payreq->created_at->format('d-M-Y H:i')
                : Carbon::parse($payreq->created_at)->format('d-M-Y H:i');
        }
        if ($payreq->updated_at) {
            $data['updated_at'] = $payreq->updated_at instanceof \Carbon\Carbon
                ? $payreq->updated_at->format('d-M-Y H:i')
                : Carbon::parse($payreq->updated_at)->format('d-M-Y H:i');
        }
        if ($payreq->approved_at) {
            $data['approved_at'] = $payreq->approved_at instanceof \Carbon\Carbon
                ? $payreq->approved_at->format('d-M-Y H:i')
                : Carbon::parse($payreq->approved_at)->format('d-M-Y H:i');
        }
        if ($payreq->canceled_at) {
            $data['canceled_at'] = $payreq->canceled_at instanceof \Carbon\Carbon
                ? $payreq->canceled_at->format('d-M-Y H:i')
                : Carbon::parse($payreq->canceled_at)->format('d-M-Y H:i');
        }

        // Replace rab_id with rab code
        if ($payreq->anggaran) {
            $data['rab_code'] = $payreq->anggaran->rab_no ?? $payreq->anggaran->nomor;
            $data['rab_description'] = $payreq->anggaran->description ?? null;
            // Also include 'rab' key for backward compatibility
            $data['rab'] = $payreq->anggaran;
        }

        // Format approval plans - replace approver_id with approver name
        if (isset($data['approval_plans']) && is_array($data['approval_plans'])) {
            foreach ($data['approval_plans'] as &$plan) {
                if (isset($plan['approver'])) {
                    $plan['approver_name'] = $plan['approver']['name'] ?? 'N/A';
                    $plan['approver_email'] = $plan['approver']['email'] ?? null;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Store a new advance payment request
     */
    public function storeAdvance(StoreAdvancePayreqRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get employee details
            $employee = User::with('department')->findOrFail($request->employee_id);

            // Check RAB validation for specific projects
            $submit = $request->boolean('submit', false);
            if (in_array($employee->project, ['000H', 'APS']) && $submit && !$request->rab_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'RAB is required for projects 000H and APS when submitting',
                ], 422);
            }

            // Generate draft document number
            $payreqNo = app(DocumentNumberController::class)
                ->generate_draft_document_number($employee->project);

            // Prepare data for PayreqController
            $payreqData = new Request([
                'remarks' => $request->remarks,
                'amount' => $request->amount,
                'project' => $employee->project,
                'department_id' => $employee->department_id,
                'payreq_no' => $payreqNo,
                'payreq_type' => 'advance',
                'rab_id' => $request->rab_id,
                'employee_id' => $request->employee_id,
                'lot_no' => null, // LOT not supported in API
            ]);

            // Create payreq via existing controller
            $payreq = app(PayreqController::class)->store($payreqData);

            if (!$payreq) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment request',
                ], 500);
            }

            $approversCount = null;
            $approvalStatus = 'draft';

            // Handle submission if requested
            if ($submit) {
                $approversCount = app(ApprovalPlanController::class)
                    ->create_approval_plan('payreq', $payreq->id);

                if ($approversCount === false) {
                    // No approval plan found, keep as draft
                    $payreq->update([
                        'status' => 'draft',
                        'editable' => 1,
                        'deletable' => 1,
                    ]);

                    DB::commit();

                    Log::warning('API Payreq created as draft - no approval plan', [
                        'payreq_id' => $payreq->id,
                        'project' => $employee->project,
                        'department_id' => $employee->department_id,
                        'api_key' => $request->api_key_name ?? 'unknown',
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'No approval plan found for this project/department. Payment request saved as draft.',
                        'data' => [
                            'payreq' => $payreq->fresh()->load(['requestor', 'department', 'anggaran']),
                            'approval_status' => 'draft',
                            'approvers_count' => 0,
                        ],
                    ], 422);
                }

                // Update to submitted status
                $payreq->update([
                    'status' => 'submitted',
                    'editable' => 0,
                    'deletable' => 0,
                    'submit_at' => Carbon::now(),
                ]);

                $approvalStatus = 'submitted';
            }

            DB::commit();

            Log::info('API Payreq Advance created successfully', [
                'payreq_id' => $payreq->id,
                'employee_id' => $request->employee_id,
                'amount' => $request->amount,
                'status' => $approvalStatus,
                'api_key' => $request->api_key_name ?? 'unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment request created successfully',
                'data' => [
                    'payreq' => $payreq->fresh()->load(['requestor', 'department', 'anggaran']),
                    'approval_status' => $approvalStatus,
                    'approvers_count' => $approversCount,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('API Payreq Advance creation failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new reimburse payment request
     */
    public function storeReimburse(StoreReimbursePayreqRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get employee details
            $employee = User::with('department')->findOrFail($request->employee_id);

            // Check RAB validation for specific projects
            $submit = $request->boolean('submit', false);
            if (in_array($employee->project, ['000H', 'APS']) && $submit && !$request->rab_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'RAB is required for projects 000H and APS when submitting',
                ], 422);
            }

            // Generate draft document number for payreq
            $payreqNo = app(DocumentNumberController::class)
                ->generate_draft_document_number($employee->project);

            // Calculate total amount from details
            $totalAmount = collect($request->details)->sum('amount');

            // Prepare data for PayreqController
            $payreqData = new Request([
                'remarks' => $request->remarks,
                'amount' => $totalAmount,
                'project' => $employee->project,
                'department_id' => $employee->department_id,
                'payreq_no' => $payreqNo,
                'payreq_type' => 'reimburse',
                'rab_id' => $request->rab_id,
                'employee_id' => $request->employee_id,
                'lot_no' => null,
            ]);

            // Create payreq
            $payreq = app(PayreqController::class)->store($payreqData);

            if (!$payreq) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment request',
                ], 500);
            }

            // Generate draft document number for realization
            $realizationNo = app(DocumentNumberController::class)
                ->generate_draft_document_number($employee->project);

            // Create realization
            $realization = Realization::create([
                'payreq_id' => $payreq->id,
                'project' => $employee->project,
                'department_id' => $employee->department_id,
                'remarks' => $request->remarks,
                'user_id' => $request->employee_id,
                'nomor' => $realizationNo,
                'status' => 'reimburse-draft',
            ]);

            // Create realization details
            $createdDetails = [];
            foreach ($request->details as $detail) {
                $realizationDetail = $realization->realizationDetails()->create([
                    'description' => $detail['description'],
                    'amount' => $detail['amount'],
                    'project' => $employee->project,
                    'department_id' => $employee->department_id,
                    'unit_no' => $detail['unit_no'] ?? null,
                    'nopol' => $detail['nopol'] ?? null,
                    'type' => $detail['type'] ?? null,
                    'qty' => $detail['qty'] ?? null,
                    'uom' => $detail['uom'] ?? null,
                    'km_position' => $detail['km_position'] ?? null,
                ]);

                $createdDetails[] = $realizationDetail;
            }

            // Update payreq amount with actual sum from details
            $actualTotal = $realization->realizationDetails()->sum('amount');
            $payreq->update(['amount' => $actualTotal]);

            $approversCount = null;
            $approvalStatus = 'draft';

            // Handle submission if requested
            if ($submit) {
                $approversCount = app(ApprovalPlanController::class)
                    ->create_approval_plan('payreq', $payreq->id);

                if ($approversCount === false) {
                    // No approval plan found, keep as draft
                    $payreq->update([
                        'status' => 'draft',
                        'editable' => 1,
                        'deletable' => 1,
                    ]);

                    DB::commit();

                    Log::warning('API Payreq Reimburse created as draft - no approval plan', [
                        'payreq_id' => $payreq->id,
                        'realization_id' => $realization->id,
                        'project' => $employee->project,
                        'department_id' => $employee->department_id,
                        'api_key' => $request->api_key_name ?? 'unknown',
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'No approval plan found for this project/department. Payment request saved as draft.',
                        'data' => [
                            'payreq' => $payreq->fresh()->load(['requestor', 'department', 'anggaran']),
                            'realization' => $realization->fresh()->load('realizationDetails'),
                            'approval_status' => 'draft',
                            'approvers_count' => 0,
                        ],
                    ], 422);
                }

                // Generate official document numbers
                $officialPayreqNo = app(DocumentNumberController::class)
                    ->generate_document_number('payreq', $employee->project);
                $officialRealizationNo = app(DocumentNumberController::class)
                    ->generate_document_number('realization', $employee->project);

                // Update payreq to submitted
                $payreq->update([
                    'status' => 'submitted',
                    'printable' => 1,
                    'draft_no' => $payreq->nomor,
                    'nomor' => $officialPayreqNo,
                    'editable' => 0,
                    'deletable' => 0,
                    'submit_at' => Carbon::now(),
                ]);

                // Update realization to submitted
                $realization->update([
                    'status' => 'reimburse-submitted',
                    'submit_at' => Carbon::now(),
                    'editable' => 0,
                    'deletable' => 0,
                    'draft_no' => $realization->nomor,
                    'nomor' => $officialRealizationNo,
                ]);

                $approvalStatus = 'submitted';
            }

            DB::commit();

            Log::info('API Payreq Reimburse created successfully', [
                'payreq_id' => $payreq->id,
                'realization_id' => $realization->id,
                'employee_id' => $request->employee_id,
                'amount' => $actualTotal,
                'details_count' => count($createdDetails),
                'status' => $approvalStatus,
                'api_key' => $request->api_key_name ?? 'unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reimburse payment request created successfully',
                'data' => [
                    'payreq' => $payreq->fresh()->load(['requestor', 'department', 'anggaran']),
                    'realization' => $realization->fresh()->load('realizationDetails'),
                    'approval_status' => $approvalStatus,
                    'approvers_count' => $approversCount,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('API Payreq Reimburse creation failed', [
                'employee_id' => $request->employee_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create reimburse payment request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a draft payment request
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        try {
            $payreq = Payreq::find($id);

            if (!$payreq) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment request not found',
                ], 404);
            }

            // Only allow cancellation of draft status
            if ($payreq->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft payment requests can be cancelled. Current status: ' . $payreq->status,
                ], 422);
            }

            // Use existing cancel method
            app(PayreqController::class)->cancel($id);

            Log::info('API Payreq cancelled', [
                'payreq_id' => $id,
                'api_key' => $request->api_key_name ?? 'unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment request cancelled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('API Payreq cancellation failed', [
                'payreq_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active RAB/Budget list for a user
     */
    public function getRabs(Request $request): JsonResponse
    {
        try {
            // Validate employee_id parameter
            $request->validate([
                'employee_id' => 'required|exists:users,id',
            ]);

            // Get employee details
            $employee = User::with('department')->findOrFail($request->employee_id);

            // Get RABs based on usage type
            // 1. Project RABs - available to all users in the project
            $projectRabs = Anggaran::where('usage', 'project')
                ->where('project', $employee->project)
                ->whereIn('status', ['approved', 'close'])
                ->where('is_active', 1)
                ->select([
                    'id',
                    'nomor',
                    'rab_no',
                    'description',
                    'project',
                    'rab_project',
                    'department_id',
                    'usage',
                    'amount',
                    'balance',
                    'status',
                    'date',
                    'periode_anggaran',
                    'periode_ofr',
                    'created_by'
                ])
                ->with(['createdBy:id,name'])
                ->orderBy('date', 'desc')
                ->get();

            // 2. Department RABs - available to all users in the department
            $departmentRabs = Anggaran::where('usage', 'department')
                ->where('department_id', $employee->department_id)
                ->whereIn('status', ['approved', 'close'])
                ->where('is_active', 1)
                ->select([
                    'id',
                    'nomor',
                    'rab_no',
                    'description',
                    'project',
                    'rab_project',
                    'department_id',
                    'usage',
                    'amount',
                    'balance',
                    'status',
                    'date',
                    'periode_anggaran',
                    'periode_ofr',
                    'created_by'
                ])
                ->with(['createdBy:id,name'])
                ->orderBy('date', 'desc')
                ->get();

            // 3. User RABs - created by the specific user
            $userRabs = Anggaran::where('usage', 'user')
                ->where('created_by', $employee->id)
                ->where('is_active', 1)
                ->select([
                    'id',
                    'nomor',
                    'rab_no',
                    'description',
                    'project',
                    'rab_project',
                    'department_id',
                    'usage',
                    'amount',
                    'balance',
                    'status',
                    'date',
                    'periode_anggaran',
                    'periode_ofr',
                    'created_by'
                ])
                ->with(['createdBy:id,name'])
                ->orderBy('date', 'desc')
                ->limit(300)
                ->get();

            // Merge all RABs
            $allRabs = $projectRabs->merge($departmentRabs)->merge($userRabs);

            Log::info('API RABs retrieved successfully', [
                'employee_id' => $request->employee_id,
                'project' => $employee->project,
                'department_id' => $employee->department_id,
                'total_rabs' => $allRabs->count(),
                'api_key' => $request->api_key_name ?? 'unknown',
            ]);

            return response()->json([
                'success' => true,
                'data' => $allRabs,
                'summary' => [
                    'total' => $allRabs->count(),
                    'project_rabs' => $projectRabs->count(),
                    'department_rabs' => $departmentRabs->count(),
                    'user_rabs' => $userRabs->count(),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('API RABs retrieval failed', [
                'employee_id' => $request->employee_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve RABs: ' . $e->getMessage(),
            ], 500);
        }
    }
}
