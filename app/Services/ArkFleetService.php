<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArkFleetService
{
    public function getConfiguredBaseUrl(): ?string
    {
        $url = config('services.ark_fleet.url_equipments');

        return is_string($url) && $url !== '' ? $url : null;
    }

    public function buildActiveEquipmentsUrl(?string $baseUrl = null): ?string
    {
        $baseUrl = $baseUrl ?? $this->getConfiguredBaseUrl();

        if ($baseUrl === null) {
            return null;
        }

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.'status=ACTIVE';
    }

    /**
     * @return array{
     *     success: bool,
     *     count: int,
     *     message: string,
     *     debug: array<string, mixed>
     * }
     */
    public function fetchActiveEquipmentCount(int $timeout = 10): array
    {
        $startedAt = microtime(true);
        $baseUrl = $this->getConfiguredBaseUrl();
        $requestUrl = $this->buildActiveEquipmentsUrl($baseUrl);

        $debug = [
            'configured_base_url' => $baseUrl,
            'request_url' => $requestUrl,
            'config_cached' => app()->configurationIsCached(),
            'app_env' => config('app.env'),
            'timeout_seconds' => $timeout,
            'php_version' => PHP_VERSION,
            'curl_enabled' => extension_loaded('curl'),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
        ];

        if ($requestUrl === null) {
            return [
                'success' => false,
                'count' => 0,
                'message' => 'URL_EQUIPMENTS is not configured. Set it in .env (e.g. http://192.168.33.15/ark-fleet/api/equipments), then run php artisan config:cache on production.',
                'debug' => $debug,
            ];
        }

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout($timeout)
                ->acceptJson()
                ->get($requestUrl);

            $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
            $body = $response->body();

            $debug['http_status'] = $response->status();
            $debug['duration_ms'] = $elapsedMs;
            $debug['response_size_bytes'] = strlen($body);
            $debug['response_preview'] = $this->truncate($body, 500);

            if (! $response->successful()) {
                Log::warning('ARK-Fleet non-success response', $debug);

                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'ARK-Fleet API returned HTTP '.$response->status().'.',
                    'debug' => $debug,
                ];
            }

            $data = $response->json();

            if (! is_array($data)) {
                $debug['json_error'] = 'Response is not a JSON object';

                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'Invalid JSON from ARK-Fleet API.',
                    'debug' => $debug,
                ];
            }

            $equipmentsData = $data['data'] ?? [];
            $count = count($equipmentsData);

            if ($count === 0 && isset($data['count']) && $data['count'] > 0) {
                $count = (int) $data['count'];
            }

            $debug['api_count_field'] = $data['count'] ?? null;
            $debug['data_array_length'] = count($equipmentsData);
            $debug['response_keys'] = array_keys($data);

            Log::info('ARK-Fleet equipment count fetched', [
                'url' => $requestUrl,
                'count' => $count,
                'duration_ms' => $elapsedMs,
            ]);

            return [
                'success' => true,
                'count' => $count,
                'message' => "Fetched {$count} active equipment(s) from ARK-Fleet.",
                'debug' => $debug,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $debug = $this->appendExceptionDebug($debug, $e, $startedAt);

            Log::warning('ARK-Fleet connection failed', $debug);

            return [
                'success' => false,
                'count' => 0,
                'message' => 'Unable to connect to ARK-Fleet API at '.$baseUrl.'. Check network access, firewall, and that the ARK-Fleet server is running.',
                'debug' => $debug,
            ];
        } catch (\Exception $e) {
            $debug = $this->appendExceptionDebug($debug, $e, $startedAt);

            Log::error('ARK-Fleet unexpected error', $debug);

            return [
                'success' => false,
                'count' => 0,
                'message' => 'Failed to fetch equipment data from ARK-Fleet API: '.$e->getMessage(),
                'debug' => $debug,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchActiveEquipmentsData(int $timeout = 30): array
    {
        $result = $this->fetchActiveEquipmentCount($timeout);

        if (! $result['success']) {
            return $result;
        }

        $requestUrl = $result['debug']['request_url'] ?? $this->buildActiveEquipmentsUrl();

        if ($requestUrl === null) {
            return $result;
        }

        try {
            $response = Http::timeout($timeout)->acceptJson()->get($requestUrl);
            $data = $response->json();
            $result['data'] = is_array($data) ? ($data['data'] ?? []) : [];

            return $result;
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = 'Failed to load equipment data for sync: '.$e->getMessage();

            return $result;
        }
    }

    /**
     * @param  array<string, mixed>  $debug
     * @return array<string, mixed>
     */
    private function appendExceptionDebug(array $debug, \Throwable $e, float $startedAt): array
    {
        $debug['exception'] = $e::class;
        $debug['error_message'] = $e->getMessage();
        $debug['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        return $debug;
    }

    private function truncate(string $value, int $max): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max).'...';
    }
}
