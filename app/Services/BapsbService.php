<?php

namespace App\Services;

use App\Models\Bapsb;
use App\Models\Bilyet;
use App\Models\Giro;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BapsbService
{
    public const LOCATIONS = [
        'Brankas Site',
        'Lemari Besi Accounting HO',
        'Brankas BO',
        'Safe Deposit Box Bank',
        'Lainnya',
    ];

    public function periodEnd(string $period): Carbon
    {
        return Carbon::createFromFormat('Y-m', $period)->endOfMonth()->endOfDay();
    }

    public function periodStart(string $period): Carbon
    {
        return Carbon::createFromFormat('Y-m', $period)->startOfMonth()->startOfDay();
    }

    /**
     * @return Collection<int, Bilyet>
     */
    public function bilyetsForPeriod(string $project, string $period): Collection
    {
        $end = $this->periodEnd($period);

        return Bilyet::query()
            ->with(['giro.bank'])
            ->where('project', $project)
            ->whereIn('status', ['onhand', 'release'])
            ->whereNotIn('type', ['debit'])
            ->whereDate('bilyet_date', '<=', $end->toDateString())
            ->where(function ($query) use ($end) {
                $query->whereNull('cair_date')
                    ->orWhereDate('cair_date', '>', $end->toDateString());
            })
            ->whereHas('giro', function ($query) {
                $query->where('needs_bilyet', true);
            })
            ->orderBy('giro_id')
            ->orderBy('type')
            ->orderBy('nomor')
            ->get();
    }

    /**
     * @return array{count_cair: int, count_void: int}
     */
    public function mutationCountsForPeriod(string $project, string $period): array
    {
        $start = $this->periodStart($period);
        $end = $this->periodEnd($period);

        $base = Bilyet::query()
            ->where('project', $project)
            ->whereNotIn('type', ['debit'])
            ->whereHas('giro', fn ($q) => $q->where('needs_bilyet', true));

        $countCair = (clone $base)
            ->where('status', 'cair')
            ->whereNotNull('cair_date')
            ->whereDate('cair_date', '>=', $start->toDateString())
            ->whereDate('cair_date', '<=', $end->toDateString())
            ->count();

        $countVoid = (clone $base)
            ->where('status', 'void')
            ->whereDate('updated_at', '>=', $start->toDateString())
            ->whereDate('updated_at', '<=', $end->toDateString())
            ->count();

        return [
            'count_cair' => $countCair,
            'count_void' => $countVoid,
        ];
    }

    /**
     * @param  Collection<int, Bilyet>  $bilyets
     * @return array{
     *     total_bg: int,
     *     total_cek: int,
     *     total_loa: int,
     *     count_bg: int,
     *     count_cek: int,
     *     count_loa: int
     * }
     */
    public function summarizeBilyets(Collection $bilyets): array
    {
        $totals = [
            'total_bg' => 0,
            'total_cek' => 0,
            'total_loa' => 0,
            'count_bg' => 0,
            'count_cek' => 0,
            'count_loa' => 0,
        ];

        foreach ($bilyets as $bilyet) {
            $amount = (int) round((float) $bilyet->amount);
            if ($this->isBgType($bilyet->type)) {
                $totals['total_bg'] += $amount;
                $totals['count_bg']++;
            } elseif ($this->isCekType($bilyet->type)) {
                $totals['total_cek'] += $amount;
                $totals['count_cek']++;
            } elseif ($this->isLoaType($bilyet->type)) {
                $totals['total_loa'] += $amount;
                $totals['count_loa']++;
            }
        }

        return $totals;
    }

    public function generateNomor(string $project, string $period): string
    {
        $monthYear = Carbon::createFromFormat('Y-m', $period)->format('m-Y');

        return DB::transaction(function () use ($project, $period, $monthYear) {
            $count = Bapsb::query()
                ->where('project', $project)
                ->where('period', $period)
                ->lockForUpdate()
                ->count();

            $padded = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

            return "BAPSB-{$padded}/{$project}/{$monthYear}";
        });
    }

    public function bankAccountLabel(Bilyet $bilyet): string
    {
        $giro = $bilyet->giro;
        if (! $giro) {
            return '-';
        }

        $sap = $giro->sap_account ? $giro->sap_account.' — ' : '';

        return $sap.$giro->acc_no.' ('.($giro->acc_name ?? '').')';
    }

    public function isBgType(?string $type): bool
    {
        return in_array(strtolower((string) $type), ['bg', 'bilyet'], true);
    }

    public function isCekType(?string $type): bool
    {
        return strtolower((string) $type) === 'cek';
    }

    public function isLoaType(?string $type): bool
    {
        return in_array(strtolower((string) $type), ['loa', 'LOA'], true);
    }

    /**
     * @return array<int, string>
     */
    public function projectsRequiringBapsb(): array
    {
        return Giro::query()
            ->where('needs_bilyet', true)
            ->whereNotNull('project')
            ->distinct()
            ->orderBy('project')
            ->pluck('project')
            ->all();
    }

    public function projectHasNeedsBilyetGiro(string $project): bool
    {
        return Giro::query()
            ->where('project', $project)
            ->where('needs_bilyet', true)
            ->exists();
    }
}
