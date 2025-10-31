<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use App\Models\Department;
use App\Models\Project;
use App\Models\RealizationDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalRequestRealizationController extends Controller
{
    public function index()
    {
        $document_count = app(ToolController::class)->approval_documents_count();

        return view('approvals-request.realizations.index', compact('document_count'));
    }

    public function show($id)
    {
        $document = ApprovalPlan::find($id);
        $document_details = $document->realization->realizationDetails;
        $payreq = $document->realization->payreq;
        $payreq->load('outgoings'); // Eager load outgoings relationship
        $departments = Department::orderBy('department_name')->get();
        $projects = Project::where('is_active', 1)->orderBy('code')->get();

        return view('approvals-request.realizations.show', compact([
            'document',
            'document_details',
            'payreq',
            'departments',
            'projects'
        ]));
    }

    public function data()
    {
        $approval_requests = ApprovalPlan::where('is_open', 1)
            ->where('document_type', 'realization')
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->get();

        return datatables()->of($approval_requests)
            ->addColumn('id', function ($approval_request) {
                return $approval_request->id;
            })
            ->addColumn('nomor', function ($approval_request) {
                return $approval_request->realization->nomor;
            })
            ->addColumn('payreq_no', function ($approval_request) {
                return $approval_request->realization->payreq->nomor;
            })
            ->addColumn('submit_at', function ($approval_request) {
                return $approval_request->realization->submit_at->addHours(8)->format('d-M-Y H:i:s') . ' wita';
            })
            ->addColumn('amount', function ($approval_request) {
                return number_format($approval_request->realization->realizationDetails->sum('amount'), 2);
            })
            ->addColumn('requestor', function ($approval_request) {
                return $approval_request->realization->requestor->name;
            })
            ->addColumn('days', function ($approval_request) {
                return $approval_request->realization->submit_at->diffInDays(now());
            })
            ->addIndexColumn()
            ->addColumn('action', 'approvals-request.realizations.action')
            ->rawColumns(['action'])
            ->toJson();
    }

    public function updateDetails(Request $request, $id)
    {
        $request->validate([
            'details' => 'required|array',
            'details.*.id' => 'nullable|exists:realization_details,id',
            'details.*.description' => 'required|string|max:255',
            'details.*.amount' => 'required|numeric|min:0',
            'details.*.department_id' => 'nullable|exists:departments,id',
            'details.*.project' => 'nullable|string|max:10|exists:projects,code',
            'details.*.unit_no' => 'nullable|string|max:20',
            'details.*.type' => 'nullable|string|max:10',
            'details.*.qty' => 'nullable|integer',
            'details.*.uom' => 'nullable|string|max:10',
            'details.*.km_position' => 'nullable|integer',
            'deleted_ids' => 'nullable|array',
            'deleted_ids.*' => 'exists:realization_details,id',
        ]);

        try {
            DB::beginTransaction();

            $document = ApprovalPlan::findOrFail($id);
            $realization = $document->realization;

            // Delete removed details
            if ($request->has('deleted_ids') && !empty($request->deleted_ids)) {
                RealizationDetail::whereIn('id', $request->deleted_ids)
                    ->where('realization_id', $realization->id)
                    ->delete();
            }

            // Update or create details
            foreach ($request->details as $detailData) {
                if (isset($detailData['id']) && $detailData['id']) {
                    // Update existing detail
                    $detail = RealizationDetail::where('id', $detailData['id'])
                        ->where('realization_id', $realization->id)
                        ->first();

                    if ($detail) {
                        $detail->update([
                            'description' => $detailData['description'],
                            'amount' => $detailData['amount'],
                            'department_id' => $detailData['department_id'] ?? null,
                            'project' => $detailData['project'] ?? null,
                            'unit_no' => $detailData['unit_no'] ?? null,
                            'type' => $detailData['type'] ?? null,
                            'qty' => $detailData['qty'] ?? null,
                            'uom' => $detailData['uom'] ?? null,
                            'km_position' => $detailData['km_position'] ?? null,
                        ]);
                    }
                } else {
                    // Create new detail
                    RealizationDetail::create([
                        'realization_id' => $realization->id,
                        'description' => $detailData['description'],
                        'amount' => $detailData['amount'],
                        'department_id' => $detailData['department_id'] ?? null,
                        'project' => $detailData['project'] ?? null,
                        'unit_no' => $detailData['unit_no'] ?? null,
                        'type' => $detailData['type'] ?? null,
                        'qty' => $detailData['qty'] ?? null,
                        'uom' => $detailData['uom'] ?? null,
                        'km_position' => $detailData['km_position'] ?? null,
                    ]);
                }
            }

            // Mark realization as modified by approver
            $realization->update([
                'modified_by_approver' => true,
                'modified_by_approver_at' => now(),
                'modified_by_approver_id' => auth()->id(),
            ]);

            DB::commit();

            // Reload details for response
            $updatedDetails = $realization->fresh()->realizationDetails;

            return response()->json([
                'success' => true,
                'message' => 'Realization details updated successfully. Document needs to be reprinted.',
                'details' => $updatedDetails,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update details: ' . $e->getMessage(),
            ], 500);
        }
    }
}
