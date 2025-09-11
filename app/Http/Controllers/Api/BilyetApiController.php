<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bilyet;
use App\Models\BilyetAudit;
use App\Http\Requests\StoreBilyetRequest;
use App\Http\Requests\UpdateBilyetRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BilyetApiController extends Controller
{
    /**
     * Display a listing of bilyets
     */
    public function index(Request $request): JsonResponse
    {
        $query = Bilyet::with(['giro.bank', 'creator', 'loan']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('project')) {
            $query->where('project', $request->project);
        }

        if ($request->has('giro_id')) {
            $query->where('giro_id', $request->giro_id);
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
        $bilyets = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $bilyets->items(),
            'pagination' => [
                'current_page' => $bilyets->currentPage(),
                'last_page' => $bilyets->lastPage(),
                'per_page' => $bilyets->perPage(),
                'total' => $bilyets->total(),
                'from' => $bilyets->firstItem(),
                'to' => $bilyets->lastItem()
            ]
        ]);
    }

    /**
     * Store a newly created bilyet
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'giro_id' => 'required|exists:giros,id',
            'prefix' => 'required|string|max:10',
            'nomor' => 'required|string|max:30',
            'type' => 'required|in:cek,bilyet,loa',
            'bilyet_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
            'loan_id' => 'nullable|exists:loans,id',
            'project' => 'nullable|string|max:50',
            'filename' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['status'] = $this->determineStatus($data);
        $data['created_by'] = auth()->id();

        // Check for duplicate
        $duplicate = Bilyet::where('prefix', $data['prefix'])
            ->where('nomor', $data['nomor'])
            ->where('giro_id', $data['giro_id'])
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Bilyet with this number already exists for this bank account'
            ], 409);
        }

        $bilyet = Bilyet::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Bilyet created successfully',
            'data' => $bilyet->load(['giro.bank', 'creator'])
        ], 201);
    }

    /**
     * Display the specified bilyet
     */
    public function show($id): JsonResponse
    {
        $bilyet = Bilyet::with(['giro.bank', 'creator', 'loan', 'audits.user'])
            ->find($id);

        if (!$bilyet) {
            return response()->json([
                'success' => false,
                'message' => 'Bilyet not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $bilyet
        ]);
    }

    /**
     * Update the specified bilyet
     */
    public function update(Request $request, $id): JsonResponse
    {
        $bilyet = Bilyet::find($id);

        if (!$bilyet) {
            return response()->json([
                'success' => false,
                'message' => 'Bilyet not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'giro_id' => 'sometimes|exists:giros,id',
            'prefix' => 'sometimes|string|max:10',
            'nomor' => 'sometimes|string|max:30',
            'type' => 'sometimes|in:cek,bilyet,loa',
            'bilyet_date' => 'sometimes|date',
            'cair_date' => 'nullable|date',
            'receive_date' => 'nullable|date',
            'amount' => 'sometimes|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
            'loan_id' => 'nullable|exists:loans,id',
            'project' => 'nullable|string|max:50',
            'filename' => 'nullable|string|max:255',
            'status' => 'sometimes|in:onhand,release,cair,void'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldValues = $bilyet->toArray();
        $data = $request->all();

        // Determine new status if not provided
        if (!isset($data['status'])) {
            $data['status'] = $this->determineStatus($data);
        }

        // Validate status transition
        if (!$bilyet->canTransitionTo($data['status'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition from ' . $bilyet->status_label . ' to ' . Bilyet::STATUS_LABELS[$data['status']]
            ], 422);
        }

        $bilyet->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Bilyet updated successfully',
            'data' => $bilyet->load(['giro.bank', 'creator'])
        ]);
    }

    /**
     * Remove the specified bilyet
     */
    public function destroy($id): JsonResponse
    {
        $bilyet = Bilyet::find($id);

        if (!$bilyet) {
            return response()->json([
                'success' => false,
                'message' => 'Bilyet not found'
            ], 404);
        }

        if ($bilyet->status !== 'onhand') {
            return response()->json([
                'success' => false,
                'message' => 'Only onhand bilyets can be deleted'
            ], 422);
        }

        $bilyet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bilyet deleted successfully'
        ]);
    }

    /**
     * Get bilyet statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $query = Bilyet::query();

        // Apply filters
        if ($request->has('project')) {
            $query->where('project', $request->project);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $bilyets = $query->get();

        $statistics = [
            'total_count' => $bilyets->count(),
            'total_amount' => $bilyets->sum('amount'),
            'average_amount' => $bilyets->avg('amount'),
            'status_breakdown' => $bilyets->groupBy('status')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('amount')
                ];
            }),
            'type_breakdown' => $bilyets->groupBy('type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('amount')
                ];
            }),
            'bank_breakdown' => $bilyets->load('giro.bank')->groupBy('giro.bank.name')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('amount')
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get bilyet audit trail
     */
    public function auditTrail(Request $request): JsonResponse
    {
        $query = BilyetAudit::with(['bilyet.giro.bank', 'user']);

        // Apply filters
        if ($request->has('bilyet_id')) {
            $query->where('bilyet_id', $request->bilyet_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->get('per_page', 15);
        $audits = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $audits->items(),
            'pagination' => [
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
                'from' => $audits->firstItem(),
                'to' => $audits->lastItem()
            ]
        ]);
    }

    /**
     * Bulk update bilyets
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bilyet_ids' => 'required|array|min:1',
            'bilyet_ids.*' => 'exists:bilyets,id',
            'bilyet_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $bilyets = Bilyet::whereIn('id', $request->bilyet_ids)
            ->where('status', 'onhand')
            ->get();

        if ($bilyets->count() !== count($request->bilyet_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some bilyets are not in onhand status or do not exist'
            ], 422);
        }

        $updatedCount = 0;
        foreach ($bilyets as $bilyet) {
            $bilyet->update([
                'bilyet_date' => $request->bilyet_date,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
                'status' => 'release'
            ]);
            $updatedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$updatedCount} bilyets",
            'data' => ['updated_count' => $updatedCount]
        ]);
    }

    /**
     * Determine status based on data completeness
     */
    private function determineStatus(array $data): string
    {
        if (isset($data['cair_date']) && !empty($data['cair_date'])) {
            return 'cair';
        }

        if (isset($data['bilyet_date']) && !empty($data['bilyet_date'])) {
            return 'release';
        }

        return 'onhand';
    }
}
