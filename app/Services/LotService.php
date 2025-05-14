<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LotService
{
    protected $baseUrl;
    protected $searchEndpoint;
    protected $timeout;

    public function __construct()
    {
        $this->initializeConfig();
    }

    protected function initializeConfig()
    {
        $this->baseUrl = rtrim(config('services.lot.base_url'), '/');
        $this->searchEndpoint = ltrim(config('services.lot.search_endpoint'), '/');
        $this->timeout = config('services.lot.timeout', 30);

        Log::info('LOT Service Initialized', [
            'base_url' => $this->baseUrl,
            'search_endpoint' => $this->searchEndpoint,
            'full_url' => $this->getFullUrl()
        ]);
    }

    protected function getFullUrl(): string
    {
        return $this->baseUrl . '/' . $this->searchEndpoint;
    }

    public function search(array $params)
    {
        try {
            // Check if fetch_all is enabled
            $fetchAll = isset($params['fetch_all']) && $params['fetch_all'];

            // Remove fetch_all from search params
            if (isset($params['fetch_all'])) {
                unset($params['fetch_all']);
            }

            // Validate search parameters only if not fetching all
            if (!$fetchAll && $this->areAllParamsEmpty($params)) {
                return [
                    'success' => false,
                    'message' => 'Please enter at least one search parameter'
                ];
            }

            $searchParams = $this->prepareSearchParams($params);

            // If fetch_all is true and no specific search parameters, set a flag for the API
            if ($fetchAll && empty($searchParams)) {
                $searchParams = ['fetch_all' => true];
            }

            $response = $this->makeRequest($searchParams);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    protected function areAllParamsEmpty(array $params): bool
    {
        return empty(array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        }));
    }

    protected function prepareSearchParams(array $params): array
    {
        $searchParams = array_filter($params, fn($value) => $value !== null && $value !== '');

        if (isset($searchParams['project'])) {
            $searchParams['project'] = strtoupper($searchParams['project']);
        }

        return $searchParams;
    }

    protected function makeRequest(array $params)
    {
        $fullUrl = $this->getFullUrl();

        Log::info('LOT Search Request', [
            'url' => $fullUrl,
            'params' => $params
        ]);

        return Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout)
            ->post($fullUrl, $params);
    }

    protected function handleResponse($response)
    {
        Log::info('LOT Search Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if (!$response->successful()) {
            return $this->handleErrorResponse($response);
        }

        $data = $response->json();

        if (isset($data['status']) && $data['status'] === 'error') {
            return [
                'success' => false,
                'message' => $this->formatErrorMessage($data['message'] ?? 'Unknown error from LOT service')
            ];
        }

        return [
            'success' => true,
            'data' => $this->formatResponseData($data)
        ];
    }

    protected function formatResponseData(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) {
            return [];
        }

        // Return all original data from API to allow frontend to access complete information
        return $data['data'];
    }

    protected function handleErrorResponse($response)
    {
        $errorMessage = match ($response->status()) {
            404 => 'LOT data not found. Make sure you have the right search parameters and try again.',
            500 => 'System error occurred. Please try again later.',
            401, 403 => 'You do not have access to this service.',
            408 => 'Request timeout. Please try again.',
            default => 'An error occurred while searching LOT data.'
        };

        // Log error details for debugging
        Log::error('LOT API Error', [
            'status' => $response->status(),
            'body' => $response->body(),
            'url' => $this->getFullUrl()
        ]);

        return [
            'success' => false,
            'message' => $errorMessage
        ];
    }

    protected function handleException(\Exception $e)
    {
        Log::error('LOT API Exception', [
            'message' => $e->getMessage(),
            'url' => $this->getFullUrl(),
            'trace' => $e->getTraceAsString()
        ]);

        $errorMessage = match (true) {
            str_contains($e->getMessage(), 'Connection refused') => 'Unable to connect to LOT server. Please try again later.',
            str_contains($e->getMessage(), 'timeout') => 'Request timeout. Please try again.',
            str_contains($e->getMessage(), 'SSL') => 'Security connection issue. Please contact administrator.',
            default => 'An error occurred while searching LOT data. Please try again.'
        };

        return [
            'success' => false,
            'message' => $errorMessage
        ];
    }

    protected function formatErrorMessage(string $message): string
    {
        // Remove technical details from error messages
        $message = preg_replace('/SQLSTATE\[.*?\]:/', '', $message);
        $message = preg_replace('/\(SQL:.*?\)/', '', $message);
        $message = trim($message);

        // Map common technical errors to user-friendly messages
        return match (true) {
            str_contains($message, 'not found') => 'LOT data not found.',
            str_contains($message, 'duplicate') => 'LOT data already exists.',
            str_contains($message, 'invalid') => 'Invalid input data.',
            default => 'An error occurred while searching LOT data.'
        };
    }
}
