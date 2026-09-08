<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Installment;
use App\Models\Loan;
use App\Services\InstallmentOpService;
use App\Services\InstallmentPaymentService;
use App\Services\InstallmentSapSubmissionService;
use App\Services\LoanSapIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstallmentController extends Controller
{
    protected $installmentPaymentService;

    public function __construct(InstallmentPaymentService $installmentPaymentService)
    {
        $this->installmentPaymentService = $installmentPaymentService;
    }

    public function generate($loan_id)
    {
        $loan = Loan::find($loan_id);
        $accounts = Account::where('type', 'bank')->get();

        return view('accounting.loans.installments.generate', compact(['loan', 'accounts']));
    }

    public function store_generate(Request $request)
    {
        $loan_id = $request->loan_id;
        $first_installment_date = $request->start_due_date;
        $tenor = $request->tenor;
        $installment_amount = $request->installment_amount;
        $start_angsuran_ke = $request->start_angsuran_ke;
        $batch = Installment::max('batch') + 1;

        $first_installment_date = new \DateTime($first_installment_date);
        for ($i = 1; $i <= $tenor; $i++) {
            $installment = new Installment;
            $installment->loan_id = $loan_id;
            $installment->due_date = $first_installment_date->format('Y-m-d');
            $installment->bilyet_amount = $installment_amount;
            $installment->angsuran_ke = $start_angsuran_ke;
            $installment->created_by = auth()->user()->id;
            $installment->batch = $batch;
            $installment->account_id = $request->account_id;
            $installment->save();

            $first_installment_date->add(new \DateInterval('P1M'));
            $start_angsuran_ke++;
        }

        return redirect()->route('accounting.loans.show', $loan_id)->with('success', 'Angsuran berhasil di-generate');
    }

    public function update(Request $request)
    {
        $installment = Installment::find($request->installment_id);

        $status = $request->paid_date ? 'paid' : null;

        $installment->due_date = $request->due_date;
        $installment->paid_date = $request->paid_date;
        $installment->bilyet_no = $request->bilyet_no;
        $installment->bilyet_amount = $request->bilyet_amount;
        $installment->account_id = $request->account_id;
        $installment->status = $status;
        $installment->save();

        return redirect()->route('accounting.loans.show', $installment->loan_id)->with('success', 'Angsuran berhasil di-update');
    }

    public function destroy($id)
    {
        $installment = Installment::find($id);
        $loan_id = $installment->loan_id;
        $installment->delete();

        return redirect()->route('accounting.loans.show', $loan_id)->with('success', 'Angsuran berhasil dihapus');
    }

    public function data($loan_id)
    {
        $instalments = Installment::where('loan_id', $loan_id)->orderBy('due_date', 'asc')->get();

        return datatables()->of($instalments)
            ->editColumn('due_date', function ($instalment) {
                return \Carbon\Carbon::parse($instalment->due_date)->format('d-M-Y');
            })
            ->editColumn('paid_date', function ($instalment) {
                return $instalment->paid_date ? \Carbon\Carbon::parse($instalment->paid_date)->format('d-M-Y') : '';
            })
            ->editColumn('bilyet_amount', function ($instalment) {
                return number_format($instalment->bilyet_amount, 2, ',', '.');
            })
            ->addColumn('principal_amount', function ($instalment) {
                if ($instalment->principal_amount === null) {
                    return '<span class="text-muted">—</span> <span class="badge badge-warning">isi split</span>';
                }

                return number_format((float) $instalment->principal_amount, 0, ',', '.');
            })
            ->addColumn('interest_amount', function ($instalment) {
                if ($instalment->interest_amount === null) {
                    return '<span class="text-muted">—</span>';
                }

                return number_format((float) $instalment->interest_amount, 0, ',', '.');
            })
            ->addColumn('paid_status', function ($instalment) {
                if ($instalment->isPaid()) {
                    return '<span class="badge badge-success">Paid</span>';
                }

                return '<span class="badge badge-secondary">Open</span>';
            })
            ->addColumn('sap_ap_badge', function ($instalment) {
                if ($instalment->sap_ap_doc_num) {
                    return '<span class="badge badge-info">AP '.$instalment->sap_ap_doc_num.'</span>';
                }

                return '<span class="badge badge-light">Belum AP</span>';
            })
            ->addColumn('created_by', function ($instalment) {
                return $instalment->user->name;
            })
            ->addColumn('account', function ($instalment) {
                return $instalment->account->account_number;
            })
            ->addColumn('payment_method', function ($instalment) {
                return $instalment->payment_method_label;
            })
            ->addColumn('sap_status', function ($instalment) {
                $badges = [
                    'pending' => '<span class="badge badge-secondary">Pending</span>',
                    'ap_created' => '<span class="badge badge-info">AP Created</span>',
                    'payment_created' => '<span class="badge badge-warning">Payment Created</span>',
                    'completed' => '<span class="badge badge-success">Completed</span>',
                ];
                $status = $instalment->sap_sync_status ?? 'pending';

                return $badges[$status] ?? '<span class="badge badge-secondary">'.ucfirst($status).'</span>';
            })
            ->addColumn('sap_documents', function ($instalment) {
                $html = '';
                if ($instalment->sap_ap_doc_num) {
                    $html .= '<small class="text-muted">AP: '.$instalment->sap_ap_doc_num.'</small><br>';
                }
                if ($instalment->sap_payment_doc_num) {
                    $html .= '<small class="text-muted">Payment: '.$instalment->sap_payment_doc_num.'</small>';
                }
                if (empty($html)) {
                    $html = '<small class="text-muted">-</small>';
                }

                return $html;
            })
            ->addIndexColumn()
            ->addColumn('select', function ($instalment) {
                if ($instalment->isPaid() || $instalment->hasSapAp() || $instalment->principal_amount === null) {
                    return '';
                }

                return '<input type="checkbox" class="installment-bulk-check" value="'.$instalment->id.'">';
            })
            ->addColumn('action', 'accounting.loans.partials.installment_action')
            ->rawColumns(['principal_amount', 'interest_amount', 'paid_status', 'sap_ap_badge', 'sap_status', 'sap_documents', 'select', 'action'])
            ->toJson();
    }

    public function createBilyetForPayment(Request $request, $installment_id)
    {
        try {
            $bilyetData = $request->validate([
                'giro_id' => 'required|exists:giros,id',
                'prefix' => 'nullable|string|max:10',
                'nomor' => 'required|string|max:30',
                'type' => 'required|in:cek,bg,loa',
                'bilyet_date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'remarks' => 'nullable|string',
            ]);

            $bilyetData['project'] = auth()->user()->project;

            $bilyet = $this->installmentPaymentService->createBilyetAndPay($installment_id, $bilyetData);

            return redirect()->back()->with('success', 'Bilyet created and linked to installment successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create bilyet: '.$e->getMessage());
        }
    }

    public function linkExistingBilyet(Request $request, $installment_id)
    {
        try {
            $request->validate([
                'bilyet_id' => 'required|exists:bilyets,id',
            ]);

            $loanPaymentService = app(\App\Services\LoanPaymentService::class);
            $loanPaymentService->linkExistingBilyetToInstallment($request->bilyet_id, $installment_id);

            return redirect()->back()->with('success', 'Existing bilyet linked to installment successfully');
        } catch (\Exception $e) {
            Log::error('Failed to link existing bilyet', [
                'installment_id' => $installment_id,
                'bilyet_id' => $request->bilyet_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to link bilyet: '.$e->getMessage());
        }
    }

    public function markAsAutoDebitPaid(Request $request, $installment_id)
    {
        try {
            $request->validate([
                'paid_date' => 'nullable|date',
                'account_id' => 'required|exists:accounts,id',
            ]);

            $this->installmentPaymentService->markAsAutoDebitPaid($installment_id, $request->paid_date, $request->account_id);

            return redirect()->back()->with('success', 'Installment marked as paid via auto-debit');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to mark as paid: '.$e->getMessage());
        }
    }

    public function createSapApInvoice(Request $request, $installment_id)
    {
        try {
            $installment = Installment::findOrFail($installment_id);

            // Validate prerequisites
            if ($installment->paid_date) {
                return redirect()->back()->with('error', 'Cannot create AP Invoice for paid installment');
            }

            if (! in_array($installment->payment_method, ['bilyet', 'auto_debit'])) {
                return redirect()->back()->with('error', 'AP Invoice can only be created for bilyet or auto-debit payment methods');
            }

            if ($installment->sap_ap_doc_num) {
                return redirect()->back()->with('error', 'AP Invoice already exists for this installment');
            }

            $integrationService = app(LoanSapIntegrationService::class);
            $result = $integrationService->createApInvoiceForInstallment($installment_id);

            return redirect()->back()->with('success', 'SAP AP Invoice created successfully. DocNum: '.$result['doc_num']);
        } catch (\Exception $e) {
            Log::error('Failed to create SAP AP Invoice', [
                'installment_id' => $installment_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to create SAP AP Invoice: '.$e->getMessage());
        }
    }

    public function linkSapApInvoice(Request $request, $installment_id)
    {
        try {
            $request->validate([
                'sap_ap_doc_num' => 'required|string',
                'sap_ap_doc_entry' => 'required|integer',
            ]);

            $installment = Installment::findOrFail($installment_id);

            // Allow linking even if AP Invoice already exists (to update it)
            $installment->sap_ap_doc_num = $request->sap_ap_doc_num;
            $installment->sap_ap_doc_entry = $request->sap_ap_doc_entry;

            // Update sync status
            if ($installment->sap_payment_doc_num) {
                $installment->sap_sync_status = 'completed';
            } else {
                $installment->sap_sync_status = 'ap_created';
            }

            $installment->sap_error_message = null;
            $installment->save();

            return redirect()->back()->with('success', 'SAP AP Invoice linked successfully. DocNum: '.$request->sap_ap_doc_num);
        } catch (\Exception $e) {
            Log::error('Failed to link SAP AP Invoice', [
                'installment_id' => $installment_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to link SAP AP Invoice: '.$e->getMessage());
        }
    }

    public function submitSapAp(Request $request, int $installment, InstallmentSapSubmissionService $service): JsonResponse
    {
        $result = $service->submitAp($installment, $request->user());

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function bulkSubmitSapAp(Request $request, InstallmentSapSubmissionService $service): JsonResponse
    {
        $validated = $request->validate([
            'installment_ids' => 'required|array|min:1',
            'installment_ids.*' => 'integer|exists:installments,id',
        ]);

        $result = $service->submitApBulk($validated['installment_ids'], $request->user());

        return response()->json($result);
    }

    public function createSapOp(Request $request, int $installment, InstallmentOpService $service): JsonResponse
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'prepared_by' => 'nullable|string|max:100',
            'approved_by' => 'nullable|string|max:100',
            'payment_means' => 'nullable|string|max:50',
            'confirm_bilyet_cair' => 'nullable|boolean',
            'confirm_auto_debit' => 'nullable|boolean',
        ]);

        $result = $service->createOp($installment, $validated, $request->user());

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function opPreview(Request $request, int $installment): JsonResponse
    {
        $installmentModel = Installment::with(['loan.creditor.sapBusinessPartner', 'bilyet', 'account', 'loan.account'])
            ->findOrFail($installment);

        $bankAccount = $installmentModel->account_id
            ? $installmentModel->account
            : ($installmentModel->loan?->account);

        return response()->json([
            'success' => true,
            'data' => [
                'installment_id' => $installmentModel->id,
                'angsuran_ke' => $installmentModel->angsuran_ke,
                'vendor' => $installmentModel->loan?->creditor?->name,
                'vendor_code' => $installmentModel->loan?->creditor?->sapBusinessPartner?->code
                    ?? $installmentModel->loan?->creditor?->sap_code,
                'sap_ap_doc_num' => $installmentModel->sap_ap_doc_num,
                'sap_ap_doc_entry' => $installmentModel->sap_ap_doc_entry,
                'amount' => (float) ($installmentModel->bilyet_amount ?? 0),
                'amount_formatted' => number_format((float) ($installmentModel->bilyet_amount ?? 0), 0, ',', '.'),
                'bank_account_id' => $bankAccount?->id,
                'bank_account_label' => $bankAccount
                    ? $bankAccount->account_number.' — '.($bankAccount->account_name ?? '')
                    : null,
                'payment_method' => $installmentModel->payment_method,
                'payment_method_label' => $installmentModel->payment_method_label,
                'bilyet_status' => $installmentModel->bilyet?->status,
                'bilyet_no' => $installmentModel->bilyet_no ?? $installmentModel->bilyet?->full_nomor,
                'default_payment_date' => now()->format('Y-m-d'),
            ],
        ]);
    }

    public function saveSplit(Request $request, int $installment): JsonResponse
    {
        $validated = $request->validate([
            'principal_amount' => 'required|numeric|min:0',
            'interest_amount' => 'required|numeric|min:0',
        ]);

        $installmentModel = Installment::findOrFail($installment);
        $principal = (float) $validated['principal_amount'];
        $interest = (float) $validated['interest_amount'];
        $splitTotal = $principal + $interest;

        if ($installmentModel->bilyet_amount !== null) {
            $bilyetAmount = (float) $installmentModel->bilyet_amount;
            if (abs($splitTotal - $bilyetAmount) > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah pokok + bunga ('.number_format($splitTotal, 0, ',', '.').') harus sama dengan nominal angsuran ('.number_format($bilyetAmount, 0, ',', '.').').',
                ], 422);
            }
        }

        $installmentModel->update([
            'principal_amount' => $principal,
            'interest_amount' => $interest,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Split pokok/bunga berhasil disimpan.',
        ]);
    }
}
