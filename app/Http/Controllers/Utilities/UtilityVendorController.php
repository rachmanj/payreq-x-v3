<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUtilityVendorRequest;
use App\Models\SapBusinessPartner;
use App\Models\UtilityCustomer;
use App\Models\UtilityVendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UtilityVendorController extends Controller
{
    public function index(): View
    {
        return view('utilities.vendors.index', [
            'vendors' => $this->ensureVendorRows(),
            'jenisList' => UtilityCustomer::JENIS_UTILITAS,
            'partners' => SapBusinessPartner::query()
                ->suppliers()
                ->active()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateUtilityVendorRequest $request): RedirectResponse
    {
        $this->ensureVendorRows();

        foreach ($request->validated()['vendors'] as $jenis => $partnerId) {
            UtilityVendor::query()
                ->where('jenis_utilitas', $jenis)
                ->update([
                    'sap_business_partner_id' => $partnerId ?: null,
                ]);
        }

        return redirect()
            ->route('utilities.vendors.index')
            ->with('success', 'Mapping vendor SAP berhasil disimpan.');
    }

    /**
     * @return Collection<int, UtilityVendor>
     */
    private function ensureVendorRows(): Collection
    {
        foreach (array_keys(UtilityCustomer::JENIS_UTILITAS) as $jenis) {
            UtilityVendor::query()->firstOrCreate(
                ['jenis_utilitas' => $jenis],
                ['sap_business_partner_id' => null],
            );
        }

        return UtilityVendor::query()
            ->with('sapBusinessPartner')
            ->orderBy('jenis_utilitas')
            ->get();
    }
}
