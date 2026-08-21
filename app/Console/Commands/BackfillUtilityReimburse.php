<?php

namespace App\Console\Commands;

use App\Http\Controllers\DocumentNumberController;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\User;
use App\Models\UtilityBill;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillUtilityReimburse extends Command
{
    /**
     * Backfill: tandai semua tagihan utilities sebagai "lunas" (tanggal_bayar)
     * dan "reimburse" (linked ke payreq) — untuk tagihan yang sebenarnya sudah
     * lunas & direimburse tapi baru di-upload sehingga statusnya masih kosong.
     *
     * Per periode dibuat 1 payreq reimburse (status close) + 1 realization
     * (status close) + realization_detail per tagihan. TANPA membuat outgoing
     * (cash-out sudah dicatat manual — hindari double-hit saldo kas).
     */
    protected $signature = 'utility:backfill-reimburse
                            {--user=44 : User ID requestor (default iwan)}
                            {--dry-run : Preview saja, tidak menyimpan apa pun}';

    protected $description = 'Backfill tagihan utilities: tandai lunas + buat payreq reimburse per periode (status close)';

    public function handle(): int
    {
        $userId = (int) $this->option('user');
        $dryRun = (bool) $this->option('dry-run');

        $user = User::find($userId);
        if (! $user) {
            $this->error("User id {$userId} tidak ditemukan.");

            return self::FAILURE;
        }

        $bills = UtilityBill::query()
            ->with('customer')
            ->whereNull('payreq_id')
            ->orderBy('periode')
            ->get();

        if ($bills->isEmpty()) {
            $this->info('Tidak ada tagihan yang perlu diproses (semua sudah reimburse).');

            return self::SUCCESS;
        }

        $periods = $bills->groupBy('periode')->sortKeys();

        $this->line('');
        $this->info('Backfill reimburse utilities — requestor: '.$user->name.' (id '.$user->id.', dept '.$user->department_id.')');
        if ($dryRun) {
            $this->warn('MODE DRY-RUN: tidak ada yang disimpan.');
        }
        $this->line('');

        $totalBills = 0;
        $totalAmount = 0;

        foreach ($periods as $periode => $group) {
            $count = $group->count();
            $amount = $group->sum('jumlah_tagihan');
            $totalBills += $count;
            $totalAmount += $amount;

            $this->line(sprintf(
                '  %s : %d tagihan, Rp %s',
                $periode,
                $count,
                number_format($amount, 0, ',', '.')
            ));
        }

        $this->line('');
        $this->line(sprintf('TOTAL: %d tagihan, Rp %s', $totalBills, number_format($totalAmount, 0, ',', '.')));
        $this->line('');

        if ($dryRun) {
            return self::SUCCESS;
        }

        foreach ($periods as $periode => $group) {
            $this->processPeriode($periode, $group, $user);
        }

        $this->line('');
        $this->info('Selesai.');

        return self::SUCCESS;
    }

    protected function processPeriode(string $periode, $bills, User $user): void
    {
        $project = $bills->first()->customer->project ?? '000H';

        DB::transaction(function () use ($periode, $bills, $user, $project) {
            // 1. Tandai lunas (tanggal_bayar = tanggal_jatuh_tempo)
            foreach ($bills as $bill) {
                if (! $bill->tanggal_bayar) {
                    $bill->update([
                        'tanggal_bayar' => $bill->tanggal_jatuh_tempo
                            ? $bill->tanggal_jatuh_tempo->toDateString()
                            : now()->toDateString(),
                    ]);
                }
            }

            $amount = $bills->sum('jumlah_tagihan');

            // 2. Payreq reimburse (status close / final)
            $payreq = Payreq::create([
                'remarks' => "Reimburse utilities periode {$periode}",
                'amount' => $amount,
                'project' => $project,
                'department_id' => $user->department_id,
                'nomor' => app(DocumentNumberController::class)->generate_document_number('payreq', $project),
                'status' => 'close',
                'type' => 'reimburse',
                'rab_id' => null,
                'budget_link_mode' => null,
                'lot_no' => null,
                'user_id' => $user->id,
                'submit_at' => now(),
                'approved_at' => now(),
            ]);

            // 3. Realization (status close)
            $realization = Realization::create([
                'payreq_id' => $payreq->id,
                'project' => $project,
                'department_id' => $user->department_id,
                'remarks' => $payreq->remarks,
                'user_id' => $user->id,
                'nomor' => app(DocumentNumberController::class)->generate_document_number('realization', $project),
                'status' => 'close',
                'submit_at' => now(),
            ]);

            // 4. Realization detail per tagihan
            foreach ($bills as $bill) {
                $realization->realizationDetails()->create([
                    'project' => $project,
                    'department_id' => $user->department_id,
                    'description' => ($bill->customer->tipe === 'prepaid' ? 'Token ' : 'Tagihan ')
                        .strtoupper($bill->customer->jenis_utilitas).' '
                        .$bill->customer->id_pelanggan.' — '.$bill->periode
                        .($bill->customer->lokasi ? ' - '.$bill->customer->lokasi : ''),
                    'amount' => $bill->jumlah_tagihan,
                    'account_id' => $bill->customer->account_id,
                    'expense_date' => $bill->tanggal_bayar
                        ? $bill->tanggal_bayar->toDateString()
                        : now()->toDateString(),
                    'type' => 'other',
                ]);
            }

            // 5. Link bills -> payreq
            UtilityBill::query()
                ->whereIn('id', $bills->pluck('id'))
                ->update(['payreq_id' => $payreq->id]);
        });

        $this->line(sprintf(
            '  ✔ %s : payreq #%d (%d tagihan, Rp %s)',
            $periode,
            Payreq::where('remarks', "Reimburse utilities periode {$periode}")->latest('id')->value('id'),
            $bills->count(),
            number_format($bills->sum('jumlah_tagihan'), 0, ',', '.')
        ));
    }
}
