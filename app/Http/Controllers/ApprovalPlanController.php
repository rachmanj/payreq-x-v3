<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\ApprovalPlan;
use App\Models\ApprovalStage;
use App\Models\Payreq;
use App\Models\Realization;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * ApprovalPlanController
 * 
 * This controller handles the approval workflow for various document types
 * including payment requests (payreq), realizations, and budget plans (rab).
 */
class ApprovalPlanController extends Controller
{
    /**
     * Create approval plans for a document
     * 
     * This function creates approval plans for a specific document based on its type.
     * It identifies the appropriate approvers from the ApprovalStage model and
     * creates an approval plan entry for each approver.
     * 
     * @param string $document_type Type of document ('payreq', 'realization', 'rab')
     * @param int $document_id ID of the document
     * @return int|bool Number of approvers created or false if failed
     */
    public function create_approval_plan($document_type, $document_id)
    {
        // Retrieve the document based on its type
        if ($document_type == 'payreq') {
            $document = Payreq::findOrFail($document_id);
        } elseif ($document_type == 'realization') {
            $document = Realization::findOrFail($document_id);
        } elseif ($document_type == 'rab') {
            $document = Anggaran::findOrFail($document_id);
        } else {
            return false; // Invalid document type
        }

        // Get all approvers for this document type, project and department
        $approvers = ApprovalStage::where('project', $document->project)
            ->where('department_id', $document->department_id)
            ->where('document_type', $document_type)
            ->get();

        // If approvers exist, create approval plans
        if ($approvers->count() > 0) {
            // Create an approval plan for each approver
            foreach ($approvers as $approver) {
                ApprovalPlan::create([
                    'document_id' => $document_id,
                    'document_type' => $document_type,
                    'approver_id' => $approver->approver_id,
                ]);
            }

            // Update document to mark it as submitted and no longer editable
            $document->submit_at = Carbon::now();
            $document->editable = 0;
            $document->deletable = 0;
            $document->save();

            return $approvers->count(); // Return number of approvers
        }

        // Return false if no approvers found
        return false;
    }

    /**
     * Update approval decision
     * 
     * This function processes an approval decision (approve, revise, reject)
     * and updates both the approval plan and the associated document.
     * 
     * Approval status codes:
     * 0 = Pending
     * 1 = Approved
     * 2 = Revise
     * 3 = Reject
     * 4 = Canceled
     * 
     * @param Request $request The HTTP request containing approval data
     * @param int $id The ID of the approval plan to update
     * @return \Illuminate\Http\RedirectResponse Redirect to appropriate page
     */
    public function update(Request $request, $id)
    {
        // Find and update the approval plan with the decision
        $approval_plan = ApprovalPlan::findOrFail($id);
        $approval_plan->update([
            'status' => $request->status,
            'remarks' => $request->remarks,
            'is_read' => $request->remarks ? 0 : 1, // Mark as unread if there are remarks
        ]);

        // Get document type and retrieve the associated document
        $document_type = $approval_plan->document_type;

        if ($document_type == 'payreq') {
            $document = Payreq::where('id', $approval_plan->document_id)->first();
        } elseif ($document_type == 'realization') {
            $document = Realization::findOrFail($approval_plan->document_id);
        } elseif ($document_type == 'rab') {
            $document = Anggaran::findOrFail($approval_plan->document_id);
        } else {
            return false; // Invalid document type
        }

        // Get all active approval plans for this document
        $approval_plans = ApprovalPlan::where('document_id', $document->id)
            ->where('document_type', $document_type)
            ->where('is_open', 1)
            ->get();

        // Count different approval decisions
        $rejected_count = 0;
        $revised_count = 0;
        $approved_count = 0;

        foreach ($approval_plans as $approval_plan) {
            if ($approval_plan->status == 3) { // Rejected
                $rejected_count++;
            }
            if ($approval_plan->status == 2) { // Revised
                $revised_count++;
            }
            if ($approval_plan->status == 1) { // Approved
                $approved_count++;
            }
        }

        // Handle document revision request
        if ($revised_count > 0) {
            $document->update([
                'status' => 'revise',
                'editable' => 1,
                'deletable' => 1,
            ]);

            // Close all open approval plans for this document
            $this->closeOpenApprovalPlans($document_type, $document->id);
        }

        // Handle document rejection
        if ($rejected_count > 0) {
            $document->update([
                'status' => 'rejected',
                'deletable' => 1,
            ]);

            // Close all open approval plans for this document
            $this->closeOpenApprovalPlans($document_type, $document->id);
        }

        // Handle document approval (when all approvers have approved)
        if ($approved_count === $approval_plans->count()) {
            // Update document status to approved and generate official document number
            $document->update([
                'status' => 'approved',
                'draft_no' => $document->nomor,
                'printable' => 1,
                'editable' => 0,
                'approved_at' => $approval_plan->updated_at,
                'nomor' => app(DocumentNumberController::class)->generate_document_number($document_type, auth()->user()->project),
            ]);

            // Special handling for reimbursement type payment requests
            if ($request->document_type === 'payreq') {
                if ($document->type === 'reimburse') {
                    $realization = Realization::where('payreq_id', $document->id)->first();
                    $realization->update([
                        'status' => 'reimburse-approved',
                        'approved_at' => $approval_plan->updated_at,
                        'nomor' => app(DocumentNumberController::class)->generate_document_number('realization', auth()->user()->project),
                    ]);
                }
            }

            // Special handling for realization documents
            if ($request->document_type === 'realization') {
                // Set due date for realization (3 days from now)
                $realization = Realization::findOrFail($document->id);
                $realization->update([
                    'due_date' => Carbon::now()->addDays(3),
                ]);

                // Check variance between payment request and realization amounts
                app(UserRealizationController::class)->check_realization_amount($document->id);
            }

            // Special handling for budget (RAB) documents
            if ($request->document_type === 'rab') {
                $document->update([
                    'periode_ofr' => $request->periode_ofr,
                    'usage' => $request->usage,
                    'periode_anggaran' => $request->periode_anggaran,
                ]);
            }
        }

        // Determine the appropriate success message based on the approval status
        $status_text = '';
        if ($request->status == 1) {
            $status_text = 'approved';
        } elseif ($request->status == 2) {
            $status_text = 'sent back for revision';
        } elseif ($request->status == 3) {
            $status_text = 'rejected';
        } else {
            $status_text = 'updated';
        }

        // Check if the request is AJAX
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => ucfirst($document_type) . ' has been ' . $status_text,
                'document_type' => $document_type
            ]);
        }

        // Redirect to appropriate page based on document type for non-AJAX requests
        if ($request->document_type === 'payreq') {
            return redirect()->route('approvals.request.payreqs.index')->with('success', 'Payment Request has been ' . $status_text);
        } elseif ($request->document_type === 'realization') {
            return redirect()->route('approvals.request.realizations.index')->with('success', 'Realization has been ' . $status_text);
        } elseif ($request->document_type === 'rab') {
            return redirect()->route('approvals.request.anggarans.index')->with('success', 'Budget (RAB) has been ' . $status_text);
        } else {
            return false; // Invalid document type
        }
    }

    /**
     * Get approval status descriptions
     * 
     * Returns an array mapping status codes to their text descriptions
     * 
     * @return array Array of approval status descriptions
     */
    public function approvalStatus()
    {
        return [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Revised',
            3 => 'Rejected',
            4 => 'Canceled',
        ];
    }

    /**
     * Close all open approval plans for a document
     * 
     * This function is called when a document is rejected or needs revision.
     * It marks all open approval plans for the document as closed (is_open = 0).
     * 
     * @param string $document_type Type of document
     * @param int $document_id ID of the document
     * @return void
     */
    public function closeOpenApprovalPlans($document_type, $document_id)
    {
        // Find all open approval plans for this document
        $approval_plans = ApprovalPlan::where('document_id', $document_id)
            ->where('document_type', $document_type)
            ->where('is_open', 1)
            ->get();

        // Close all open approval plans
        if ($approval_plans->count() > 0) {
            foreach ($approval_plans as $approval_plan) {
                $approval_plan->update(['is_open' => 0]);
            }
        }
    }

    /**
     * @deprecated Use closeOpenApprovalPlans() instead
     */
    public function cekExistingAndDisableOpen($document_type, $document_id)
    {
        return $this->closeOpenApprovalPlans($document_type, $document_id);
    }

    /**
     * Bulk approve multiple documents
     * 
     * This method allows approving multiple documents at once.
     * 
     * @param Request $request The HTTP request containing the IDs of documents to approve
     * @return \Illuminate\Http\JsonResponse JSON response with success/error message
     */
    public function bulkApprove(Request $request)
    {
        // Validate request
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
            'document_type' => 'required|string|in:payreq,realization,rab',
            'remarks' => 'nullable|string',
        ]);

        $successCount = 0;
        $failCount = 0;
        $document_type = $request->document_type;

        // Process each approval plan
        foreach ($request->ids as $id) {
            $approval_plan = ApprovalPlan::findOrFail($id);

            // Skip if not the correct document type or already processed
            if ($approval_plan->document_type !== $document_type || $approval_plan->status !== 0 || $approval_plan->is_open !== 1) {
                $failCount++;
                continue;
            }

            // Update the approval plan
            $approval_plan->update([
                'status' => 1, // Approved
                'remarks' => $request->remarks,
                'is_read' => $request->remarks ? 0 : 1,
            ]);

            // Get the document
            if ($document_type == 'payreq') {
                $document = Payreq::where('id', $approval_plan->document_id)->first();
            } elseif ($document_type == 'realization') {
                $document = Realization::findOrFail($approval_plan->document_id);
            } elseif ($document_type == 'rab') {
                $document = Anggaran::findOrFail($approval_plan->document_id);
            } else {
                $failCount++;
                continue;
            }

            // Get all active approval plans for this document
            $approval_plans = ApprovalPlan::where('document_id', $document->id)
                ->where('document_type', $document_type)
                ->where('is_open', 1)
                ->get();

            // Count approved plans
            $approved_count = $approval_plans->where('status', 1)->count();

            // Check if all approvers have approved
            if ($approved_count === $approval_plans->count()) {
                // Update document status to approved and generate official document number
                $document->update([
                    'status' => 'approved',
                    'draft_no' => $document->nomor,
                    'printable' => 1,
                    'editable' => 0,
                    'approved_at' => now(),
                    'nomor' => app(DocumentNumberController::class)->generate_document_number($document_type, auth()->user()->project),
                ]);

                // Special handling for reimbursement type payment requests
                if ($document_type === 'payreq') {
                    if ($document->type === 'reimburse') {
                        $realization = Realization::where('payreq_id', $document->id)->first();
                        $realization->update([
                            'status' => 'reimburse-approved',
                            'approved_at' => now(),
                            'nomor' => app(DocumentNumberController::class)->generate_document_number('realization', auth()->user()->project),
                        ]);
                    }
                }

                // Special handling for realization documents
                if ($document_type === 'realization') {
                    // Set due date for realization (3 days from now)
                    $realization = Realization::findOrFail($document->id);
                    $realization->update([
                        'due_date' => Carbon::now()->addDays(3),
                    ]);

                    // Check variance between payment request and realization amounts
                    app(UserRealizationController::class)->check_realization_amount($document->id);
                }

                // Special handling for budget (RAB) documents
                if ($document_type === 'rab') {
                    $document->update([
                        'periode_ofr' => $request->periode_ofr,
                        'usage' => $request->usage,
                        'periode_anggaran' => $request->periode_anggaran,
                    ]);
                }
            }

            $successCount++;
        }

        // Return response
        if ($successCount > 0) {
            $documentTypeLabel = ucfirst($document_type);
            if ($document_type === 'payreq') {
                $documentTypeLabel = 'Payment Request';
            } elseif ($document_type === 'rab') {
                $documentTypeLabel = 'Budget (RAB)';
            }

            return response()->json([
                'success' => true,
                'message' => $successCount . ' ' . $documentTypeLabel . ($successCount > 1 ? 's' : '') . ' have been approved successfully' . ($failCount > 0 ? ' (' . $failCount . ' failed)' : ''),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve any documents',
            ], 422);
        }
    }
}
