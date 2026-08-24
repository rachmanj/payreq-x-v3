<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiateUtilityApInvoicePreviewRequest;
use App\Http\Requests\SubmitUtilityApInvoiceRequest;
use App\Models\SapSubmissionLog;
use App\Models\UtilityApInvoice;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use App\Models\UtilityVendor;
use App\Services\SapService;
use App\Services\SapUtilityApInvoiceBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class UtilityApInvoiceController extends Controller
{
    public function initiatePreview(InitiateUtilityApInvoicePreviewRequest $request): RedirectResponse
    {
        $result = $this->loadEligibleSelection($request->validated()['bill_ids']);

        if ($result['error'] !== null) {
            return redirect()->route('utilities.bills.index')->with('error', $result['error']);
        }

        session(['utility_ap_invoice_bill_ids' => $result['bills']->pluck('id')->all()]);

        return redirect()->route('utilities.bills.ap-invoice.preview');
    }

    public function preview(): View|RedirectResponse
    {
        $billIds = session('utility_ap_invoice_bill_ids', []);
        $result = $this->loadEligibleSelection($billIds);

        if ($result['error'] !== null) {
            return redirect()->route('utilities.bills.index')->with('error', $result['error']);
        }

        $builder = new SapUtilityApInvoiceBuilder($result['bills'], $result['vendor']);
        $preview = $builder->getPreviewData();

        return view('utilities.bills.ap_invoice_preview', [
            'preview' => $preview,
            'billIds' => $result['bills']->pluck('id')->all(),
        ]);
    }

    public function submit(SubmitUtilityApInvoiceRequest $request, SapService $sapService): RedirectResponse
    {
        $validated = $request->validated();
        $result = $this->loadEligibleSelection($validated['bill_ids'], $validated['num_at_card']);

        if ($result['error'] !== null) {
            return redirect()->route('utilities.bills.ap-invoice.preview')->with('error', $result['error']);
        }

        $builder = $result['builder'];
        $bills = $result['bills'];
        $vendor = $result['vendor'];
        $payload = $builder->build();
        $preview = $builder->getPreviewData();

        $postedInvoice = null;
        $sapError = null;

        try {
            $postedInvoice = DB::transaction(function () use ($bills, $vendor, $payload, $preview, $sapService) {
                $invoice = UtilityApInvoice::create([
                    'jenis_utilitas' => $vendor->jenis_utilitas,
                    'sap_business_partner_id' => $vendor->sap_business_partner_id,
                    'num_at_card' => $preview['num_at_card'],
                    'tax_code' => $preview['tax_code'],
                    'periode_summary' => $preview['periode_summary'],
                    'total_amount' => $preview['total'],
                    'status' => UtilityApInvoice::STATUS_PENDING,
                    'submitted_by' => auth()->id(),
                ]);

                UtilityBill::query()->whereIn('id', $bills->pluck('id'))->update([
                    'utility_ap_invoice_id' => $invoice->id,
                ]);

                $sapResult = $sapService->createApInvoice($payload);

                if (! ($sapResult['success'] ?? false)) {
                    throw new \RuntimeException($sapResult['message'] ?? 'Gagal membuat AP Invoice di SAP B1.');
                }

                $invoice->update([
                    'status' => UtilityApInvoice::STATUS_POSTED,
                    'sap_doc_num' => $sapResult['doc_num'] ?? null,
                    'sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                    'submitted_at' => now(),
                ]);

                SapSubmissionLog::create([
                    'utility_ap_invoice_id' => $invoice->id,
                    'document_type' => 'ap_invoice_utility',
                    'status' => 'success',
                    'action' => 'submission',
                    'sap_doc_num' => $sapResult['doc_num'] ?? null,
                    'sap_doc_entry' => $sapResult['doc_entry'] ?? null,
                    'sap_response' => $sapResult['data'] ?? $sapResult,
                    'attempt_number' => 1,
                    'submitted_by' => auth()->id(),
                    'user_id' => auth()->id(),
                ]);

                return $invoice;
            });
        } catch (Throwable $exception) {
            $sapError = $exception->getMessage();

            SapSubmissionLog::create([
                'document_type' => 'ap_invoice_utility',
                'status' => 'failed',
                'action' => 'submission',
                'error_message' => $sapError,
                'sap_error' => $sapError,
                'attempt_number' => 1,
                'submitted_by' => auth()->id(),
                'user_id' => auth()->id(),
            ]);
        }

        if ($sapError !== null) {
            return redirect()
                ->route('utilities.bills.ap-invoice.preview')
                ->withInput()
                ->with('error', 'Gagal submit ke SAP B1: '.$sapError);
        }

        session()->forget('utility_ap_invoice_bill_ids');

        return redirect()
            ->route('utilities.ap-invoices.show', $postedInvoice)
            ->with('success', 'AP Invoice SAP berhasil dibuat. DocNum: '.($postedInvoice->sap_doc_num ?? '-'));
    }

    public function show(UtilityApInvoice $utilityApInvoice): View
    {
        $utilityApInvoice->load(['bills.customer.account', 'sapBusinessPartner', 'submittedBy']);

        return view('utilities.ap_invoices.show', [
            'invoice' => $utilityApInvoice,
            'jenisLabel' => UtilityCustomer::JENIS_UTILITAS[$utilityApInvoice->jenis_utilitas] ?? strtoupper($utilityApInvoice->jenis_utilitas),
        ]);
    }

    /**
     * @param  list<int|string>  $billIds
     * @return array{bills: Collection<int, UtilityBill>, vendor: UtilityVendor|null, builder: SapUtilityApInvoiceBuilder|null, error: string|null}
     */
    private function loadEligibleSelection(array $billIds, ?string $numAtCard = null): array
    {
        $bills = UtilityBill::query()
            ->with(['customer.account'])
            ->whereIn('id', $billIds)
            ->get();

        if ($bills->isEmpty()) {
            return [
                'bills' => $bills,
                'vendor' => null,
                'builder' => null,
                'error' => 'Tidak ada data preview. Silakan pilih tagihan terlebih dahulu.',
            ];
        }

        $jenis = $bills->first()?->customer?->jenis_utilitas;
        $vendor = $jenis
            ? UtilityVendor::query()->with('sapBusinessPartner')->where('jenis_utilitas', $jenis)->first()
            : null;

        if (! $vendor) {
            return [
                'bills' => $bills,
                'vendor' => null,
                'builder' => null,
                'error' => 'Vendor SAP untuk jenis utilitas ini belum di-mapping.',
            ];
        }

        $builder = new SapUtilityApInvoiceBuilder($bills, $vendor, $numAtCard);
        $errors = $builder->validate();

        if ($errors !== []) {
            return [
                'bills' => $bills,
                'vendor' => $vendor,
                'builder' => $builder,
                'error' => implode(' ', $errors),
            ];
        }

        return [
            'bills' => $bills,
            'vendor' => $vendor,
            'builder' => $builder,
            'error' => null,
        ];
    }
}
