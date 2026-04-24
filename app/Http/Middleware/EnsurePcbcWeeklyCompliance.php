<?php

namespace App\Http\Middleware;

use App\Services\PcbcComplianceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePcbcWeeklyCompliance
{
    public function __construct(
        protected PcbcComplianceService $pcbcComplianceService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->pcbcComplianceService->shouldEnforceForUser($user)) {
            return $next($request);
        }

        if (! $this->pcbcComplianceService->isSanctioned($user)) {
            return $next($request);
        }

        return redirect()
            ->route('cashier.pcbc.index', ['page' => 'upload'])
            ->with(
                'error',
                'Access is limited until PCBC compliance is met: upload a PCBC PDF with a document date covering a missing week (Mon–Sun, '.config('pcbc_compliance.timezone').').'
            );
    }
}
