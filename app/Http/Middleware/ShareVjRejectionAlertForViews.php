<?php

namespace App\Http\Middleware;

use App\Services\VjRejectionAlertService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareVjRejectionAlertForViews
{
    public function __construct(
        protected VjRejectionAlertService $vjRejectionAlertService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            view()->share('rejectedVjAlerts', collect());

            return $next($request);
        }

        view()->share(
            'rejectedVjAlerts',
            $this->vjRejectionAlertService->getAlertsFor($request->user())
        );

        return $next($request);
    }
}
