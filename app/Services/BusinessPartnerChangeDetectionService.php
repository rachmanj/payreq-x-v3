<?php

namespace App\Services;

use App\Models\SapBusinessPartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusinessPartnerChangeDetectionService
{
    public function detectChanges(): array
    {
        $changes = [
            'new_partners' => [],
            'updated_partners' => [],
            'deactivated_partners' => [],
            'reactivated_partners' => [],
        ];

        $recentSync = SapBusinessPartner::whereNotNull('last_synced_at')
            ->orderBy('last_synced_at', 'desc')
            ->first();

        if (!$recentSync) {
            return $changes;
        }

        $lastSyncTime = $recentSync->last_synced_at;

        $newPartners = SapBusinessPartner::where('last_synced_at', '>=', $lastSyncTime->subMinutes(5))
            ->where('created_at', '>=', $lastSyncTime)
            ->get();

        foreach ($newPartners as $partner) {
            $changes['new_partners'][] = [
                'code' => $partner->code,
                'name' => $partner->name,
                'type' => $partner->type,
            ];
        }

        $deactivated = SapBusinessPartner::where('active', false)
            ->where('last_synced_at', '>=', $lastSyncTime)
            ->get();

        foreach ($deactivated as $partner) {
            $changes['deactivated_partners'][] = [
                'code' => $partner->code,
                'name' => $partner->name,
                'type' => $partner->type,
            ];
        }

        return $changes;
    }

    public function getStatistics(): array
    {
        return [
            'total' => SapBusinessPartner::count(),
            'active' => SapBusinessPartner::where('active', true)->count(),
            'inactive' => SapBusinessPartner::where('active', false)->count(),
            'customers' => SapBusinessPartner::where('type', 'cCustomer')->count(),
            'suppliers' => SapBusinessPartner::where('type', 'cSupplier')->count(),
            'leads' => SapBusinessPartner::where('type', 'cLead')->count(),
            'vat_liable' => SapBusinessPartner::where('vat_liable', true)->count(),
            'with_credit_limit' => SapBusinessPartner::whereNotNull('credit_limit')
                ->where('credit_limit', '>', 0)
                ->count(),
            'last_synced' => SapBusinessPartner::whereNotNull('last_synced_at')
                ->max('last_synced_at'),
        ];
    }
}
