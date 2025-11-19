<?php

namespace App\Services\SapBridge;

use App\Exceptions\SapBridgeException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AccountStatementService
{
    protected ?string $baseUrl;
    protected ?string $apiKey;
    protected int $timeout;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null, ?int $timeout = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? config('services.sap_bridge.url'), '/');
        $this->apiKey = $apiKey ?? config('services.sap_bridge.api_key');
        $this->timeout = $timeout ?? (int) config('services.sap_bridge.timeout', 30);
    }

    public function getAccountStatement(string $accountCode, string $startDate, string $endDate): array
    {
        if (blank($this->baseUrl) || blank($this->apiKey)) {
            throw new SapBridgeException('SAP Bridge service is not configured properly.');
        }

        $endpoint = "{$this->baseUrl}/api/account-statements";

        try {
            $response = Http::acceptJson()
                ->timeout($this->timeout)
                ->withHeaders([
                    'x-sap-bridge-api-key' => $this->apiKey,
                ])
                ->get($endpoint, [
                    'account_code' => $accountCode,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);
        } catch (ConnectionException $exception) {
            throw new SapBridgeException('Unable to reach SAP Bridge service.', 503, null, $exception);
        }

        if ($response->successful()) {
            return $response->json();
        }

        $payload = $response->json();
        $message = data_get($payload, 'message', 'SAP Bridge request failed.');

        throw new SapBridgeException($message, $response->status(), $payload);
    }
}

