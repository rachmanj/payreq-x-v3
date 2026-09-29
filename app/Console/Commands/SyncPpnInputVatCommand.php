<?php

namespace App\Console\Commands;

use App\Services\PpnInputVatSyncService;
use Illuminate\Console\Command;

class SyncPpnInputVatCommand extends Command
{
    protected $signature = 'ppn:sync-input-vat
                            {--days=60 : Lookback window in days for faktur pajak / RefDate filter}
                            {--triggered-by= : User id when started manually from the UI}';

    protected $description = 'Pull PPN Masukan (11603001) lines from SAP B1, upsert to ppn_input_sync, and create purchase fakturs';

    public function handle(PpnInputVatSyncService $syncService): int
    {
        $days = (int) $this->option('days');
        $triggeredBy = $this->option('triggered-by');
        $userId = $triggeredBy !== null && $triggeredBy !== '' ? (int) $triggeredBy : null;

        $this->info("Starting PPN input VAT sync (lookback {$days} days)...");

        $run = $syncService->sync($days, $userId);

        if ($run->status === 'success') {
            $this->info($run->message ?? 'Sync completed.');

            return self::SUCCESS;
        }

        $this->error($run->message ?? 'Sync failed.');

        return self::FAILURE;
    }
}
