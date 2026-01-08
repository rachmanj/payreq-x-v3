<?php

namespace App\Console\Commands;

use App\Services\SapMasterDataSyncService;
use Illuminate\Console\Command;

class SyncSapMasterData extends Command
{
    protected $signature = 'sap:sync-master-data 
        {--projects : Sync SAP Projects} 
        {--cost-centers : Sync SAP Cost Centers} 
        {--accounts : Sync SAP GL Accounts}
        {--business-partners : Sync SAP Business Partners}';

    protected $description = 'Sync Projects, Cost Centers, Accounts, and Business Partners from SAP B1 Service Layer';

    public function __construct(
        protected SapMasterDataSyncService $syncService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting SAP master data synchronization...');

        $targets = $this->determineTargets();

        foreach ($targets as $target) {
            $this->line('');
            $this->comment("Syncing {$target['label']}...");

            $result = call_user_func([$this->syncService, $target['method']]);

            $this->info("✔ {$target['label']} synced: {$result['synced']} record(s)");

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->error("  • {$error}");
                }
            }
        }

        $this->info('');
        $this->info('SAP master data synchronization completed.');

        return Command::SUCCESS;
    }

    protected function determineTargets(): array
    {
        $targets = [];

        if ($this->option('projects')) {
            $targets[] = ['label' => 'Projects', 'method' => 'syncProjects'];
        }

        if ($this->option('cost-centers')) {
            $targets[] = ['label' => 'Cost Centers', 'method' => 'syncCostCenters'];
        }

        if ($this->option('accounts')) {
            $targets[] = ['label' => 'Accounts', 'method' => 'syncAccounts'];
        }

        if ($this->option('business-partners')) {
            $targets[] = ['label' => 'Business Partners', 'method' => 'syncBusinessPartners'];
        }

        if (empty($targets)) {
            return [
                ['label' => 'Projects', 'method' => 'syncProjects'],
                ['label' => 'Cost Centers', 'method' => 'syncCostCenters'],
                ['label' => 'Accounts', 'method' => 'syncAccounts'],
                ['label' => 'Business Partners', 'method' => 'syncBusinessPartners'],
            ];
        }

        return $targets;
    }
}
