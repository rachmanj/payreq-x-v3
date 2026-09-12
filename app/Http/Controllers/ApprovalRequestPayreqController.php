<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesApprovalActivityDetails;
use App\Models\ApprovalPlan;
use App\Models\Department;
use App\Models\Project;
use App\Models\RealizationDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApprovalRequestPayreqController extends Controller
{
    use HandlesApprovalActivityDetails;

    public function index()
    {
        $document_count = app(ToolController::class)->approval_documents_count();

        return view('approvals-request.payreqs.index', compact('document_count'));
    }

    public function show($id)
    {
        $document = ApprovalPlan::find($id);
        $payreq = $document->payreq;
        $payreq->load(['outgoings', 'transferDestinations.transferAccount.bank']);
        $realization = $payreq->realization->load('activity');
        $realization_details = $realization->realizationDetails()->with('activity')->get();
        $departments = Department::orderBy('department_name')->get();
        $projects = Project::where('is_active', 1)->orderBy('code')->get();
        $openActivities = $this->openActivitiesForProject($realization->project);
        $showActivityColumn = $payreq->type === 'reimburse';
        $activityLocked = ! $this->canUpdateActivityOnPlan($document);
        $activityModalOptions = auth()->user()->can('manage_activities')
            ? ApprovalActivityController::modalFormOptions()
            : [];

        return view('approvals-request.payreqs.show', compact([
            'document',
            'payreq',
            'realization',
            'realization_details',
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
        $approval_requests = ApprovalPlan::query()
            ->where('document_type', 'payreq')
            ->where('is_open', 1)
            ->where('status', 0)
            ->where('approver_id', auth()->user()->id)
            ->with([
                'payreq.requestor',
                'payreq.anggaran',
                'payreq.anggaranAllocations.anggaran',
                'payreq.realization.realizationDetails',
            ])
            ->get();

        return datatables()->of($approval_requests)
            ->addColumn('id', function ($approval_request) {
                return $approval_request->id;
            })
            ->addColumn('nomor', function ($approval_request) {
                return $approval_request->payreq->nomor;
            })
            ->addColumn('submit_at', function ($approval_request) {
                return $approval_request->payreq->submit_at->format('d-M-Y H:i:s');
            })
            ->addColumn('type', function ($approval_request) {
                $chipClass = match ($approval_request->payreq->type) {
                    'advance' => 'vj-chip-info',
                    'reimburse' => 'vj-chip-primary',
                    default => 'vj-chip-neutral',
                };

                return '<span class="vj-chip '.$chipClass.'">'.ucfirst($approval_request->payreq->type).'</span>';
            })
            ->addColumn('amount', function ($approval_request) {
                if ($approval_request->payreq->type == 'advance') {
                    return number_format($approval_request->payreq->amount, 2);
                } else {
                    return number_format($approval_request->payreq->realization->realizationDetails->sum('amount'), 2);
                }
            })
            ->addColumn('requestor', function ($approval_request) {
                return $approval_request->payreq->requestor->name;
            })
            ->addColumn('days', function ($approval_request) {
                return $approval_request->payreq->submit_at->diffInDays(now());
            })
            ->addColumn('remarks', function ($approval_request) {
                $remarks = $approval_request->payreq->remarks;

                return $remarks
                    ? '<span title="'.e($remarks).'">'.e(Str::limit($remarks, 60)).'</span>'
                    : '—';
            })
            ->addIndexColumn()
            ->addColumn('action', 'approvals-request.payreqs.action')
            ->rawColumns(['action', 'remarks', 'type'])
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
        $payreq = $document->payreq;

        if ($payreq->type === 'reimburse') {
            $this->assertActivityChangesAllowed($document, $request);

            if ($this->requestContainsActivityFields($request)) {
                $this->validateActivityFields($request);
            }
        }

        try {
            DB::beginTransaction();

            $realization = $payreq->realization;
            $canUpdateActivity = $payreq->type === 'reimburse' && $this->canUpdateActivityOnPlan($document);

            if ($request->has('deleted_ids') && ! empty($request->deleted_ids)) {
                RealizationDetail::whereIn('id', $request->deleted_ids)
                    ->where('realization_id', $realization->id)
                    ->delete();
            }

            foreach ($request->details as $detailData) {
                $activityFields = $payreq->type === 'reimburse'
                    ? $this->activityFieldsForDetailUpdate($detailData, $canUpdateActivity)
                    : [];

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

            if ($payreq->type === 'reimburse') {
                $this->updateRealizationHeaderActivity($realization, $request, $canUpdateActivity);
            }

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
