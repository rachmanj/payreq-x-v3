<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Prevents browser/proxy caching of dynamic HTML pages and JSON/AJAX API responses on GET/HEAD.
 * Skips file downloads, streamed responses, 304 responses, and responses with explicit cache policy.
 */
class PreventHtmlResponseCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $response;
        }

        if ($response->getStatusCode() === Response::HTTP_NOT_MODIFIED) {
            return $response;
        }

        if ($response instanceof StreamedResponse) {
            return $response;
        }

        $contentDisposition = (string) $response->headers->get('Content-Disposition', '');
        if ($contentDisposition !== '' && stripos($contentDisposition, 'attachment') !== false) {
            return $response;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        $isHtml = str_contains($contentType, 'text/html');
        $isJson = str_contains($contentType, 'application/json');
        if (! $isHtml && ! $isJson) {
            return $response;
        }

        $existingCacheControl = strtolower((string) $response->headers->get('Cache-Control', ''));
        // Only preserve Cache-Control when the app intentionally allows caching (e.g. cache.headers middleware).
        if ($existingCacheControl !== ''
            && (str_contains($existingCacheControl, 'public')
                || preg_match('/max-age=[1-9]\d*/', $existingCacheControl) === 1)) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
