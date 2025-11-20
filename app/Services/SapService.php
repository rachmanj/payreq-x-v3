<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class SapService
{
    protected Client $client;
    protected CookieJar $cookieJar;
    protected array $config;
    protected bool $isLoggedIn = false;

    public function __construct()
    {
        $this->config = [
            'base_uri' => rtrim(config('services.sap.server_url'), '/') . '/',
            'db_name' => config('services.sap.db_name'),
            'user' => config('services.sap.user'),
            'password' => config('services.sap.password'),
        ];

        $this->cookieJar = new CookieJar();
        $this->client = new Client([
            'base_uri' => $this->config['base_uri'],
            'cookies' => $this->cookieJar,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'verify' => false,
            'timeout' => 30,
        ]);
    }

    public function login(): bool
    {
        try {
            $response = $this->client->post('Login', [
                'json' => [
                    'CompanyDB' => $this->config['db_name'],
                    'UserName' => $this->config['user'],
                    'Password' => $this->config['password'],
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $this->isLoggedIn = true;
                Log::info('SAP B1 login successful', [
                    'company_db' => $this->config['db_name'],
                    'user' => $this->config['user'],
                ]);
                return true;
            }

            return false;
        } catch (RequestException $e) {
            $this->isLoggedIn = false;
            $errorMessage = $e->getResponse() 
                ? $e->getResponse()->getBody()->getContents() 
                : $e->getMessage();
            
            Log::error('SAP B1 login failed', [
                'error' => $errorMessage,
                'company_db' => $this->config['db_name'],
                'user' => $this->config['user'],
            ]);

            throw new \Exception('SAP B1 login failed: ' . $errorMessage);
        }
    }

    public function logout(): void
    {
        try {
            $this->client->post('Logout');
            $this->isLoggedIn = false;
            Log::info('SAP B1 logout successful');
        } catch (RequestException $e) {
            Log::warning('SAP B1 logout failed', ['error' => $e->getMessage()]);
        }
    }

    public function hasValidSession(): bool
    {
        return $this->isLoggedIn && $this->cookieJar->count() > 0;
    }

    public function ensureSession(): void
    {
        if (!$this->hasValidSession()) {
            $this->login();
        }
    }

    protected function handleSessionExpiration(callable $callback)
    {
        try {
            return $callback();
        } catch (RequestException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                Log::warning('SAP B1 session expired, re-logging in');
                $this->isLoggedIn = false;
                $this->login();
                return $callback();
            }
            throw $e;
        }
    }

    public function createJournalEntry(array $journalData): array
    {
        $this->ensureSession();

        return $this->handleSessionExpiration(function () use ($journalData) {
            try {
                Log::debug('SAP B1 Journal Entry Request', ['payload' => $journalData]);
                
                $response = $this->client->post('JournalEntries', [
                    'json' => $journalData,
                ]);

                $statusCode = $response->getStatusCode();
                $body = json_decode($response->getBody()->getContents(), true);

                if ($statusCode === 201) {
                    Log::info('SAP B1 journal entry created successfully', [
                        'journal_entry' => $body['DocEntry'] ?? null,
                        'journal_number' => $body['JournalEntry']['JournalEntryLines'][0]['Line_ID'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'doc_entry' => $body['DocEntry'] ?? null,
                        'journal_number' => $this->extractJournalNumber($body),
                        'data' => $body,
                    ];
                }

                $errorMessage = $body['error']['message']['value'] ?? 'Unknown error';
                throw new \Exception('Failed to create journal entry. Status: ' . $statusCode . '. Error: ' . $errorMessage);
            } catch (RequestException $e) {
                $errorMessage = 'Unknown error';
                if ($e->getResponse()) {
                    $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                    if (isset($errorBody['error']['message']['value'])) {
                        $errorMessage = $errorBody['error']['message']['value'];
                    } elseif (isset($errorBody['error']['message'])) {
                        $errorMessage = is_array($errorBody['error']['message']) 
                            ? ($errorBody['error']['message']['value'] ?? json_encode($errorBody['error']['message']))
                            : $errorBody['error']['message'];
                    }
                }
                throw new \Exception('SAP B1 Error: ' . $errorMessage, 0, $e);
            }
        });
    }

    protected function extractJournalNumber(array $response): ?string
    {
        // Priority: Document Number (Number) is the SAP Journal Number
        if (isset($response['Number'])) {
            return (string) $response['Number'];
        }

        if (isset($response['JournalEntry']['Number'])) {
            return (string) $response['JournalEntry']['Number'];
        }

        // Fallback to JdtNum if Number is not available
        if (isset($response['JdtNum'])) {
            return (string) $response['JdtNum'];
        }

        if (isset($response['JournalEntry']['JdtNum'])) {
            return (string) $response['JournalEntry']['JdtNum'];
        }

        if (isset($response['DocEntry'])) {
            return (string) $response['DocEntry'];
        }

        return null;
    }

    public function __destruct()
    {
        if ($this->isLoggedIn) {
            $this->logout();
        }
    }
}

