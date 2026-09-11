<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesApprovalActivityDetails;
use App\Models\ApprovalPlan;
use App\Models\Department;
use App\Models\Project;
use App\Models\RealizationDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalRequestRealizationController extends Controller
{
    use HandlesApprovalActivityDetails;

    public function index()
    {
        $document_count = app(ToolController::class)->approval_documents_count();

        return view('approvals-request.realizations.index', compact('document_count'));
    }

    public function show($id)
    {
        $document = ApprovalPlan::find($id);
        $realization = $document->realization->load('activity');
        $document_details = $realization->realizationDetails()->with('activity')->get();
        $payreq = $realization->payreq;
        $payreq->load('outgoings');
        $departments = Department::orderBy('department_name')->get();
        $projects = Project::where('is_active', 1)->orderBy('code')->get();
        $openActivities = $this->openActivitiesForProject($realization->project);
        $showActivityColumn = true;
        $activityLocked = ! $this->canUpdateActivityOnPlan($document);
        $activityModalOptions = auth()->user()->can('manage_activities')
            ? ApprovalActivityController::modalFormOptions()
            : [];

        return view('approvals-request.realizations.show', compact([
            'document',
            'document_details',
            'payreq',
            'realization',
            'departments',
            'projects',
            'openActivities',
            'showActivityColumn',
            'activityLocked',
            'activityModalOptions',
        ]));
    }

    public function data()
    {
        $approval_requests = ApprovalPlan::pendingRealizationApprovals(auth()->user()->id)
            ->with(['realization.payreq.requestor', 'realization.realizationDetails'])
            ->get();

        return datatables()->of($approval_requests)
            ->addColumn('id', function ($approval_request) {
                return $approval_request->id;
            })
            ->addColumn('nomor', function ($approval_request) {
                return $approval_request->realization?->nomor ?? '-';
            })
            ->addColumn('payreq_no', function ($approval_request) {
                return $approval_request->realization?->payreq?->nomor ?? '-';
            })
            ->addColumn('submit_at', function ($approval_request) {
                $submitAt = $approval_request->realization?->submit_at;

                return $submitAt
                    ? $submitAt->format('d-M-Y H:i:s').' wita'
                    : '-';
            })
            ->addColumn('amount', function ($approval_request) {
                $details = $approval_request->realization?->realizationDetails;

                return number_format($details?->sum('amount') ?? 0, 2);
            })
            ->addColumn('requestor', function ($approval_request) {
                return $approval_request->realization?->requestor?->name ?? '-';
            })
            ->addColumn('days', function ($approval_request) {
                $submitAt = $approval_request->realization?->submit_at;

                return $submitAt ? $submitAt->diffInDays(now()) : '-';
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

        $document = ApprovalPlan::findOrFail($id);
        $this->assertActivityChangesAllowed($document, $request);

        if ($this->requestContainsActivityFields($request)) {
            $this->validateActivityFields($request);
        }

        try {
            DB::beginTransaction();

            $realization = $document->realization;
            $canUpdateActivity = $this->canUpdateActivityOnPlan($document);

            if ($request->has('deleted_ids') && ! empty($request->deleted_ids)) {
                RealizationDetail::whereIn('id', $request->deleted_ids)
                    ->where('realization_id', $realization->id)
                    ->delete();
            }

            foreach ($request->details as $detailData) {
                $activityFields = $this->activityFieldsForDetailUpdate($detailData, $canUpdateActivity);

                if (isset($detailData['id']) && $detailData['id']) {
                    $detail = RealizationDetail::where('id', $detailData['id'])
                        ->where('realization_id', $realization->id)
                        ->first();

                    if ($detail) {
                        $detail->update(array_merge([
                            'description' => $detailData['description'],
                            'amount' => $detailData['amount'],
                            'department_id' => $detailData['department_id'] ?? null,
                            'project' => $detailData['project'] ?? null,
                            'unit_no' => $detailData['unit_no'] ?? null,
                            'type' => $detailData['type'] ?? null,
                            'qty' => $detailData['qty'] ?? null,
                            'uom' => $detailData['uom'] ?? null,
                            'km_position' => $detailData['km_position'] ?? null,
                        ], $activityFields));
                    }
                } else {
                    RealizationDetail::create(array_merge([
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
                    ], $activityFields));
                }
            }

            $this->updateRealizationHeaderActivity($realization, $request, $canUpdateActivity);

            $realization->update([
                'modified_by_approver' => true,
                'modified_by_approver_at' => now(),
                'modified_by_approver_id' => auth()->id(),
            ]);

            DB::commit();

            $updatedDetails = $realization->fresh()->realizationDetails()->with('activity')->get();

            return response()->json([
                'success' => true,
                'message' => 'Realization details updated successfully. Document needs to be reprinted.',
                'details' => $updatedDetails,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update details: '.$e->getMessage(),
            ], 500);
        }
    }
}
