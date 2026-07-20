<?php

namespace App\Console\Commands;

use App\Services\SapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ProbeSapSqlQueriesCommand extends Command
{
    protected $signature = 'sap:probe-sql-queries
                            {--forget-cache : Clear the cached SQLQueries availability flag before probing}';

    protected $description = 'Probe whether SAP B1 Service Layer SQLQueries is available for account statements';

    public function handle(SapService $sapService): int
    {
        if ($this->option('forget-cache')) {
            Cache::forget('sap.sql_queries_available');
            $this->info('Cleared cache key sap.sql_queries_available.');
        }

        $mode = (string) config('services.sap.account_statement.mode', 'auto');
        $this->line("Configured account statement mode: {$mode}");

        $result = $sapService->probeSqlQueries();

        if ($result['available']) {
            $this->info($result['message']);
            $this->line('Recommendation: keep SAP_ACCOUNT_STATEMENT_MODE=auto (or sql).');

            return self::SUCCESS;
        }

        $this->error('SQLQueries is not available: '.$result['message']);
        $this->warn('Recommendation: set SAP_ACCOUNT_STATEMENT_MODE=odata until SQLQueries is enabled.');

        return self::FAILURE;
    }
}
