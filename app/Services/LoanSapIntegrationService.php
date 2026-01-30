<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Bilyet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class LoanSapIntegrationService
{
    protected SapService $sapService;

    public function __construct(SapService $sapService)
    {
        $this->sapService = $sapService;
    }

    public function createApInvoiceForInstallment(int $installmentId): array
    {
        try {
            DB::beginTransaction();

            $installment = Installment::with(['loan.creditor'])->findOrFail($installmentId);

            // Validate prerequisites
            if (!$installment->canCreateSapApInvoice()) {
                throw new Exception('Installment cannot create AP Invoice. Prerequisites not met.');
            }

            // Build and validate payload
            $builder = new SapApInvoiceBuilder($installment);
            $errors = $builder->validate();

            if (!empty($errors)) {
                throw new Exception('Validation failed: ' . implode(', ', $errors));
            }

            $invoiceData = $builder->build();

            // Create AP Invoice in SAP B1
            $result = $this->sapService->createApInvoice($invoiceData);

            if (!($result['success'] ?? false)) {
                throw new Exception('Failed to create AP Invoice: ' . ($result['message'] ?? 'Unknown error'));
            }

            // Update installment with SAP document numbers
            $installment->sap_ap_doc_num = $result['doc_num'];
            $installment->sap_ap_doc_entry = $result['doc_entry'];
            $installment->sap_sync_status = 'ap_created';
            $installment->sap_error_message = null;
            $installment->save();

            DB::commit();

            Log::info('AP Invoice created for installment', [
                'installment_id' => $installmentId,
                'sap_doc_num' => $result['doc_num'],
                'sap_doc_entry' => $result['doc_entry'],
            ]);

            return [
                'success' => true,
                'doc_num' => $result['doc_num'],
                'doc_entry' => $result['doc_entry'],
                'message' => 'AP Invoice created successfully',
            ];
        } catch (Exception $e) {
            DB::rollBack();

            // Update installment with error
            $installment = Installment::find($installmentId);
            if ($installment) {
                $installment->sap_error_message = $e->getMessage();
                $installment->save();
            }

            Log::error('Failed to create AP Invoice for installment', [
                'installment_id' => $installmentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function createOutgoingPaymentForInstallment(int $installmentId, ?Bilyet $bilyet = null): array
    {
        try {
            DB::beginTransaction();

            $installment = Installment::with(['loan.creditor', 'bilyet.giro', 'account'])->findOrFail($installmentId);

            // Load bilyet if not provided and payment method is bilyet
            if (!$bilyet && $installment->payment_method === 'bilyet' && $installment->bilyet_id) {
                $bilyet = Bilyet::with('giro')->find($installment->bilyet_id);
            }

            // Validate prerequisites
            if (!$installment->canCreateSapPayment()) {
                throw new Exception('Installment cannot create Payment. Prerequisites not met.');
            }

            // Build and validate payload
            $builder = new SapOutgoingPaymentBuilder($installment, $bilyet);
            $errors = $builder->validate();

            if (!empty($errors)) {
                throw new Exception('Validation failed: ' . implode(', ', $errors));
            }

            $paymentData = $builder->build();

            // Create Outgoing Payment in SAP B1
            $result = $this->sapService->createOutgoingPayment($paymentData);

            if (!($result['success'] ?? false)) {
                throw new Exception('Failed to create Outgoing Payment: ' . ($result['message'] ?? 'Unknown error'));
            }

            // Update installment with SAP payment document numbers
            $installment->sap_payment_doc_num = $result['doc_num'];
            $installment->sap_payment_doc_entry = $result['doc_entry'];
            $installment->sap_sync_status = 'completed';
            $installment->sap_error_message = null;
            $installment->save();

            DB::commit();

            Log::info('Outgoing Payment created for installment', [
                'installment_id' => $installmentId,
                'sap_doc_num' => $result['doc_num'],
                'sap_doc_entry' => $result['doc_entry'],
            ]);

            return [
                'success' => true,
                'doc_num' => $result['doc_num'],
                'doc_entry' => $result['doc_entry'],
                'message' => 'Outgoing Payment created successfully',
            ];
        } catch (Exception $e) {
            DB::rollBack();

            // Update installment with error
            $installment = Installment::find($installmentId);
            if ($installment) {
                $installment->sap_error_message = $e->getMessage();
                $installment->save();
            }

            Log::error('Failed to create Outgoing Payment for installment', [
                'installment_id' => $installmentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function handleBilyetCair(Bilyet $bilyet): void
    {
        try {
            // Find linked installment
            $installment = Installment::with(['loan.creditor', 'bilyet.giro'])
                ->where('bilyet_id', $bilyet->id)
                ->where('payment_method', 'bilyet')
                ->first();

            if (!$installment) {
                Log::debug('No installment found for bilyet', ['bilyet_id' => $bilyet->id]);
                return;
            }

            // Ensure AP Invoice exists
            if (!$installment->sap_ap_doc_num) {
                Log::info('Creating AP Invoice for installment before payment', [
                    'installment_id' => $installment->id,
                ]);
                $this->createApInvoiceForInstallment($installment->id);
                $installment->refresh();
            }

            // Create Outgoing Payment if not already created
            if (!$installment->sap_payment_doc_num) {
                Log::info('Creating Outgoing Payment for installment', [
                    'installment_id' => $installment->id,
                ]);
                $this->createOutgoingPaymentForInstallment($installment->id, $bilyet);
            }
        } catch (Exception $e) {
            Log::error('Error handling bilyet cair', [
                'bilyet_id' => $bilyet->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - let the process continue
        }
    }

    public function handleInstallmentPaid(Installment $installment): void
    {
        try {
            // Only process auto-debit payments
            if ($installment->payment_method !== 'auto_debit') {
                return;
            }

            // Ensure AP Invoice exists
            if (!$installment->sap_ap_doc_num) {
                Log::info('Creating AP Invoice for auto-debit installment', [
                    'installment_id' => $installment->id,
                ]);
                $this->createApInvoiceForInstallment($installment->id);
                $installment->refresh();
            }

            // Create Outgoing Payment if not already created
            if (!$installment->sap_payment_doc_num) {
                Log::info('Creating Outgoing Payment for auto-debit installment', [
                    'installment_id' => $installment->id,
                ]);
                $this->createOutgoingPaymentForInstallment($installment->id);
            }
        } catch (Exception $e) {
            Log::error('Error handling installment paid', [
                'installment_id' => $installment->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - let the process continue
        }
    }
}
