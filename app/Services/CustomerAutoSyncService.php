<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SapBusinessPartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerAutoSyncService
{
    public function syncCustomersFromBusinessPartners(): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();
        try {
            $businessPartners = SapBusinessPartner::where('type', 'cCustomer')
                ->where('active', true)
                ->get();

            foreach ($businessPartners as $bp) {
                try {
                    $customer = Customer::where('code', $bp->code)->first();

                    if ($customer) {
                        $customer->update([
                            'name' => $bp->name,
                            'npwp' => $bp->federal_tax_id,
                            'type' => 'customer',
                        ]);
                        $stats['updated']++;
                    } else {
                        Customer::create([
                            'code' => $bp->code,
                            'name' => $bp->name,
                            'npwp' => $bp->federal_tax_id,
                            'type' => 'customer',
                        ]);
                        $stats['created']++;
                    }
                } catch (\Throwable $e) {
                    $stats['errors'][] = "Failed to sync {$bp->code}: " . $e->getMessage();
                    Log::error('Customer auto-sync error', [
                        'bp_code' => $bp->code,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $stats['errors'][] = 'Transaction failed: ' . $e->getMessage();
            Log::error('Customer auto-sync transaction failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

    public function syncVendorsFromBusinessPartners(): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();
        try {
            $businessPartners = SapBusinessPartner::where('type', 'cSupplier')
                ->where('active', true)
                ->get();

            foreach ($businessPartners as $bp) {
                try {
                    $customer = Customer::where('code', $bp->code)->first();

                    if ($customer) {
                        $customer->update([
                            'name' => $bp->name,
                            'npwp' => $bp->federal_tax_id,
                            'type' => 'vendor',
                        ]);
                        $stats['updated']++;
                    } else {
                        Customer::create([
                            'code' => $bp->code,
                            'name' => $bp->name,
                            'npwp' => $bp->federal_tax_id,
                            'type' => 'vendor',
                        ]);
                        $stats['created']++;
                    }
                } catch (\Throwable $e) {
                    $stats['errors'][] = "Failed to sync {$bp->code}: " . $e->getMessage();
                    Log::error('Vendor auto-sync error', [
                        'bp_code' => $bp->code,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $stats['errors'][] = 'Transaction failed: ' . $e->getMessage();
            Log::error('Vendor auto-sync transaction failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

    public function syncAll(): array
    {
        return [
            'customers' => $this->syncCustomersFromBusinessPartners(),
            'vendors' => $this->syncVendorsFromBusinessPartners(),
        ];
    }
}
