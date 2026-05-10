<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MenuSearchService;
use App\Services\PcbcComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuSearchController extends Controller
{
    public function index(
        Request $request,
        MenuSearchService $menuSearchService,
        PcbcComplianceService $pcbcComplianceService,
    ): JsonResponse {
        $user = $request->user();

        $sortedPermissions = $user->getAllPermissions()->pluck('name')->sort()->values()->all();
        $inCashierPcbcScope = $user->can('akses_transaksi_cashier') || $user->can('akses_pcbc');
        $sanctionedSuffix = ($inCashierPcbcScope && $pcbcComplianceService->isSanctioned($user)) ? '1' : '0';
        $permissionFingerprint = md5(implode('|', $sortedPermissions).'|'.$sanctionedSuffix);
        $cacheKey = 'menu_items_user_'.$user->id.'_'.$permissionFingerprint;

        $items = Cache::remember($cacheKey, 3600, fn () => $menuSearchService->getItemsForUser($user));

        if ($request->filled('q')) {
            $term = strtolower((string) $request->query('q'));
            $items = array_values(array_filter(
                $items,
                fn (array $item): bool => str_contains($item['searchText'], $term)
            ));
            $items = array_slice($items, 0, 15);
        }

        return response()->json([
            'items' => $items,
        ]);
    }
}
