<?php

namespace App\Http\Controllers\UserPayreq;

use App\Http\Controllers\ApprovalPlanController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Requests\UserPayreq\ProcessAdvancePayreqRequest;
use App\Models\Payreq;
use App\Models\PayreqAnggaranAllocation;
use App\Models\Realization;
use App\Services\LotService;
use App\Support\PayreqBudgetLinkMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayreqAdvanceController extends Controller
{
    public function __construct(
        protected LotService $lotService
    ) {}

    public function create()
    {
        $payreq_no = app(DocumentNumberController::class)->generate_draft_document_number(auth()->user()->project);
        $rabs = app(UserAnggaranController::class)->getAvailableRabs();

        return view('user-payreqs.advance.create', compact(['payreq_no', 'rabs']));
    }

    public function edit($id)
    {
        $payreq = Payreq::with(['anggaranAllocations'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);
        $rabs = app(UserAnggaranController::class)->getAvailableRabs();

        return view('user-payreqs.advance.edit', compact(['payreq', 'rabs']));
    }

    public function proses(ProcessAdvancePayreqRequest $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();

        $payreqId = $validated['payreq_id'] ?? null;
        try {
            $payreq = DB::transaction(function () use ($validated, $payreqId) {
                return $payreqId
                    ? $this->persistAdvanceDraftUpdate((int) $payreqId, $validated)
                    : $this->persistAdvanceDraftCreate($validated);
            });
        } catch (\Throwable) {
            return redirect()->back()->withInput()->with('error', 'Payreq draft gagal disimpan.');
        }

        if (in_array($validated['button_type'], ['create_submit', 'edit_submit'], true)) {
            return $this->submit($payreq->id);
        }

        return $this->draft_redirect($payreq);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistAdvanceDraftCreate(array $validated): Payreq
    {
        $payreqAttrs = $this->advancePayreqAttributes($validated);

        $payreq = Payreq::create($payreqAttrs);
        $this->syncAdvanceAllocations($payreq, $validated);

        return $payreq;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistAdvanceDraftUpdate(int $payreqId, array $validated): Payreq
    {
        $payreq = Payreq::where('user_id', Auth::id())->findOrFail($payreqId);
        $payreqAttrs = $this->advancePayreqAttributes($validated);
        $payreq->update($payreqAttrs);
        $this->syncAdvanceAllocations($payreq->fresh(), $validated);

        return $payreq->fresh();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function advancePayreqAttributes(array $validated): array
    {
        $mode = $validated['budget_link_mode'];
        $amount = $validated['amount'];

        $rabId = null;
        if ($mode === PayreqBudgetLinkMode::MULTI_ALLOCATION) {
            $rabId = isset($validated['allocations'][0]['anggaran_id'])
                ? (int) $validated['allocations'][0]['anggaran_id']
                : null;
        } elseif (! empty($validated['rab_id'])) {
            $rabId = (int) $validated['rab_id'];
        }

        return [
            'remarks' => $validated['remarks'],
            'amount' => is_numeric($amount) ? $amount : str_replace(',', '', (string) $amount),
            'project' => $validated['project'],
            'department_id' => $validated['department_id'] ?? Auth::user()->department_id,
            'nomor' => $validated['payreq_no'],
            'status' => 'draft',
            'type' => 'advance',
            'rab_id' => $rabId,
            'budget_link_mode' => $mode,
            'lot_no' => $validated['lot_no'] ?? null,
            'user_id' => (int) $validated['employee_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncAdvanceAllocations(Payreq $payreq, array $validated): void
    {
        if (($validated['budget_link_mode'] ?? null) !== PayreqBudgetLinkMode::MULTI_ALLOCATION) {
            $payreq->anggaranAllocations()->delete();

            return;
        }

        $payreq->anggaranAllocations()->delete();

        foreach ($validated['allocations'] ?? [] as $index => $row) {
            PayreqAnggaranAllocation::create([
                'payreq_id' => $payreq->id,
                'anggaran_id' => (int) $row['anggaran_id'],
                'amount' => $row['amount'],
                'remarks' => $row['remarks'] ?? null,
                'sort_order' => (int) $index,
            ]);
        }
    }

    public function submit($id)
    {
        $payreq = Payreq::find($id);

        if (! in_array(auth()->user()->project, ['000H', 'APS'], true)) {
            $response = app(ApprovalPlanController::class)->create_approval_plan('payreq', $id);

            if (! $response) {
                return redirect()->route('user-payreqs.index')->with('error', 'Payreq gagal disubmit. Hubungi IT Administrator');
            }

            $payreq->update([
                'status' => 'submitted',
                'editable' => '0',
                'deletable' => '0',
            ]);

            return redirect()->route('user-payreqs.index')->with('success', 'Payreq berhasil disubmit');
        }

        if ($payreq->isAdvanceMultiBudget()) {
            if ($payreq->anggaranAllocations()->count() < 1) {
                return redirect()->route('user-payreqs.index')->with('error', 'Alokasi anggaran minimal satu baris, payreq belum bisa disubmit');
            }

            $sumAlloc = round((float) $payreq->anggaranAllocations()->sum('amount'), 2);
            $sumPayreq = round((float) $payreq->amount, 2);

            if (abs($sumAlloc - $sumPayreq) > 0.009) {
                return redirect()->route('user-payreqs.index')->with('error', 'Jumlah alokasi baris tidak sama dengan total payreq');
            }

            $response = app(ApprovalPlanController::class)->create_approval_plan('payreq', $id);
            if (! $response) {
                return redirect()->route('user-payreqs.index')->with('error', 'Payreq gagal disubmit. Hubungi IT Administrator');
            }

            $payreq->update([
                'status' => 'submitted',
                'editable' => '0',
                'deletable' => '0',
            ]);

            return redirect()->route('user-payreqs.index')->with('success', 'Payreq berhasil disubmit');
        }

        if (! $payreq->rab_id) {
            $payreq->update([
                'status' => 'draft',
            ]);

            return redirect()->route('user-payreqs.index')->with('error', 'RAB harus diisi, payreq belum bisa disubmit');
        }

        $response = app(ApprovalPlanController::class)->create_approval_plan('payreq', $id);
        if (! $response) {
            return redirect()->route('user-payreqs.index')->with('error', 'Payreq gagal disubmit. Hubungi IT Administrator');
        }

        $payreq->update([
            'status' => 'submitted',
            'editable' => '0',
            'deletable' => '0',
        ]);

        return redirect()->route('user-payreqs.index')->with('success', 'Payreq berhasil disubmit');
    }

    public function draft_redirect($response)
    {
        if (! $response) {
            return redirect()->route('user-payreqs.index')->with('error', 'Payreq draft gagal disimpan. Hubungi IT Administrator');
        }

        return redirect()->route('user-payreqs.index')->with('success', 'Payreq draft berhasil disimpan');
    }

    public function create_new_payreq($realization_id)
    {
        $realization = Realization::findOrFail($realization_id);
        $payreq_amount = $realization->payreq->amount;
        $realization_amount = $realization->realizationDetails->sum('amount');
        $kekurangan = $realization_amount - $payreq_amount;
        $payreq_no = app(DocumentNumberController::class)->generate_document_number('payreq', $realization->project);

        return Payreq::create([
            'amount' => $kekurangan,
            'department_id' => $realization->department_id,
            'project' => $realization->project,
            'status' => 'approved',
            'editable' => 0,
            'deletable' => 0,
            'printable' => 1,
            'nomor' => $payreq_no,
            'type' => 'other',
            'remarks' => 'Kekurangan untuk Realization Nomor '.$realization->nomor,
            'user_id' => $realization->payreq->user_id,
            'approved_at' => date('Y-m-d H:i:s'),
            'submit_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function searchLOT(Request $request)
    {
        $searchParams = [
            'travel_number' => $request->travel_number,
            'traveler' => $request->traveler,
            'department' => $request->department,
            'project' => $request->project,
        ];

        $result = $this->lotService->search($searchParams);

        if ($result['success'] && ! empty($result['data'])) {
            foreach ($result['data'] as &$lot) {
                if (! empty($lot['official_travel_number'])) {
                    $payreq = Payreq::where('lot_no', $lot['official_travel_number'])
                        ->select('id', 'nomor', 'amount', 'status')
                        ->first();

                    if ($payreq) {
                        $lot['payment_request'] = [
                            'id' => $payreq->id,
                            'nomor' => $payreq->nomor,
                            'amount' => $payreq->amount,
                            'status' => $payreq->status,
                        ];
                    }
                }
            }
        }

        return response()->json($result);
    }
}
