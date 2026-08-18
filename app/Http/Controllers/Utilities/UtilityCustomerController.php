<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Project;
use App\Models\UtilityCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UtilityCustomerController extends Controller
{
    public function index(): View
    {
        return view('utilities.customers.index', [
            'jenisList' => UtilityCustomer::JENIS_UTILITAS,
        ]);
    }

    public function data(): JsonResponse
    {
        return datatables()->of(
            UtilityCustomer::query()->with('account')->orderBy('nama')
        )
            ->addColumn('jenis_label', fn (UtilityCustomer $customer) => UtilityCustomer::JENIS_UTILITAS[$customer->jenis_utilitas] ?? $customer->jenis_utilitas)
            ->addColumn('account_info', function (UtilityCustomer $customer) {
                if (! $customer->account) {
                    return '-';
                }

                return '<small>'.$customer->account->account_number.'</small><br><small>'.e($customer->account->account_name).'</small>';
            })
            ->addColumn('is_active_badge', fn (UtilityCustomer $customer) => $customer->is_active
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-secondary">Nonaktif</span>')
            ->addColumn('action', 'utilities.customers.action')
            ->rawColumns(['account_info', 'is_active_badge', 'action'])
            ->toJson();
    }

    public function create(): View
    {
        return view('utilities.customers.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCustomer($request);

        UtilityCustomer::create($validated);

        return redirect()->route('utilities.customers.index')->with('success', 'ID Pelanggan berhasil ditambahkan.');
    }

    public function edit(UtilityCustomer $customer): View
    {
        return view('utilities.customers.edit', array_merge($this->formData(), [
            'customer' => $customer,
        ]));
    }

    public function show(UtilityCustomer $customer): RedirectResponse
    {
        return redirect()->route('utilities.customers.edit', $customer);
    }

    public function update(Request $request, UtilityCustomer $customer): RedirectResponse
    {
        $validated = $this->validateCustomer($request, $customer->id);

        $customer->update($validated);

        return redirect()->route('utilities.customers.index')->with('success', 'ID Pelanggan berhasil diperbarui.');
    }

    public function destroy(UtilityCustomer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('utilities.customers.index')->with('success', 'ID Pelanggan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'jenisList' => UtilityCustomer::JENIS_UTILITAS,
            'projects' => Project::orderBy('code')->get(),
            'accounts' => Account::query()->selectable()->orderBy('account_number')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCustomer(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'unique:utility_customers,id_pelanggan';
        if ($ignoreId) {
            $uniqueRule .= ','.$ignoreId.',id,jenis_utilitas,'.$request->input('jenis_utilitas');
        } else {
            $uniqueRule .= ',NULL,id,jenis_utilitas,'.$request->input('jenis_utilitas');
        }

        $validated = $request->validate([
            'jenis_utilitas' => 'required|in:pln,pdam,telkom',
            'id_pelanggan' => ['required', 'string', 'max:50', $uniqueRule],
            'nama' => 'required|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'project' => 'required|string|max:20',
            'account_id' => 'nullable|exists:accounts,id',
            'is_active' => 'nullable|boolean',
        ], [
            'id_pelanggan.unique' => 'ID Pelanggan untuk jenis utilitas ini sudah terdaftar.',
        ]);

        $validated['is_active'] = $request->has('is_active');

        return $validated;
    }
}
