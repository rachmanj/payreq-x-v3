<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\PayreqController;
use App\Http\Requests\StoreRealizationDetailRequest;
use App\Http\Requests\UpdateRealizationDetailRequest;
use App\Models\Bank;
use App\Models\Equipment;
use App\Models\LotClaim;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\TransferAccount;
use App\Services\PayreqBudgetSubmitValidator;
use App\Services\PayreqSubmitLimitService;
use App\Services\PayreqTransferDestinationService;
use App\Support\PayreqPaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PayreqReimburseController extends Controller
{
    private function lotClaimForPayreq(Payreq $payreq): ?LotClaim
    {
        if (! $payreq->lot_no) {
            return null;
        }

        return LotClaim::where('lot_no', $payreq->lot_no)->first();
    }

    public function create()
    {
        $payreq_no = app(DocumentNumberController::class)->generate_draft_document_number(auth()->user()->project);
        $rabs = app(UserAnggaranController::class)->getAvailableRabs();
        $transferAccounts = TransferAccount::where('user_id', auth()->id())->with('bank')->orderBy('label')->get();
        $banks = Bank::orderBy('name')->get();

        return view('user-payreqs.reimburse.create', compact('payreq_no', 'rabs', 'transferAccounts', 'banks'));
    }

    public function store(Request $request)
    {
        $this->normalizeTransferDestinationsInput($request);

        $validated = $request->validate(array_merge([
            'employee_id' => 'required|exists:users,id',
            'payreq_type' => 'required|in:reimburse',
            'payreq_no' => 'required|string|max:100',
            'project' => 'required|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'remarks' => 'required|string',
            'rab_id' => 'nullable|exists:anggarans,id',
        ], PayreqPaymentMethod::rules(), PayreqTransferDestinationService::rules()));

        if ((int) $validated['employee_id'] !== (int) auth()->id()) {
            abort(403, 'Invalid request.');
        }

        $destinationErrors = $this->validateTransferDestinations(
            $validated['transfer_destinations'] ?? null,
            null
        );
        if ($destinationErrors->isNotEmpty()) {
            return redirect()->back()->withInput()->withErrors($destinationErrors);
        }

        if ($validated['payment_method'] === 'transfer' && ! PayreqTransferDestinationService::hasDestinations($validated['transfer_destinations'] ?? null)) {
            $owned = TransferAccount::where('id', $validated['transfer_account_id'])
                ->where('user_id', auth()->id())
                ->exists();
            if (! $owned) {
                return redirect()->back()->withInput()->withErrors([
                    'transfer_account_id' => 'Akun transfer tidak valid atau bukan milik Anda.',
                ]);
            }
        }

        if (PayreqTransferDestinationService::hasDestinations($validated['transfer_destinations'] ?? null)) {
            $request->merge([
                'payment_method' => 'transfer',
                'transfer_account_id' => PayreqTransferDestinationService::firstTransferAccountId(
                    $validated['transfer_destinations'] ?? null
                ),
            ]);
        }

        $payreq = app(PayreqController::class)->store($request);

        PayreqTransferDestinationService::sync(
            $payreq,
            $validated['transfer_destinations'] ?? null,
            (int) auth()->id(),
            PayreqTransferDestinationService::isPresentMarked($validated['transfer_destinations_present'] ?? null)
        );

        // Create new Realization
        $realization = Realization::create([
            'payreq_id' => $payreq->id,
            'project' => $payreq->project,
            'department_id' => $payreq->department_id,
            'remarks' => $request->remarks,
            'user_id' => $payreq->user_id,
            'nomor' => app(DocumentNumberController::class)->generate_draft_document_number(auth()->user()->project),
            'status' => 'reimburse-draft',
        ]);

        $equipments = $this->getEquipments();
        $rabs = app(UserAnggaranController::class)->getAvailableRabs();
        $lotc_detail = $this->lotClaimForPayreq($payreq);
        $transferAccounts = TransferAccount::where('user_id', auth()->id())->with('bank')->orderBy('label')->get();
        $banks = Bank::orderBy('name')->get();
        $transferDestinations = $payreq->transferDestinations()->with('transferAccount.bank')->get();

        $submitLimitSummary = app(PayreqSubmitLimitService::class)->summary((int) auth()->id());
        $submitLimitBlockedMessage = $submitLimitSummary['blocked']
            ? "Kamu masih punya {$submitLimitSummary['count']} payreq menunggu approval (maksimal {$submitLimitSummary['limit']}). Selesaikan dulu sebelum submit payreq baru."
            : '';

        return view('user-payreqs.reimburse.add_details', compact(['payreq', 'equipments', 'realization', 'rabs', 'lotc_detail', 'transferAccounts', 'banks', 'transferDestinations', 'submitLimitSummary', 'submitLimitBlockedMessage']));
    }

    public function edit($id)
    {
        $equipments = $this->getEquipments();
        $payreq = Payreq::findOrFail($id);
        $realization = Realization::where('payreq_id', $payreq->id)
            ->with(['realizationDetails'])
            ->first();
        $rabs = app(UserAnggaranController::class)->getAvailableRabs();
        $lotc_detail = $this->lotClaimForPayreq($payreq);
        $transferAccounts = TransferAccount::where('user_id', auth()->id())->with('bank')->orderBy('label')->get();
        $banks = Bank::orderBy('name')->get();
        $transferDestinations = $payreq->transferDestinations()->with('transferAccount.bank')->get();

        $submitLimitSummary = app(PayreqSubmitLimitService::class)->summary((int) auth()->id());
        $submitLimitBlockedMessage = $submitLimitSummary['blocked']
            ? "Kamu masih punya {$submitLimitSummary['count']} payreq menunggu approval (maksimal {$submitLimitSummary['limit']}). Selesaikan dulu sebelum submit payreq baru."
            : '';

        return view('user-payreqs.reimburse.add_details', compact(['payreq', 'equipments', 'realization', 'rabs', 'lotc_detail', 'transferAccounts', 'banks', 'transferDestinations', 'submitLimitSummary', 'submitLimitBlockedMessage']));
    }

    public function store_detail(StoreRealizationDetailRequest $request)
    {
        $realization = Realization::findOrFail($request->validated('realization_id'));
        $payreq = Payreq::findOrFail($realization->payreq_id);

        $rab_id = $payreq->rab_id;

        $detail = $realization->realizationDetails()->create(array_merge($request->realizationDetailPayload(), [
            'project' => $realization->project,
            'department_id' => $realization->department_id,
            'rab_id' => $rab_id,
        ]));

        $payreq->update([
            'amount' => $realization->realizationDetails()->sum('amount'),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Detail added successfully',
                'total' => $realization->realizationDetails()->sum('amount'),
                'detail' => $detail->fresh(['activity']),
            ]);
        }

        return $this->edit($realization->payreq_id);
    }

    public function submit_payreq(Request $request)
    {
        $realization = Realization::findOrFail($request->realization_id);
        $payreq = Payreq::with('requestor')->findOrFail($realization->payreq_id);

        if ($error = app(PayreqBudgetSubmitValidator::class)->validate($payreq)) {
            $payreq->update([
                'status' => 'draft',
                'editable' => '1',
                'deletable' => '1',
            ]);

            return redirect()->route('user-payreqs.index')->with('error', $error);
        }

        if ($error = app(PayreqSubmitLimitService::class)->validate($payreq->requestor)) {
            return redirect()->route('user-payreqs.index')->with('error', $error);
        }

        // create approval plan
        $approval_plan = app(ApprovalPlanController::class)->create_approval_plan('payreq', $payreq->id);

        if (! $approval_plan) {
            return redirect()->route('user-payreqs.index')->with('error', 'Payreq failed to submit');
        }

        $payreq->update([
            'status' => 'submitted',
            // 'printable' => 1, // saat submit payreq, sudah bisa langsung printable
            'draft_no' => $payreq->nomor, // Simpan draft number
            'nomor' => app(DocumentNumberController::class)->generate_document_number('payreq', auth()->user()->project),
        ]);

        $realization->update([
            'status' => 'reimburse-submitted',
            'submit_at' => Carbon::now(),
            'editable' => 0,
            'deletable' => 0,
            'draft_no' => $realization->nomor, // Simpan draft number
            'nomor' => app(DocumentNumberController::class)->generate_document_number('realization', auth()->user()->project),
        ]);

        return redirect()->route('user-payreqs.index')->with('success', 'Payreq submitted successfully');
    }

    public function delete_detail(Request $request)
    {
        $realization = Realization::findOrFail($request->realization_id);

        $realization_detail = RealizationDetail::findOrFail($request->realization_detail_id);
        $realization_detail->delete();

        // update payreq amount is sum of realization details amount
        $payreq = Payreq::findOrFail($realization->payreq_id);
        $payreq->update([
            'amount' => $realization->realizationDetails()->sum('amount'),
        ]);

        // Check if this is an AJAX request
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Detail deleted successfully',
                'total' => $realization->realizationDetails()->sum('amount'),
            ]);
        }

        return $this->edit($realization->payreq_id);
    }

    public function update_detail(UpdateRealizationDetailRequest $request)
    {
        $detail = RealizationDetail::findOrFail($request->validated('realization_detail_id'));
        $realization = Realization::findOrFail($detail->realization_id);

        $detail->update($request->realizationDetailPayload());

        $payreq = Payreq::findOrFail($realization->payreq_id);
        $payreq->update([
            'amount' => $realization->realizationDetails()->sum('amount'),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Detail updated successfully',
                'total' => $realization->realizationDetails()->sum('amount'),
                'detail' => $detail->fresh(['activity']),
            ]);
        }

        return $this->edit($realization->payreq_id);
    }

    public function getEquipments()
    {
        if (! in_array(auth()->user()->project, ['000H', 'APS', '001H'])) {
            return Equipment::where('project', auth()->user()->project)->orderBy('unit_code', 'asc')->get();
        }

        return Equipment::orderBy('unit_code', 'asc')->get();
    }

    public function update_rab(Request $request)
    {
        $request->merge([
            'payment_method' => $request->input('payment_method', 'cash'),
        ]);

        $this->normalizeTransferDestinationsInput($request);

        $validated = $request->validate(array_merge([
            'payreq_id' => 'required|exists:payreqs,id',
            'rab_id' => 'nullable|exists:anggarans,id',
            'remarks' => 'nullable|string',
        ], PayreqPaymentMethod::rules(), PayreqTransferDestinationService::rules()));

        $payreq = Payreq::findOrFail($validated['payreq_id']);

        if ((int) $payreq->user_id !== (int) auth()->id()) {
            $errorPayload = [
                'status' => 'error',
                'message' => 'RAB tidak dapat diubah karena payreq ini bukan milik akun Anda. Silakan login dengan akun pemilik payreq.',
            ];

            return response()->json($errorPayload, 403);
        }

        if (! in_array($payreq->status, ['draft', 'revise'], true)) {
            $errorPayload = [
                'status' => 'error',
                'message' => 'Metode pembayaran tidak dapat diubah pada status payreq ini.',
            ];

            return response()->json($errorPayload, 403);
        }

        $destinationErrors = $this->validateTransferDestinations(
            $validated['transfer_destinations'] ?? null,
            $payreq->amount !== null ? (int) $payreq->amount : null
        );
        if ($destinationErrors->isNotEmpty()) {
            $errorMessage = $destinationErrors->first();

            return response()->json([
                'status' => 'error',
                'message' => $errorMessage,
            ], 422);
        }

        if ($validated['payment_method'] === 'transfer' && ! PayreqTransferDestinationService::hasDestinations($validated['transfer_destinations'] ?? null)) {
            $owned = TransferAccount::where('id', $validated['transfer_account_id'])
                ->where('user_id', auth()->id())
                ->exists();
            if (! $owned) {
                $errorMessage = 'Akun transfer tidak valid atau bukan milik Anda.';

                return response()->json([
                    'status' => 'error',
                    'message' => $errorMessage,
                ], 422);
            }
        }

        $paymentData = $validated;
        if (PayreqTransferDestinationService::hasDestinations($validated['transfer_destinations'] ?? null)) {
            $paymentData['payment_method'] = 'transfer';
            $paymentData['transfer_account_id'] = PayreqTransferDestinationService::firstTransferAccountId(
                $validated['transfer_destinations'] ?? null
            );
        }

        $paymentAttrs = PayreqPaymentMethod::normalizedAttributes($paymentData);
        $finalPaymentMethod = $paymentAttrs['payment_method'];

        $updateAttributes = [
            'rab_id' => $validated['rab_id'] ?? $payreq->rab_id,
            'remarks' => $validated['remarks'] ?? $payreq->remarks,
        ];

        if ($finalPaymentMethod === 'transfer') {
            $updateAttributes['payment_method'] = $paymentAttrs['payment_method'];
            $updateAttributes['transfer_account_id'] = $paymentAttrs['transfer_account_id'];
        }

        $payreq->update($updateAttributes);

        if ($finalPaymentMethod === 'transfer') {
            PayreqTransferDestinationService::sync(
                $payreq->fresh(),
                $validated['transfer_destinations'] ?? null,
                (int) auth()->id(),
                PayreqTransferDestinationService::isPresentMarked($validated['transfer_destinations_present'] ?? null)
            );
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'RAB updated successfully',
            ]);
        }

        return redirect()->route('user-payreqs.index')->with('success', 'RAB updated successfully');
    }

    private function normalizeTransferDestinationsInput(Request $request): void
    {
        $destinations = $request->input('transfer_destinations', []);
        if (! is_array($destinations)) {
            return;
        }

        $normalized = [];
        foreach (PayreqTransferDestinationService::pruneEmptyRows($destinations) as $row) {
            $row['planned_amount'] = PayreqTransferDestinationService::normalizePlannedAmountInput(
                $row['planned_amount'] ?? null
            );

            $normalized[] = $row;
        }

        $request->merge(['transfer_destinations' => $normalized]);

        if (PayreqTransferDestinationService::hasDestinations($normalized)) {
            $request->merge([
                'payment_method' => 'transfer',
                'transfer_account_id' => PayreqTransferDestinationService::firstTransferAccountId($normalized),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $destinations
     */
    private function validateTransferDestinations(?array $destinations, ?int $payreqAmount): \Illuminate\Support\MessageBag
    {
        $validator = Validator::make(
            ['transfer_destinations' => $destinations],
            PayreqTransferDestinationService::rules()
        );

        PayreqTransferDestinationService::assertValid(
            $validator,
            $destinations,
            (int) auth()->id(),
            $payreqAmount
        );

        return $validator->errors();
    }
}
