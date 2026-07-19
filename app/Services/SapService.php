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
            'base_uri' => rtrim(config('services.sap.server_url'), '/').'/',
            'db_name' => config('services.sap.db_name'),
            'user' => config('services.sap.user'),
            'password' => config('services.sap.password'),
        ];

        $this->cookieJar = new CookieJar;
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

            throw new \Exception('SAP B1 login failed: '.$errorMessage);
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
        if (! $this->hasValidSession()) {
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
                throw new \Exception('Failed to create journal entry. Status: '.$statusCode.'. Error: '.$errorMessage);
            } catch (RequestException $e) {
                throw new \Exception('SAP B1 Error: '.$this->extractErrorMessage($e), 0, $e);
            }
        });
    }

    public function cancelJournalEntry(string $jdtNum): array
    {
        $this->ensureSession();

        return $this->handleSessionExpiration(function () use ($jdtNum) {
            try {
                Log::debug('SAP B1 Journal Entry Cancel Request', ['jdt_num' => $jdtNum]);

                $response = $this->client->post('JournalEntries('.$jdtNum.')/Cancel');

                $statusCode = $response->getStatusCode();

                if (! in_array($statusCode, [200, 204], true)) {
                    $body = json_decode($response->getBody()->getContents(), true);
                    $errorMessage = $body['error']['message']['value'] ?? 'Unknown error';
                    throw new \Exception('Failed to cancel journal entry. Status: '.$statusCode.'. Error: '.$errorMessage);
                }

                Log::info('SAP B1 journal entry cancelled successfully', [
                    'jdt_num' => $jdtNum,
                ]);

                $reversalJournalNo = $this->findReversalJournalNumber($jdtNum);

                return [
                    'success' => true,
                    'jdt_num' => $jdtNum,
                    'reversal_journal_no' => $reversalJournalNo,
                ];
            } catch (RequestException $e) {
                throw new \Exception('SAP B1 Error: '.$this->extractErrorMessage($e), 0, $e);
            }
        });
    }

    protected function findReversalJournalNumber(string $jdtNum): ?string
    {
        try {
            $response = $this->client->get('JournalEntries', [
                'query' => [
                    '$filter' => 'StornoToTr eq '.$jdtNum,
                    '$top' => 1,
                    '$select' => 'Number,JdtNum,StornoToTr',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $items = $body['value'] ?? [];

            if (! empty($items[0])) {
                return $this->extractJournalNumber($items[0]);
            }
        } catch (\Throwable $e) {
            Log::warning('Could not look up SAP B1 reversal journal number', [
                'jdt_num' => $jdtNum,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function extractErrorMessage(RequestException $e): string
    {
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

        return $errorMessage;
    }

    public function getProjects(): array
    {
        $this->ensureSession();

        return $this->handleSessionExpiration(function () {
            $response = $this->client->post('ProjectsService_GetProjectList', [
                'json' => new \stdClass,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            $items = $body['value'] ?? $body ?? [];

            return is_array($items) ? $items : [];
        });
    }

    public function getCostCenters(): array
    {
        return $this->fetchAll('ProfitCenters', [
            '$select' => 'CenterCode,CenterName,Active',
        ]);
    }

    public function getAccounts(): array
    {
        return $this->fetchAll('ChartOfAccounts');
    }

    public function getBusinessPartners(): array
    {
        return $this->fetchAll('BusinessPartners');
    }

    public function getItems(int $limit = 100): array
    {
        try {
            $this->ensureSession();

            $response = $this->handleSessionExpiration(function () use ($limit) {
                return $this->client->get('Items', [
                    'query' => [
                        '$select' => 'ItemCode,ItemName,ItemType',
                        '$top' => $limit,
                    ],
                ]);
            });

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['value'] ?? (is_array($body) ? $body : []);
        } catch (\Exception $e) {
            Log::warning('Failed to query SAP B1 for items', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getServiceItems(): array
    {
        try {
            $items = $this->getItems(100);

            // Filter for service items (ItemType = 'S') in PHP
            $serviceItems = array_filter($items, function ($item) {
                return isset($item['ItemType']) && $item['ItemType'] === 'S';
            });

            return array_values($serviceItems);
        } catch (\Exception $e) {
            // Log the error but don't throw - allow fallback to configured default
            Log::warning('Failed to query SAP B1 for service items', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function fetchAll(string $endpoint, array $query = [], int $pageSize = 100): array
    {
        $this->ensureSession();

        $results = [];
        $skip = 0;

        do {
            $response = $this->handleSessionExpiration(function () use ($endpoint, $query, $pageSize, $skip) {
                return $this->client->get($endpoint, [
                    'query' => array_merge($query, [
                        '$top' => $pageSize,
                        '$skip' => $skip,
                    ]),
                ]);
            });

            $body = json_decode($response->getBody()->getContents(), true);
            $items = $body['value'] ?? (is_array($body) ? $body : []);

            if (! is_array($items)) {
                $items = [];
            }

            $results = array_merge($results, $items);

            $fetched = count($items);
            $skip += $pageSize;
        } while ($fetched === $pageSize);

        return $results;
    }

    public function createArInvoice(array $invoiceData): array
    {
        $this->ensureSession();

        return $this->handleSessionExpiration(function () use ($invoiceData) {
            try {
                Log::debug('SAP B1 AR Invoice Request', ['payload' => $invoiceData]);

                $response = $this->client->post('Invoices', [
                    'json' => $invoiceData,
                ]);

                $statusCode = $response->getStatusCode();
                $body = json_decode($response->getBody()->getContents(), true);

                if ($statusCode === 201) {
                    Log::info('SAP B1 AR Invoice created successfully', [
                        'doc_entry' => $body['DocEntry'] ?? null,
                        'doc_num' => $body['DocNum'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'doc_entry' => $body['DocEntry'] ?? null,
                        'doc_num' => $this->extractArInvoiceNumber($body),
                        'data' => $body,
                    ];
                }

                $errorMessage = $body['error']['message']['value'] ?? 'Unknown error';
                throw new \Exception('Failed to create AR Invoice. Status: '.$statusCode.'. Error: '.$errorMessage);
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
                throw new \Exception('SAP B1 Error: '.$errorMessage, 0, $e);
            }
        });
    }

    public function createApInvoice(array $invoiceData): array
    {
        $this->ensureSession();

        return $this->handleSessionExpiration(function () use ($invoiceData) {
            try {
                Log::debug('SAP B1 AP Invoice Request', ['payload' => $invoiceData]);

                $response = $this->client->post('PurchaseInvoices', [
                    'json' => $invoiceData,
                ]);

                $statusCode = $response->getStatusCode();
                $body = json_decode($response->getBody()->getContents(), true);

                if ($statusCode === 201) {
                    Log::info('SAP B1 AP Invoice created successfully', [
                        'doc_entry' => $body['DocEntry'] ?? null,
                        'doc_num' => $body['DocNum'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'doc_entry' => $body['DocEntry'] ?? null,
                        'doc_num' => $this->extractApInvoiceNumber($body),
                        'data' => $body,
                    ];
                }

                $errorMessage = $body['error']['message']['value'] ?? 'Unknown error';
                throw new \Exception('Failed to create AP Invoice. Status: '.$statusCode.'. Error: '.$errorMessage);
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
                throw new \Exception('SAP B1 Error: '.$errorMessage, 0, $e);
            }
        });
    }

    public function createOutgoingPayment(array $paymentData): array
    {
        $this->ensureSession();

        return $this->handleSessionExpiration(function () use ($paymentData) {
            try {
                Log::debug('SAP B1 Outgoing Payment Request', ['payload' => $paymentData]);

                $response = $this->client->post('Payments', [
                    'json' => $paymentData,
                ]);

                $statusCode = $response->getStatusCode();
                $body = json_decode($response->getBody()->getContents(), true);

                if ($statusCode === 201) {
                    Log::info('SAP B1 Outgoing Payment created successfully', [
                        'doc_entry' => $body['DocEntry'] ?? null,
                        'doc_num' => $body['DocNum'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'doc_entry' => $body['DocEntry'] ?? null,
                        'doc_num' => $this->extractPaymentNumber($body),
                        'data' => $body,
                    ];
                }

                $errorMessage = $body['error']['message']['value'] ?? 'Unknown error';
                throw new \Exception('Failed to create Outgoing Payment. Status: '.$statusCode.'. Error: '.$errorMessage);
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
                throw new \Exception('SAP B1 Error: '.$errorMessage, 0, $e);
            }
        });
    }

    protected function extractArInvoiceNumber(array $response): ?string
    {
        // Priority: DocNum is the SAP Document Number
        if (isset($response['DocNum'])) {
            return (string) $response['DocNum'];
        }

        if (isset($response['Invoice']['DocNum'])) {
            return (string) $response['Invoice']['DocNum'];
        }

        // Fallback to DocEntry
        if (isset($response['DocEntry'])) {
            return (string) $response['DocEntry'];
        }

        return null;
    }

    protected function extractApInvoiceNumber(array $response): ?string
    {
        if (isset($response['DocNum'])) {
            return (string) $response['DocNum'];
        }

        if (isset($response['PurchaseInvoice']['DocNum'])) {
            return (string) $response['PurchaseInvoice']['DocNum'];
        }

        if (isset($response['DocEntry'])) {
            return (string) $response['DocEntry'];
        }

        return null;
    }

    protected function extractPaymentNumber(array $response): ?string
    {
        if (isset($response['DocNum'])) {
            return (string) $response['DocNum'];
        }

        if (isset($response['Payment']['DocNum'])) {
            return (string) $response['Payment']['DocNum'];
        }

        if (isset($response['DocEntry'])) {
            return (string) $response['DocEntry'];
        }

        return null;
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

    /**
     * @return array<int, array{doc_date: ?string, posting_date: ?string, doc_num: string, ref_doc_num: string, transaction_id: string, description: string, project_code: string, debit: float, credit: float}>
     */
    public function getGLLines(string $accountCode, string $dateFrom, string $dateTo): array
    {
        $accountCode = trim($accountCode);
        if ($accountCode === '') {
            return [];
        }

        $this->ensureSession();

        $lines = [];
        $skip = 0;
        $top = 100;
        $filter = "RefDate ge '".$dateFrom."' and RefDate le '".$dateTo."'";

        do {
            $response = $this->handleSessionExpiration(function () use ($filter, $skip, $top) {
                return $this->client->get('JournalEntries', [
                    'query' => [
                        '$expand' => 'JournalEntryLines',
                        '$filter' => $filter,
                        '$top' => $top,
                        '$skip' => $skip,
                    ],
                ]);
            });

            $body = json_decode($response->getBody()->getContents(), true);
            $items = $body['value'] ?? [];

            foreach ($items as $journal) {
                $journalLines = $journal['JournalEntryLines'] ?? [];

                foreach ($journalLines as $line) {
                    if (($line['AccountCode'] ?? '') !== $accountCode) {
                        continue;
                    }

                    $debit = (float) ($line['Debit'] ?? 0);
                    $credit = (float) ($line['Credit'] ?? 0);

                    if ($debit === 0.0 && $credit === 0.0) {
                        continue;
                    }

                    $refDate = isset($journal['RefDate']) ? substr((string) $journal['RefDate'], 0, 10) : null;

                    $projectCode = (string) ($line['ProjectCode'] ?? $line['ProfitCenter'] ?? $line['CostingCode'] ?? '');

                    $lines[] = [
                        'doc_date' => $refDate,
                        'posting_date' => $refDate,
                        'doc_num' => (string) ($journal['DocNum'] ?? ''),
                        'ref_doc_num' => trim((string) ($journal['Reference'] ?? '').' '.(string) ($journal['Reference2'] ?? '')),
                        'transaction_id' => (string) ($journal['JdtNum'] ?? $journal['DocEntry'] ?? ''),
                        'description' => trim((string) ($line['LineMemo'] ?? '').(($line['LineMemo'] ?? '') !== '' ? ' ' : '').(string) ($journal['Memo'] ?? '')),
                        'project_code' => trim($projectCode),
                        'debit' => $debit,
                        'credit' => $credit,
                    ];
                }
            }

            $count = count($items);
            $skip += $top;
        } while ($count === $top);

        return $lines;
    }

    public function __destruct()
    {
        if ($this->isLoggedIn) {
            $this->logout();
        }
    }
}
