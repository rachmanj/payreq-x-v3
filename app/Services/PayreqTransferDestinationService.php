<?php

namespace App\Services;

use App\Models\Payreq;
use App\Models\PayreqTransferDestination;
use App\Models\TransferAccount;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class PayreqTransferDestinationService
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'transfer_destinations' => ['nullable', 'array'],
            'transfer_destinations.*.transfer_account_id' => ['required', 'integer', 'exists:transfer_accounts,id'],
            'transfer_destinations.*.planned_amount' => ['nullable', 'numeric', 'min:1'],
            'transfer_destinations.*.remark' => ['nullable', 'string', 'max:255'],
            'transfer_destinations_present' => ['nullable', 'in:0,1'],
        ];
    }

    public static function isPresentMarked(mixed $value): bool
    {
        return (int) $value === 1;
    }

    public static function normalizePlannedAmountInput(mixed $plannedAmount): mixed
    {
        if (! is_string($plannedAmount)) {
            return $plannedAmount;
        }

        $normalized = str_replace([',', '.'], '', $plannedAmount);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $destinations
     */
    public static function assertValid(Validator $validator, ?array $destinations, int $userId, ?int $payreqAmount): void
    {
        $normalized = self::normalizeInput($destinations);
        if ($normalized->isEmpty()) {
            return;
        }

        foreach ($normalized as $index => $row) {
            $transferAccountId = (int) $row['transfer_account_id'];

            $owned = TransferAccount::query()
                ->where('id', $transferAccountId)
                ->where('user_id', $userId)
                ->exists();

            if (! $owned) {
                $validator->errors()->add(
                    "transfer_destinations.{$index}.transfer_account_id",
                    'Akun transfer tidak valid atau bukan milik Anda.'
                );
            }
        }

        $totalPlanned = $normalized->sum(fn (array $row) => (int) ($row['planned_amount'] ?? 0));

        if ($payreqAmount !== null && $payreqAmount > 0 && $totalPlanned > $payreqAmount) {
            $validator->errors()->add(
                'transfer_destinations',
                'Total planned amount tidak boleh melebihi jumlah payreq.'
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $destinations
     */
    public static function hasDestinations(?array $destinations): bool
    {
        return self::normalizeInput($destinations)->isNotEmpty();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $destinations
     */
    public static function firstTransferAccountId(?array $destinations): ?int
    {
        $first = self::normalizeInput($destinations)->first();

        return $first ? (int) $first['transfer_account_id'] : null;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $destinations
     */
    public static function sync(Payreq $payreq, ?array $destinations, int $userId, bool $destinationsPresent = false): void
    {
        $normalized = self::normalizeInput($destinations);

        if ($normalized->isEmpty()) {
            if ($destinationsPresent) {
                PayreqTransferDestination::query()
                    ->where('payreq_id', $payreq->id)
                    ->delete();
            }

            return;
        }

        $incomingAccountIds = $normalized
            ->pluck('transfer_account_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        PayreqTransferDestination::query()
            ->where('payreq_id', $payreq->id)
            ->whereNotIn('transfer_account_id', $incomingAccountIds)
            ->delete();

        $existing = PayreqTransferDestination::query()
            ->where('payreq_id', $payreq->id)
            ->get()
            ->keyBy('transfer_account_id');

        foreach ($normalized as $row) {
            $transferAccountId = (int) $row['transfer_account_id'];
            $attributes = [
                'planned_amount' => isset($row['planned_amount']) && $row['planned_amount'] !== ''
                    ? (int) $row['planned_amount']
                    : null,
                'remark' => $row['remark'] ?? null,
            ];

            if ($existing->has($transferAccountId)) {
                $existing->get($transferAccountId)->update($attributes);

                continue;
            }

            PayreqTransferDestination::create([
                'payreq_id' => $payreq->id,
                'transfer_account_id' => $transferAccountId,
                'planned_amount' => $attributes['planned_amount'],
                'remark' => $attributes['remark'],
                'created_by' => $userId,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $destinations
     * @return Collection<int, array{transfer_account_id: int, planned_amount: int|null, remark: string|null}>
     */
    private static function normalizeInput(?array $destinations): Collection
    {
        if (! is_array($destinations)) {
            return collect();
        }

        return collect($destinations)
            ->filter(fn ($row) => is_array($row) && ! empty($row['transfer_account_id']))
            ->map(function (array $row) {
                $plannedAmount = self::normalizePlannedAmountInput($row['planned_amount'] ?? null);

                return [
                    'transfer_account_id' => (int) $row['transfer_account_id'],
                    'planned_amount' => $plannedAmount !== null ? (int) $plannedAmount : null,
                    'remark' => isset($row['remark']) ? (string) $row['remark'] : null,
                ];
            })
            ->values();
    }
}
