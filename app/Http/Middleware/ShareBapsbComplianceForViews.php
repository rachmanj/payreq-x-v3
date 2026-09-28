<?php

namespace App\Http\Middleware;

use App\Services\BapsbComplianceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareBapsbComplianceForViews
{
    public function __construct(
        protected BapsbComplianceService $bapsbComplianceService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            view()->share('bapsbComplianceWarning', null);
            view()->share('bapsb_pending_validation_count', 0);

            return $next($request);
        }

        $user = $request->user();
        view()->share('bapsbComplianceWarning', $this->bapsbComplianceService->getWarningForUser($user));

        $pending = 0;
        if ($user->can('validate_bapsb_report')) {
            $pending = $this->bapsbComplianceService->pendingValidationCount();
        }
        view()->share('bapsb_pending_validation_count', $pending);

        return $next($request);
    }
}
