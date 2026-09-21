<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

// TEMP DEBUG 22 Sep 2026 (hapus setelah investigasi cache)
class DebugInvoicePaymentRequestsTemp
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->isInvoicePaymentRequest($request)) {
            return $response;
        }

        try {
            $response->headers->set('X-Response-Source', 'app');

            $content = $response->getContent();
            $bodySize = is_string($content) ? strlen($content) : 0;
            $userAgent = (string) $request->userAgent();
            if (strlen($userAgent) > 120) {
                $userAgent = substr($userAgent, 0, 120);
            }

            Log::info('INVPAY REQUEST DEBUG', [
                'waktu' => now()->toIso8601String(),
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
                'method' => $request->getMethod(),
                'url' => $request->fullUrl(),
                'user_agent' => $userAgent,
                'status' => $response->getStatusCode(),
                'content_type' => (string) $response->headers->get('Content-Type', ''),
                'body_size' => $bodySize,
                'x-response-source' => 'app',
            ]);
        } catch (\Throwable) {
            // TEMP DEBUG 22 Sep 2026 (hapus setelah investigasi cache)
        }

        return $response;
    }

    private function isInvoicePaymentRequest(Request $request): bool
    {
        if ($request->is('*invoice-payment*')) {
            return true;
        }

        return str_contains($request->getRequestUri(), 'invoice-payment');
    }
}
