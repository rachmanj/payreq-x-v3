<?php

namespace App\Http\Controllers;

use App\Models\ApprovalPlan;
use App\Models\Department;
use App\Models\RealizationDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalRequestPayreqController extends Controller
{
    public function index()
    {
        $document_count = app(ToolController::class)->approval_documents_count();

        return view('approvals-request.payreqs.index', compact('document_count'));
    }

    public function show($id)
    {
        $document = ApprovalPlan::find($id);
        $payreq = $document->payreq;
        $realization = $payreq->realization;
        $realization_details = $realization->realizationDetails;
        $departments = Department::orderBy('department_name')->get();

        return view('approvals-request.payreqs.show', compact([
            'document',
            'payreq',
            'realization',
            'realization_details',
            'departments'
        ]));
    }

    public function data()
    {
        $approval_requests = ApprovalPlan::where('document_type', 'payreq')
            ->where('is_open', 1)
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->get();

        return datatables()->of($approval_requests)
            ->addColumn('id', function ($approval_request) {
                return $approval_request->id;
            })
            ->addColumn('nomor', function ($approval_request) {
                return $approval_request->payreq->nomor;
            })
            ->addColumn('submit_at', function ($approval_request) {
                return $approval_request->payreq->submit_at->addHours(8)->format('d-M-Y H:i:s');
            })
            ->addColumn('type', function ($approval_request) {
                return ucfirst($approval_request->payreq->type);
            })
            ->addColumn('amount', function ($approval_request) {
                // if payreq type is advance
                if ($approval_request->payreq->type == 'advance') {
                    return number_format($approval_request->payreq->amount, 2);
                } else {
                    // $realization_details = $approval_request->payreq->realization->realizationDetails;
                    // $amount = $realization_details->sum('amount');
                    return number_format($approval_request->payreq->realization->realizationDetails->sum('amount'), 2);
                }
            })
            ->addColumn('requestor', function ($approval_request) {
                return $approval_request->payreq->requestor->name;
            })
            ->addColumn('days', function ($approval_request) {
                return $approval_request->payreq->submit_at->diffInDays(now());
            })
            ->addIndexColumn()
            ->addColumn('action', 'approvals-request.payreqs.action')
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
            $realization = $document->payreq->realization;

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
                        'project' => $realization->project,
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
