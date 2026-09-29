<?php

namespace App\Services;

use App\Models\TaxPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TaxPeriodService
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'open' => ['prepared'],
        'prepared' => ['approved', 'open'],
        'approved' => ['filed', 'prepared'],
        'filed' => ['locked', 'approved'],
        'locked' => ['open'],
    ];

    public function findOrCreatePeriod(string $masaPajak, string $taxType = 'ppn'): TaxPeriod
    {
        return TaxPeriod::query()->firstOrCreate(
            [
                'masa_pajak' => $masaPajak,
                'tax_type' => $taxType,
            ],
            [
                'status' => 'open',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $reconciliationResult
     */
    public function saveReconciliationSnapshot(TaxPeriod $period, array $reconciliationResult): TaxPeriod
    {
        $totals = $reconciliationResult['totals'];

        $period->update([
            'pk_total' => $reconciliationResult['sap']['pk_total'],
            'pm_total' => $reconciliationResult['sap']['pm_total'],
            'kb_lb' => $totals['kb_lb'],
            'diff_sap_app' => $totals['diff_sap_app'],
            'diff_coretax_app' => $totals['diff_coretax_app'],
            'diff_pk_pm' => $totals['diff_pk_pm'],
            'snapshot_json' => $reconciliationResult,
        ]);

        return $period->fresh();
    }

    public function prepare(TaxPeriod $period, User $user): TaxPeriod
    {
        $this->assertTransition($period, 'prepared');
        $this->assertNotLockedForMutation($period, 'Masa pajak terkunci; tidak bisa disiapkan.');

        $period->update([
            'status' => 'prepared',
            'prepared_by' => $user->id,
            'prepared_at' => now(),
        ]);

        return $period->fresh();
    }

    public function approve(TaxPeriod $period, User $user): TaxPeriod
    {
        $this->assertTransition($period, 'approved');
        $this->assertNotLockedForMutation($period, 'Masa pajak terkunci.');

        $period->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $period->fresh();
    }

    public function close(TaxPeriod $period, User $user): TaxPeriod
    {
        if ($period->status === 'approved') {
            $this->assertTransition($period, 'filed');

            $period->update([
                'status' => 'filed',
                'filed_at' => now(),
            ]);

            return $period->fresh();
        }

        if ($period->status === 'filed') {
            $this->assertTransition($period, 'locked');

            $period->update([
                'status' => 'locked',
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]);

            return $period->fresh();
        }

        throw new InvalidArgumentException('Tutup masa hanya dari status disetujui (→ dilaporkan) atau dilaporkan (→ terkunci).');
    }

    public function reopen(TaxPeriod $period, User $user, string $reason): TaxPeriod
    {
        if ($period->status !== 'locked') {
            throw new InvalidArgumentException('Buka kunci hanya untuk masa pajak berstatus terkunci.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Alasan buka kunci wajib diisi.');
        }

        return DB::transaction(function () use ($period, $user, $reason): TaxPeriod {
            $auditLog = $period->audit_log ?? [];
            $auditLog[] = [
                'action' => 'reopen',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'at' => now()->toIso8601String(),
                'reason' => $reason,
            ];

            $period->update([
                'status' => 'open',
                'audit_log' => $auditLog,
            ]);

            return $period->fresh();
        });
    }

    public function assertCanMutateFaktur(TaxPeriod $period): void
    {
        if (in_array($period->status, ['filed', 'locked'], true)) {
            throw new InvalidArgumentException('Masa pajak sudah dilaporkan atau terkunci; perubahan faktur tidak diizinkan.');
        }
    }

    private function assertTransition(TaxPeriod $period, string $toStatus): void
    {
        $allowed = self::ALLOWED_TRANSITIONS[$period->status] ?? [];
        if (! in_array($toStatus, $allowed, true)) {
            throw new InvalidArgumentException(
                "Transisi tidak sah: {$period->status} → {$toStatus}."
            );
        }
    }

    private function assertNotLockedForMutation(TaxPeriod $period, string $message): void
    {
        if ($period->status === 'locked') {
            throw new InvalidArgumentException($message);
        }
    }
}
