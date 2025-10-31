<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract API key from header
        $rawKey = $request->header('X-API-Key');

        // Check if API key is provided
        if (!$rawKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required. Please provide X-API-Key header.',
            ], 401);
        }

        // Validate the API key
        $apiKey = ApiKey::validate($rawKey);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API key',
            ], 401);
        }

        // Update last used timestamp (asynchronously to avoid slowing down request)
        dispatch(function () use ($apiKey) {
            $apiKey->markAsUsed();
        })->afterResponse();

        // Attach API key info to request for logging/tracking
        $request->merge([
            'api_key_id' => $apiKey->id,
            'api_key_name' => $apiKey->name,
            'api_key_application' => $apiKey->application,
        ]);

        // Store API key instance in request for controller access if needed
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
