<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ToolController;
use App\Models\Account;
use App\Models\Bilyet;
use App\Models\GeneralOutgoingPayment;
use App\Models\Giro;
use App\Models\SapSubmissionLog;
use App\Services\OpVoucherService;
use App\Services\SapGeneralOutgoingPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Yajra\DataTables\Facades\DataTables;

class GeneralOutgoingPaymentController extends Controller
{
    public function __construct(protected SapGeneralOutgoingPaymentService $service) {}

    public function index(): View
    {
        return view('cashier.general_op.index');
    }

    public function data()
    {
        $query = GeneralOutgoingPayment::query()
            ->with(['giro.bank', 'bilyet'])
            ->orderByDesc('doc_date');

        if (! $this->hasElevatedAccess()) {
            $query->where('project', auth()->user()->project);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('doc_date', function (GeneralOutgoingPayment $payment) {
                return $payment->doc_date?->format('d M Y') ?? '-';
            })
            ->addColumn('sap_doc_num', function (GeneralOutgoingPayment $payment) {
                return $payment->sap_doc_num ?: '-';
            })
            ->addColumn('bank_giro', function (GeneralOutgoingPayment $payment) {
                $giro = $payment->giro;
                if ($giro === null) {
                    return '-';
                }

                $bankName = $giro->bank?->name;
                $label = trim(($bankName ? $bankName.' - ' : '').($giro->acc_name ?: $giro->acc_no));

                return e($label !== '' ? $label : '-');
            })
            ->addColumn('bilyet', function (GeneralOutgoingPayment $payment) {
                $bilyet = $payment->bilyet;
                if ($bilyet === null) {
                    return '-';
                }

                return e(trim($bilyet->prefix.' '.$bilyet->nomor.' ('.$bilyet->type.')'));
            })
            ->editColumn('amount', function (GeneralOutgoingPayment $payment) {
                return number_format((int) $payment->amount, 0, ',', '.');
            })
            ->addColumn('profit_center', function (GeneralOutgoingPayment $payment) {
                return $payment->profit_center
                    ? '<small>'.e($payment->profit_center).'</small>'
                    : '<small class="text-muted">-</small>';
            })
            ->editColumn('status', function (GeneralOutgoingPayment $payment) {
                $class = $payment->status === GeneralOutgoingPayment::STATUS_SUCCESS ? 'success' : 'danger';

                return '<span class="badge badge-'.$class.'">'.e(strtoupper($payment->status)).'</span>';
            })
            ->addColumn('action', function (GeneralOutgoingPayment $payment) {
                $actions = '';

                if ($payment->sap_doc_num) {
                    $actions .= '<a href="'.route('cashier.general-op.print-op', $payment->id).'" '
                        .'class="btn btn-info btn-xs mr-1" target="_blank" title="Print OP">'
                        .'<i class="fas fa-print"></i> Print OP</a>';
                }

                return $actions !== '' ? '<div class="btn-group">'.$actions.'</div>' : '-';
            })
            ->rawColumns(['status', 'action', 'profit_center'])
            ->make(true);
    }

    public function create(): View
    {
        $project = auth()->user()->project;

        $girosQuery = Giro::query()
            ->with('bank')
            ->whereNotNull('sap_account')
            ->where('sap_account', '!=', '')
            ->orderBy('acc_no');

        if (! $this->hasElevatedAccess()) {
            $girosQuery->where('project', $project);
        }

        $giros = $girosQuery->get();

        $bilyetsQuery = Bilyet::query()
            ->where('status', 'onhand')
            ->orderBy('prefix')
            ->orderBy('nomor');

        if (! $this->hasElevatedAccess()) {
            $bilyetsQuery->where('project', $project);
        }

        $bilyets = $bilyetsQuery->get();

        $cashAccountsQuery = Account::query()
            ->where(function ($query) {
                $query->where('type', 'cash')
                    ->orWhere('is_payment_source', true);
            })
            ->where('is_active', true)
            ->whereNotNull('sap_account')
            ->where('sap_account', '!=', '')
            ->orderBy('account_number');

        if (! $this->hasElevatedAccess()) {
            $cashAccountsQuery->where('project', $project);
        }

        $cashAccounts = $cashAccountsQuery->get();

        $bilyetsByGiro = $bilyets->groupBy('giro_id')->map(
            fn ($items) => $items->map(fn (Bilyet $bilyet) => [
                'id' => $bilyet->id,
                'label' => trim($bilyet->prefix.' '.$bilyet->nomor.' ('.$bilyet->type.')'),
            ])->values()
        );

        $cashAccountOptions = $cashAccounts->map(fn (Account $account) => [
            'id' => $account->id,
            'label' => $account->account_number.' - '.$account->account_name.' ('.$account->sap_account.')',
        ])->values();

        $user = auth()->user();
        $user->loadMissing('department');
        $defaultProfitCenter = trim((string) ($user->department?->sap_code ?? ''));

        return view('cashier.general_op.create', compact(
            'giros',
            'bilyets',
            'cashAccounts',
            'project',
            'bilyetsByGiro',
            'cashAccountOptions',
            'defaultProfitCenter',
        ));
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $this->validateSubmissionRequest($request);

        $result = $this->service->preview(
            (int) $validated['giro_id'],
            (int) $validated['bilyet_id'],
            (float) $validated['amount'],
            $validated['doc_date'],
            $validated['project'],
            $validated['remarks'] ?? null,
            $this->normalizeDestinationLines($request),
            $request->user(),
            profitCenter: $validated['profit_center'] ?? null,
        );

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Validasi gagal.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'preview' => $result['preview'],
            'sap_payload' => $result['sap_payload'],
            'local_impact' => $result['local_impact'],
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $this->validateSubmissionRequest($request);

        try {
            $result = $this->service->submit(
                (int) $validated['giro_id'],
                (int) $validated['bilyet_id'],
                (float) $validated['amount'],
                $validated['doc_date'],
                $validated['posting_date'],
                $validated['project'],
                $validated['remarks'] ?? null,
                $this->normalizeDestinationLines($request),
                $request->user(),
                profitCenter: $validated['profit_center'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        if (! ($result['success'] ?? false)) {
            return back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Gagal membuat Outgoing Payment.');
        }

        return redirect()
            ->route('cashier.general-op.index')
            ->with('success', $result['message'] ?? 'Outgoing Payment berhasil dibuat.');
    }

    public function printOp(int $id, OpVoucherService $opVoucherService): View
    {
        $payment = GeneralOutgoingPayment::query()->with('bilyet')->findOrFail($id);

        if (! $this->hasElevatedAccess() && $payment->project !== auth()->user()->project) {
            abort(403, 'Anda tidak memiliki akses ke OP project lain.');
        }

        if (! $payment->sap_doc_num || ! $payment->sap_doc_entry) {
            abort(404, 'Outgoing Payment SAP belum tersedia untuk dokumen ini.');
        }

        $log = SapSubmissionLog::query()
            ->where('document_type', SapSubmissionLog::DOCUMENT_TYPE_GENERAL_OUTGOING_PAYMENT)
            ->where('status', 'success')
            ->where('sap_doc_entry', $payment->sap_doc_entry)
            ->orderByDesc('id')
            ->first();

        if ($log === null) {
            abort(404, 'Log pembayaran SAP tidak ditemukan untuk OP ini.');
        }

        try {
            $voucher = $opVoucherService->build($log);
        } catch (\Throwable $exception) {
            return view('prints.op-voucher-error', [
                'message' => $exception->getMessage(),
            ]);
        }

        if ($voucher['header'] === [] || $voucher['lines'] === []) {
            return view('prints.op-voucher-error', [
                'message' => 'Data voucher OP tidak lengkap: header atau baris akun kosong.',
            ]);
        }

        return view('prints.op-voucher', [
            'voucher' => $voucher,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateSubmissionRequest(Request $request): array
    {
        return $request->validate([
            'giro_id' => ['required', 'integer', 'exists:giros,id'],
            'bilyet_id' => ['required', 'integer', 'exists:bilyets,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'doc_date' => ['required', 'date'],
            'posting_date' => ['required', 'date'],
            'project' => ['required', 'string', 'max:10'],
            'remarks' => ['nullable', 'string', 'max:254'],
            'profit_center' => ['nullable', 'string', 'max:20'],
            'destination_accounts' => ['required', 'array', 'min:1'],
            'destination_accounts.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'destination_accounts.*.amount' => ['required', 'numeric', 'min:1'],
            'destination_accounts.*.description' => ['nullable', 'string', 'max:254'],
            'destination_accounts.*.profit_center' => ['nullable', 'string', 'max:20'],
        ]);
    }

    /**
     * @return list<array{account_id: int, amount: float|int, description?: string|null, profit_center?: string|null}>
     */
    protected function normalizeDestinationLines(Request $request): array
    {
        $lines = [];

        foreach ($request->input('destination_accounts', []) as $line) {
            if (! is_array($line)) {
                continue;
            }

            $lines[] = [
                'account_id' => (int) ($line['account_id'] ?? 0),
                'amount' => (float) ($line['amount'] ?? 0),
                'description' => $line['description'] ?? null,
                'profit_center' => isset($line['profit_center']) && trim((string) $line['profit_center']) !== ''
                    ? trim((string) $line['profit_center'])
                    : null,
            ];
        }

        return $lines;
    }

    protected function hasElevatedAccess(): bool
    {
        return count(array_intersect(['admin', 'superadmin'], app(ToolController::class)->getUserRoles())) > 0;
    }
}
