<?php

namespace App\Services;

use App\Models\Account;
use App\Models\SapAccount;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
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

        $unitUdf = $this->normalizeUnitUdfColumn(
            (string) config('services.sap.account_statement.unit_udf', '')
        );
        $rows = $this->fetchAccountJournalLinesViaOdata($accountCode, $dateFrom, $dateTo, $unitUdf);

        return array_map(static function (array $row): array {
            return [
                'doc_date' => $row['posting_date'] ?? null,
                'posting_date' => $row['posting_date'] ?? null,
                'doc_num' => (string) ($row['doc_num'] ?? ''),
                'ref_doc_num' => (string) ($row['tx_num'] ?? ''),
                'transaction_id' => (string) ($row['tx_num'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'project_code' => (string) ($row['project_code'] ?? ''),
                'debit' => (float) ($row['debit'] ?? 0),
                'credit' => (float) ($row['credit'] ?? 0),
            ];
        }, $rows);
    }

    /**
     * Account statement payload matching the former SAP-Bridge shape.
     *
     * @return array{
     *     account: array{id: int|null, code: string, name: string, account_type: string|null},
     *     start_date: string,
     *     end_date: string,
     *     opening_balance: float,
     *     closing_balance: float,
     *     transactions: array<int, array<string, mixed>>,
     *     summary: array{total_debit: float, total_credit: float, transaction_count: int}
     * }
     */
    public function getAccountStatement(string $accountCode, string $startDate, string $endDate): array
    {
        $accountCode = trim($accountCode);
        if ($accountCode === '') {
            throw new \InvalidArgumentException('Account code is required.');
        }

        $mode = strtolower((string) config('services.sap.account_statement.mode', 'auto'));

        if ($mode === 'sql') {
            return $this->getAccountStatementViaSql($accountCode, $startDate, $endDate);
        }

        if ($mode === 'odata') {
            return $this->getAccountStatementViaOdata($accountCode, $startDate, $endDate);
        }

        if ($this->sqlQueriesAvailable()) {
            try {
                return $this->getAccountStatementViaSql($accountCode, $startDate, $endDate);
            } catch (\Throwable $exception) {
                Log::warning('SAP SQLQueries account statement failed, falling back to OData', [
                    'account_code' => $accountCode,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $this->getAccountStatementViaOdata($accountCode, $startDate, $endDate);
    }

    /**
     * Probe whether Service Layer SQLQueries is usable (for artisan diagnostics).
     *
     * @return array{available: bool, message: string, mode: string}
     */
    public function probeSqlQueries(): array
    {
        $mode = (string) config('services.sap.account_statement.mode', 'auto');

        try {
            $this->ensureSession();
            $this->ensureSqlQuery(
                'AO_PROBE',
                'AccountingOne SQLQueries probe',
                'SELECT "TransId" FROM "OJDT" WHERE 1 = 0'
            );
            $this->executeSqlQuery('AO_PROBE', []);
            Cache::forever('sap.sql_queries_available', true);

            return [
                'available' => true,
                'message' => 'SQLQueries endpoint is available and executable.',
                'mode' => $mode,
            ];
        } catch (\Throwable $exception) {
            Cache::forever('sap.sql_queries_available', false);

            return [
                'available' => false,
                'message' => $exception->getMessage(),
                'mode' => $mode,
            ];
        }
    }

    protected function sqlQueriesAvailable(): bool
    {
        if (Cache::has('sap.sql_queries_available')) {
            return (bool) Cache::get('sap.sql_queries_available');
        }

        $result = $this->probeSqlQueries();

        return $result['available'];
    }

    /**
     * @return array{
     *     account: array{id: int|null, code: string, name: string, account_type: string|null},
     *     start_date: string,
     *     end_date: string,
     *     opening_balance: float,
     *     closing_balance: float,
     *     transactions: array<int, array<string, mixed>>,
     *     summary: array{total_debit: float, total_credit: float, transaction_count: int}
     * }
     */
    protected function getAccountStatementViaSql(string $accountCode, string $startDate, string $endDate): array
    {
        $this->ensureSession();

        $unitUdf = $this->normalizeUnitUdfColumn(
            (string) config('services.sap.account_statement.unit_udf', '')
        );

        $this->ensureSqlQuery(
            'AO_OPEN3',
            'AccountingOne account opening balance',
            'SELECT SUM(Debit) AS TotalDebit, SUM(Credit) AS TotalCredit'
            .' FROM JDT1 WHERE Account = :accountCode AND RefDate < :startDate'
        );

        // Lines from OJDT/JDT1 only — OIGE/ODLN are not accessible via SQLQueries on this SL.
        // unit_no is enriched afterwards from InventoryGenExits / DeliveryNotes OData.
        $txSqlCode = $this->ensureAccountStatementLinesSql();
        $rawRows = $this->executeSqlQuery($txSqlCode, [
            'accountCode' => $accountCode,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $openingRows = $this->executeSqlQuery('AO_OPEN3', [
            'accountCode' => $accountCode,
            'startDate' => $startDate,
        ]);
        $openingBalance = (float) ($openingRows[0]['TotalDebit'] ?? 0) - (float) ($openingRows[0]['TotalCredit'] ?? 0);

        $unitMap = $this->buildUnitNoMap($rawRows, $unitUdf);

        $transactions = [];
        $running = $openingBalance;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($rawRows as $index => $row) {
            $debit = (float) ($row['DebitAmount'] ?? 0);
            $credit = (float) ($row['CreditAmount'] ?? 0);

            if ($debit === 0.0 && $credit === 0.0) {
                continue;
            }

            $running += $debit - $credit;
            $totalDebit += $debit;
            $totalCredit += $credit;

            $lineMemo = trim((string) ($row['LineMemo'] ?? ''));
            $headerMemo = trim((string) ($row['HeaderMemo'] ?? ''));
            $description = trim($lineMemo.($lineMemo !== '' && $headerMemo !== '' ? ' ' : '').$headerMemo);
            $docNum = isset($row['DocNum']) && $row['DocNum'] !== '' && $row['DocNum'] !== null
                ? (string) $row['DocNum']
                : null;
            $transType = $this->normalizeTransTypeCode(isset($row['DocType']) ? (string) $row['DocType'] : null);

            $transactions[] = [
                'id' => $index + 1,
                'posting_date' => $this->normalizeSapDate(isset($row['PostingDate']) ? (string) $row['PostingDate'] : null),
                'doc_num' => $docNum,
                'doc_type' => $this->mapDocTypeLabel($transType),
                'tx_num' => isset($row['TxNum']) ? (string) $row['TxNum'] : null,
                'description' => $description !== '' ? $description : null,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'project_code' => isset($row['ProjectCode']) && $row['ProjectCode'] !== '' ? (string) $row['ProjectCode'] : null,
                'department_name' => null,
                'unit_no' => $this->lookupUnitNo($unitMap, $transType, $docNum),
                'running_balance' => $running,
            ];
        }

        return $this->buildAccountStatementPayload(
            $accountCode,
            $startDate,
            $endDate,
            $openingBalance,
            $transactions,
            $totalDebit,
            $totalCredit
        );
    }

    /**
     * @return array{
     *     account: array{id: int|null, code: string, name: string, account_type: string|null},
     *     start_date: string,
     *     end_date: string,
     *     opening_balance: float,
     *     closing_balance: float,
     *     transactions: array<int, array<string, mixed>>,
     *     summary: array{total_debit: float, total_credit: float, transaction_count: int}
     * }
     */
    protected function getAccountStatementViaOdata(string $accountCode, string $startDate, string $endDate): array
    {
        $this->ensureSession();

        $unitUdf = $this->normalizeUnitUdfColumn(
            (string) config('services.sap.account_statement.unit_udf', '')
        );
        $configuredLookback = trim((string) config('services.sap.account_statement.odata_lookback_start', ''));
        $lookbackStart = $configuredLookback !== ''
            ? $configuredLookback
            : \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $startDate)->subYear()->format('Y-m-d');

        $openingBalance = $this->sumAccountMovementViaOdata($accountCode, $lookbackStart, $startDate, exclusiveEnd: true);

        $periodRows = $this->fetchAccountJournalLinesViaOdata($accountCode, $startDate, $endDate, $unitUdf);

        $transactions = [];
        $running = $openingBalance;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($periodRows as $index => $row) {
            $debit = (float) ($row['debit'] ?? 0);
            $credit = (float) ($row['credit'] ?? 0);

            if ($debit === 0.0 && $credit === 0.0) {
                continue;
            }

            $running += $debit - $credit;
            $totalDebit += $debit;
            $totalCredit += $credit;

            $transactions[] = [
                'id' => $index + 1,
                'posting_date' => $row['posting_date'] ?? null,
                'doc_num' => $row['doc_num'] !== '' ? $row['doc_num'] : null,
                'doc_type' => $this->mapDocTypeLabel($row['doc_type'] !== '' ? $row['doc_type'] : null),
                'tx_num' => $row['tx_num'] !== '' ? $row['tx_num'] : null,
                'description' => $row['description'] !== '' ? $row['description'] : null,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'project_code' => $row['project_code'] !== '' ? $row['project_code'] : null,
                'department_name' => null,
                // OData path cannot join OIGE/ODLN; unit_no stays empty here (SQL path is primary).
                'unit_no' => $row['unit_no'] !== '' ? $row['unit_no'] : null,
                'running_balance' => $running,
            ];
        }

        return $this->buildAccountStatementPayload(
            $accountCode,
            $startDate,
            $endDate,
            $openingBalance,
            $transactions,
            $totalDebit,
            $totalCredit
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array{
     *     account: array{id: int|null, code: string, name: string, account_type: string|null},
     *     start_date: string,
     *     end_date: string,
     *     opening_balance: float,
     *     closing_balance: float,
     *     transactions: array<int, array<string, mixed>>,
     *     summary: array{total_debit: float, total_credit: float, transaction_count: int}
     * }
     */
    protected function buildAccountStatementPayload(
        string $accountCode,
        string $startDate,
        string $endDate,
        float $openingBalance,
        array $transactions,
        float $totalDebit,
        float $totalCredit
    ): array {
        $closingBalance = $openingBalance + $totalDebit - $totalCredit;

        $transactions = array_map(static function (array $row): array {
            $row['debit_amount'] = round((float) ($row['debit_amount'] ?? 0), 2);
            $row['credit_amount'] = round((float) ($row['credit_amount'] ?? 0), 2);
            $row['running_balance'] = round((float) ($row['running_balance'] ?? 0), 2);

            return $row;
        }, array_values($transactions));

        return [
            'account' => $this->resolveAccountMeta($accountCode),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => round($openingBalance, 2),
            'closing_balance' => round($closingBalance, 2),
            'transactions' => $transactions,
            'summary' => [
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'transaction_count' => count($transactions),
            ],
        ];
    }

    /**
     * @return array{id: int|null, code: string, name: string, account_type: string|null}
     */
    protected function resolveAccountMeta(string $accountCode): array
    {
        $sapAccount = SapAccount::query()->where('code', $accountCode)->first();
        if ($sapAccount !== null) {
            return [
                'id' => $sapAccount->id,
                'code' => $sapAccount->code,
                'name' => (string) $sapAccount->name,
                'account_type' => $sapAccount->account_type,
            ];
        }

        $account = Account::query()->where('account_number', $accountCode)->first();

        return [
            'id' => $account?->id,
            'code' => $accountCode,
            'name' => (string) ($account?->account_name ?? $accountCode),
            'account_type' => $account?->type,
        ];
    }

    protected function ensureSqlQuery(string $sqlCode, string $sqlName, string $sqlText): void
    {
        $this->handleSessionExpiration(function () use ($sqlCode, $sqlName, $sqlText) {
            try {
                $this->client->get("SQLQueries('{$sqlCode}')");

                return;
            } catch (RequestException $exception) {
                $status = $exception->getResponse()?->getStatusCode();
                if ($status !== 404) {
                    throw new \Exception('SAP B1 Error: '.$this->extractErrorMessage($exception), 0, $exception);
                }
            }

            try {
                $this->client->post('SQLQueries', [
                    'json' => [
                        'SqlCode' => $sqlCode,
                        'SqlName' => $sqlName,
                        'SqlText' => $sqlText,
                    ],
                ]);
            } catch (RequestException $exception) {
                throw new \Exception('SAP B1 Error: '.$this->extractErrorMessage($exception), 0, $exception);
            }
        });
    }

    /**
     * @param  array<string, string>  $params
     * @return array<int, array<string, mixed>>
     */
    protected function executeSqlQuery(string $sqlCode, array $params): array
    {
        return $this->handleSessionExpiration(function () use ($sqlCode, $params) {
            $query = [];
            foreach ($params as $key => $value) {
                $query[$key] = "'".$value."'";
            }

            try {
                $response = $this->client->get("SQLQueries('{$sqlCode}')/List", [
                    'query' => $query,
                ]);
            } catch (RequestException $exception) {
                throw new \Exception('SAP B1 Error: '.$this->extractErrorMessage($exception), 0, $exception);
            }

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['value'] ?? (is_array($body) ? $body : []);
        });
    }

    protected function sumAccountMovementViaOdata(
        string $accountCode,
        string $dateFrom,
        string $dateTo,
        bool $exclusiveEnd = false
    ): float {
        // Large single-range expands are rejected by Service Layer; chunk by month.
        try {
            $cursor = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfMonth();
            $end = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $dateTo)->startOfDay();
        } catch (\Throwable) {
            return 0.0;
        }

        $sum = 0.0;

        while ($cursor->lt($end) || (! $exclusiveEnd && $cursor->equalTo($end->copy()->startOfMonth()))) {
            $chunkStart = $cursor->format('Y-m-d');
            $chunkEndDate = $cursor->copy()->endOfMonth()->startOfDay();

            if ($chunkEndDate->gte($end)) {
                if ($exclusiveEnd) {
                    $chunkEnd = $end->copy()->subDay()->format('Y-m-d');
                } else {
                    $chunkEnd = $end->format('Y-m-d');
                }
            } else {
                $chunkEnd = $chunkEndDate->format('Y-m-d');
            }

            if ($chunkStart <= $chunkEnd) {
                try {
                    $sum += $this->sumAccountMovementChunkViaOdata($accountCode, $chunkStart, $chunkEnd);
                } catch (\Throwable $exception) {
                    Log::warning('OData opening-balance chunk failed; continuing with partial sum', [
                        'account_code' => $accountCode,
                        'chunk_start' => $chunkStart,
                        'chunk_end' => $chunkEnd,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $cursor->addMonth();

            if ($cursor->year > $end->year + 1) {
                break;
            }
        }

        return $sum;
    }

    protected function sumAccountMovementChunkViaOdata(string $accountCode, string $dateFrom, string $dateTo): float
    {
        $filter = "ReferenceDate ge '{$dateFrom}' and ReferenceDate le '{$dateTo}'";
        $sum = 0.0;
        $skip = 0;
        $top = 100;

        do {
            // JournalEntryLines is a complex collection property (not a navigable expand) on this SL.
            $response = $this->handleSessionExpiration(function () use ($filter, $skip, $top) {
                return $this->client->get('JournalEntries', [
                    'query' => [
                        '$filter' => $filter,
                        '$top' => $top,
                        '$skip' => $skip,
                    ],
                ]);
            });

            $body = json_decode($response->getBody()->getContents(), true);
            $items = $body['value'] ?? [];

            foreach ($items as $journal) {
                foreach ($journal['JournalEntryLines'] ?? [] as $line) {
                    if (($line['AccountCode'] ?? '') !== $accountCode) {
                        continue;
                    }
                    $sum += (float) ($line['Debit'] ?? 0) - (float) ($line['Credit'] ?? 0);
                }
            }

            $count = count($items);
            $skip += $top;
        } while ($count === $top);

        return $sum;
    }

    /**
     * Accept AliasID (`MIS_UnitNo`) or physical column (`U_MIS_UnitNo`).
     */
    protected function normalizeUnitUdfColumn(string $unitUdf): string
    {
        $unitUdf = trim($unitUdf);
        if ($unitUdf === '') {
            return '';
        }

        if (! str_starts_with($unitUdf, 'U_')) {
            $unitUdf = 'U_'.$unitUdf;
        }

        if (! preg_match('/^U_[A-Za-z0-9_]+$/', $unitUdf)) {
            throw new \InvalidArgumentException('Invalid SAP_ACCOUNT_STATEMENT_UNIT_UDF value.');
        }

        return $unitUdf;
    }

    /**
     * Register the period-lines SQLQueries definition and return its SqlCode.
     *
     * unit_no is not selected here: OIGE/ODLN are not accessible via SQLQueries on this SL.
     * Enrichment uses OData InventoryGenExits / DeliveryNotes (see buildUnitNoMap).
     */
    protected function ensureAccountStatementLinesSql(): string
    {
        $txSqlCode = 'AO_TX5';

        $this->ensureSqlQuery(
            $txSqlCode,
            'AccountingOne account statement lines',
            'SELECT T0.BaseRef AS DocNum, T1.TransType AS DocType, T0.Memo AS HeaderMemo,'
            .' T1.TransId AS TxNum, T1.RefDate AS PostingDate, T1.LineMemo AS LineMemo,'
            .' T1.Project AS ProjectCode, T1.Debit AS DebitAmount, T1.Credit AS CreditAmount,'
            .' T1.Line_ID AS LineId'
            .' FROM OJDT T0 INNER JOIN JDT1 T1 ON T0.TransId = T1.TransId'
            .' WHERE T1.Account = :accountCode'
            .' AND T1.RefDate >= :startDate AND T1.RefDate <= :endDate'
            .' ORDER BY T1.RefDate, T1.TransId, T1.Line_ID'
        );

        return $txSqlCode;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawRows
     * @return array<string, string> keyed as "{transType}|{docNum}"
     */
    protected function buildUnitNoMap(array $rawRows, string $unitUdf): array
    {
        if ($unitUdf === '') {
            return [];
        }

        $giDocNums = [];
        $dlDocNums = [];

        foreach ($rawRows as $row) {
            $transType = $this->normalizeTransTypeCode(isset($row['DocType']) ? (string) $row['DocType'] : null);
            $docNum = isset($row['DocNum']) ? trim((string) $row['DocNum']) : '';
            if ($docNum === '' || $transType === null) {
                continue;
            }

            if ($transType === '60') {
                $giDocNums[$docNum] = true;
            } elseif ($transType === '15') {
                $dlDocNums[$docNum] = true;
            }
        }

        $map = [];
        foreach ($this->fetchUdfByDocNums('InventoryGenExits', array_keys($giDocNums), $unitUdf) as $docNum => $unitNo) {
            $map['60|'.$docNum] = $unitNo;
        }
        foreach ($this->fetchUdfByDocNums('DeliveryNotes', array_keys($dlDocNums), $unitUdf) as $docNum => $unitNo) {
            $map['15|'.$docNum] = $unitNo;
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $docNums
     * @return array<string, string> DocNum => unit value
     */
    protected function fetchUdfByDocNums(string $endpoint, array $docNums, string $unitUdf): array
    {
        if ($docNums === []) {
            return [];
        }

        $result = [];
        foreach (array_chunk($docNums, 20) as $chunk) {
            $filters = [];
            foreach ($chunk as $docNum) {
                if (is_numeric($docNum)) {
                    $filters[] = 'DocNum eq '.(int) $docNum;
                } else {
                    $filters[] = "DocNum eq '".str_replace("'", "''", $docNum)."'";
                }
            }

            try {
                $response = $this->handleSessionExpiration(function () use ($endpoint, $filters, $unitUdf) {
                    return $this->client->get($endpoint, [
                        'query' => [
                            '$filter' => implode(' or ', $filters),
                            '$select' => 'DocNum,'.$unitUdf,
                        ],
                    ]);
                });
            } catch (\Throwable $exception) {
                Log::warning('SAP unit_no OData lookup failed', [
                    'endpoint' => $endpoint,
                    'unit_udf' => $unitUdf,
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            $body = json_decode($response->getBody()->getContents(), true);
            foreach ($body['value'] ?? [] as $doc) {
                $docNum = isset($doc['DocNum']) ? trim((string) $doc['DocNum']) : '';
                $unitNo = isset($doc[$unitUdf]) ? trim((string) $doc[$unitUdf]) : '';
                if ($docNum !== '' && $unitNo !== '') {
                    $result[$docNum] = $unitNo;
                }
            }
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $unitMap
     */
    protected function lookupUnitNo(array $unitMap, ?string $transType, ?string $docNum): ?string
    {
        if ($transType === null || $docNum === null || $docNum === '') {
            return null;
        }

        return $unitMap[$transType.'|'.$docNum] ?? null;
    }

    protected function normalizeTransTypeCode(?string $transType): ?string
    {
        if ($transType === null || trim($transType) === '') {
            return null;
        }

        $code = trim($transType);
        if (is_numeric($code)) {
            return (string) (int) $code;
        }

        return $code;
    }

    /**
     * Map SAP B1 TransType codes to labels (same set as docs/je_daily.sql).
     */
    protected function mapDocTypeLabel(?string $transType): ?string
    {
        $code = $this->normalizeTransTypeCode($transType);
        if ($code === null) {
            return null;
        }

        $labels = [
            '-2' => 'Opening Balance',
            '13' => 'AR Invoice',
            '14' => 'AR Credit Memo',
            '203' => 'AR DP',
            '15' => 'Material Issue',
            '16' => 'Material Return',
            '18' => 'AP Invoice',
            '19' => 'AP Credit Memo',
            '204' => 'AP DP',
            '20' => 'Goods Receipt PO',
            '202' => 'Production Order',
            '21' => 'Goods Return',
            '24' => 'Incoming Payments',
            '30' => 'Journal Entry',
            '46' => 'Outgoing Payments',
            '59' => 'Goods Receipt',
            '60' => 'Goods Issue',
            '67' => 'InventoryTransfer',
            '69' => 'Landed Costs',
            '321' => 'Internal Reconciliation',
            '162' => 'Inventory Revaluation',
        ];

        return $labels[$code] ?? $transType;
    }

    protected function normalizeSapDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{8}$/', $value) === 1) {
            return substr($value, 0, 4).'-'.substr($value, 4, 2).'-'.substr($value, 6, 2);
        }

        return substr($value, 0, 10);
    }

    /**
     * @return array<int, array{posting_date: ?string, doc_num: string, doc_type: string, tx_num: string, description: string, project_code: string, debit: float, credit: float, unit_no: string}>
     */
    protected function fetchAccountJournalLinesViaOdata(
        string $accountCode,
        string $dateFrom,
        string $dateTo,
        string $unitUdf
    ): array {
        $lines = [];
        $skip = 0;
        $top = 100;
        $filter = "ReferenceDate ge '{$dateFrom}' and ReferenceDate le '{$dateTo}'";

        do {
            // Do not $expand JournalEntryLines — on this Service Layer it is a collection
            // property already returned on JournalEntries, and $expand is rejected.
            $response = $this->handleSessionExpiration(function () use ($filter, $skip, $top) {
                return $this->client->get('JournalEntries', [
                    'query' => [
                        '$filter' => $filter,
                        '$top' => $top,
                        '$skip' => $skip,
                    ],
                ]);
            });

            $body = json_decode($response->getBody()->getContents(), true);
            $items = $body['value'] ?? [];

            foreach ($items as $journal) {
                $refDate = $this->normalizeSapDate(
                    isset($journal['ReferenceDate'])
                        ? (string) $journal['ReferenceDate']
                        : (isset($journal['RefDate']) ? (string) $journal['RefDate'] : null)
                );
                // Prefer BaseRef-equivalent source doc number when SL exposes it; else JE Number.
                $docNum = trim((string) (
                    $journal['BaseReference']
                    ?? $journal['Reference']
                    ?? $journal['Number']
                    ?? $journal['DocNum']
                    ?? ''
                ));
                $docType = (string) (
                    $journal['OriginalJournal']
                    ?? $journal['TransactionCode']
                    ?? ''
                );
                $txNum = (string) ($journal['JdtNum'] ?? $journal['DocEntry'] ?? '');
                $memo = trim((string) ($journal['Memo'] ?? ''));

                foreach ($journal['JournalEntryLines'] ?? [] as $line) {
                    if (($line['AccountCode'] ?? '') !== $accountCode) {
                        continue;
                    }

                    $debit = (float) ($line['Debit'] ?? 0);
                    $credit = (float) ($line['Credit'] ?? 0);
                    if ($debit === 0.0 && $credit === 0.0) {
                        continue;
                    }

                    $lineMemo = trim((string) ($line['LineMemo'] ?? ''));
                    $description = trim($lineMemo.($lineMemo !== '' && $memo !== '' ? ' ' : '').$memo);
                    $projectCode = trim((string) ($line['ProjectCode'] ?? $line['ProfitCenter'] ?? $line['CostingCode'] ?? ''));

                    $lines[] = [
                        'posting_date' => $refDate,
                        'doc_num' => $docNum,
                        'doc_type' => $docType,
                        'tx_num' => $txNum,
                        'description' => $description,
                        'project_code' => $projectCode,
                        'debit' => $debit,
                        'credit' => $credit,
                        // unit_no requires OIGE/ODLN joins — only available on SQLQueries path.
                        'unit_no' => '',
                    ];
                }
            }

            $count = count($items);
            $skip += $top;
        } while ($count === $top);

        usort($lines, static function (array $a, array $b): int {
            return strcmp(
                ($a['posting_date'] ?? '').'|'.($a['tx_num'] ?? ''),
                ($b['posting_date'] ?? '').'|'.($b['tx_num'] ?? '')
            );
        });

        return $lines;
    }

    public function __destruct()
    {
        if ($this->isLoggedIn) {
            $this->logout();
        }
    }
}
