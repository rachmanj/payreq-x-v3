<?php

namespace App\Console\Commands;

use App\Models\RealizationDetail;
use App\Models\UtilityCustomer;
use Illuminate\Console\Command;

class BackfillUtilityAccount extends Command
{
    /**
     * Isi account_id yang kosong pada utility_customers sesuai jenis utilitas,
     * lalu update realization_detail utilitas yang sudah terlanjur dibuat dengan
     * account_id null (dari backfill reimburse) supaya ter-mapping ke akun GL.
     *
     * Mapping akun (harus sudah ada di tabel accounts):
     *   pln    -> 144  (61208001 Electricity)
     *   pdam   -> 145  (61208002 Water)
     *   telkom -> 146  (61208003 Cable and Satelite TV)
     */
    protected $signature = 'utility:backfill-account
                            {--dry-run : Preview saja, tidak menyimpan apa pun}';

    protected $description = 'Isi account_id kosong di utility_customers + realization_detail utilitas sesuai jenis';

    protected array $accountByJenis = [
        'pln' => 144,
        'pdam' => 145,
        'telkom' => 146,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->line('');
        if ($dryRun) {
            $this->warn('MODE DRY-RUN: tidak ada yang disimpan.');
        }
        $this->line('');

        // 1. Customer
        $this->info('utility_customers (account_id kosong):');
        foreach ($this->accountByJenis as $jenis => $accountId) {
            $count = UtilityCustomer::where('jenis_utilitas', $jenis)
                ->whereNull('account_id')
                ->count();

            $this->line(sprintf('  %-8s -> akun %d : %d customer', strtoupper($jenis), $accountId, $count));

            if (! $dryRun && $count > 0) {
                UtilityCustomer::where('jenis_utilitas', $jenis)
                    ->whereNull('account_id')
                    ->update(['account_id' => $accountId]);
            }
        }

        // 2. Realization detail utilitas (account_id kosong)
        $this->line('');
        $this->info('realization_details utilitas (account_id kosong):');
        foreach ($this->accountByJenis as $jenis => $accountId) {
            $upper = strtoupper($jenis);
            $query = RealizationDetail::whereNull('account_id')
                ->where(function ($q) use ($upper) {
                    $q->where('description', 'LIKE', "Tagihan {$upper}%")
                        ->orWhere('description', 'LIKE', "Token {$upper}%");
                });

            $count = $query->count();
            $this->line(sprintf('  %-8s -> akun %d : %d detail', $upper, $accountId, $count));

            if (! $dryRun && $count > 0) {
                $query->update(['account_id' => $accountId]);
            }
        }

        $this->line('');
        $this->info($dryRun ? 'Dry-run selesai (tidak ada perubahan).' : 'Selesai.');

        return self::SUCCESS;
    }
}
