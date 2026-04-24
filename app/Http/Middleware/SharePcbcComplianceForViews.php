<?php

namespace App\Http\Middleware;

use App\Services\PcbcComplianceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SharePcbcComplianceForViews
{
    public function __construct(
        protected PcbcComplianceService $pcbcComplianceService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            view()->share('pcbcCompliance', null);
            view()->share('pcbcViolationSanctioned', false);

            return $next($request);
        }

        $user = $request->user();

        $inCashierPcbcScope = $user->can('akses_transaksi_cashier') || $user->can('akses_pcbc');
        view()->share(
            'pcbcViolationSanctioned',
            $inCashierPcbcScope && $this->pcbcComplianceService->isSanctioned($user)
        );

        if ($user->can('see_pcbc_warning')) {
            view()->share('pcbcCompliance', $this->pcbcComplianceService->getStatus($user));
        } else {
            view()->share('pcbcCompliance', null);
        }

        return $next($request);
    }
}
