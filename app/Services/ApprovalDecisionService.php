<?php

namespace App\Services;

use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\UserRealizationController;
use App\Models\Anggaran;
use App\Models\ApprovalPlan;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\User;
use App\Models\UtilityBill;
use Carbon\Carbon;
use InvalidArgumentException;

class ApprovalDecisionService
{
    /**
     * @param  array<string, mixed>|null  $rabFields
     * @return array{
     *     document_type: string,
     *     document_id: int,
     *     document_status: string|null,
     *     plan_status: int,
     *     approved_count: int,
     *     revised_count: int,
     *     rejected_count: int,
     *     total_open_plans: int,
     *     status_text: string,
     *     message: string
     * }
     */
    public function decide(
        ApprovalPlan $plan,
        int $status,
        ?string $remarks,
        User $actor,
        ?array $rabFields = null
    ): array {
        $plan->update([
            'status' => $status,
            'remarks' => $remarks,
            'is_read' => $remarks ? 0 : 1,
        ]);

        $documentType = $plan->document_type;
        $document = $this->resolveDocument($documentType, (int) $plan->document_id);

        $approvalPlans = ApprovalPlan::where('document_id', $document->id)
            ->where('document_type', $documentType)
            ->where('is_open', 1)
            ->get();

        $rejectedCount = 0;
        $revisedCount = 0;
        $approvedCount = 0;

        foreach ($approvalPlans as $approvalPlan) {
            if ($approvalPlan->status == 3) {
                $rejectedCount++;
            }
            if ($approvalPlan->status == 2) {
                $revisedCount++;
            }
            if ($approvalPlan->status == 1) {
                $approvedCount++;
            }
        }

        if ($revisedCount > 0) {
            $document->update([
                'status' => 'revise',
                'editable' => 1,
                'deletable' => 1,
            ]);

            $paymentRequest = Payreq::where('id', $document->payreq_id)->first();
            if ($paymentRequest) {
                $paymentRequest->update([
                    'status' => 'paid',
                ]);
            }

            $this->closeOpenApprovalPlans($documentType, (int) $document->id);
        }

        if ($rejectedCount > 0) {
            $document->update([
                'status' => 'rejected',
                'deletable' => 1,
            ]);

            if ($documentType === 'payreq') {
                UtilityBill::where('payreq_id', $document->id)->update(['payreq_id' => null]);
            }

            $paymentRequest = Payreq::where('id', $document->payreq_id)->first();
            if ($paymentRequest) {
                $paymentRequest->update([
                    'status' => 'paid',
                ]);
            }

            $this->closeOpenApprovalPlans($documentType, (int) $document->id);
        }

        if ($approvedCount === $approvalPlans->count()) {
            $updateData = [
                'status' => 'approved',
                'printable' => 1,
                'editable' => 0,
                'approved_at' => $approvalPlan->updated_at,
            ];

            if ($documentType === 'payreq' && $document->type !== 'reimburse') {
                $updateData['draft_no'] = $document->nomor;
                $updateData['nomor'] = app(DocumentNumberController::class)->generate_document_number($documentType, $actor->project);
            }

            $document->update($updateData);

            $paymentRequest = Payreq::where('id', $document->payreq_id)->first();
            if ($paymentRequest) {
                $paymentRequest->update([
                    'status' => 'realization',
                ]);
            }

            if ($documentType === 'payreq' && $document->type === 'reimburse') {
                $realization = Realization::where('payreq_id', $document->id)->first();
                if ($realization) {
                    $realization->update([
                        'status' => 'reimburse-approved',
                        'approved_at' => $approvalPlan->updated_at,
                    ]);
                }
            }

            if ($documentType === 'realization') {
                $realization = Realization::findOrFail($document->id);
                $realization->update([
                    'due_date' => Carbon::now()->addDays(3),
                ]);

                app(UserRealizationController::class)->check_realization_amount($document->id);
            }

            if ($documentType === 'rab') {
                $document->update([
                    'periode_ofr' => $rabFields['periode_ofr'] ?? null,
                    'usage' => $rabFields['usage'] ?? null,
                    'periode_anggaran' => $rabFields['periode_anggaran'] ?? null,
                ]);
            }
        }

        $statusText = match ($status) {
            1 => 'approved',
            2 => 'sent back for revision',
            3 => 'rejected',
            default => 'updated',
        };

        $document->refresh();

        return [
            'document_type' => $documentType,
            'document_id' => (int) $document->id,
            'document_status' => $document->status,
            'plan_status' => $status,
            'approved_count' => $approvedCount,
            'revised_count' => $revisedCount,
            'rejected_count' => $rejectedCount,
            'total_open_plans' => $approvalPlans->count(),
            'status_text' => $statusText,
            'message' => ucfirst($documentType).' has been '.$statusText,
        ];
    }

    public function closeOpenApprovalPlans(string $documentType, int $documentId): void
    {
        $approvalPlans = ApprovalPlan::where('document_id', $documentId)
            ->where('document_type', $documentType)
            ->where('is_open', 1)
            ->get();

        foreach ($approvalPlans as $approvalPlan) {
            $approvalPlan->update(['is_open' => 0]);
        }
    }

    private function resolveDocument(string $documentType, int $documentId): Payreq|Realization|Anggaran
    {
        if ($documentType === 'payreq') {
            $document = Payreq::where('id', $documentId)->first();
        } elseif ($documentType === 'realization') {
            $document = Realization::findOrFail($documentId);
        } elseif ($documentType === 'rab') {
            $document = Anggaran::findOrFail($documentId);
        } else {
            throw new InvalidArgumentException('Invalid document type: '.$documentType);
        }

        if ($document === null) {
            throw new InvalidArgumentException('Document not found for type '.$documentType.' id '.$documentId);
        }

        return $document;
    }
}
