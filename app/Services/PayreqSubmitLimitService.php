<?php

namespace App\Services;

use App\Models\Parameter;
use App\Models\Payreq;
use App\Models\User;

final class PayreqSubmitLimitService
{
    private const DEFAULT_LIMIT = 5;

    public function limit(): int
    {
        $parameter = Parameter::where('name1', 'max_submitted_payreq')->first();

        if ($parameter === null || $parameter->param_value === null || $parameter->param_value === '') {
            return self::DEFAULT_LIMIT;
        }

        $value = (int) $parameter->param_value;

        return $value > 0 ? $value : self::DEFAULT_LIMIT;
    }

    public function submittedCount(int $userId): int
    {
        return Payreq::where('user_id', $userId)->where('status', 'submitted')->count();
    }

    public function validate(User $requestor): ?string
    {
        $limit = $this->limit();
        $count = $this->submittedCount($requestor->id);

        if ($count >= $limit) {
            return "Masih ada {$count} payreq kamu yang menunggu approval (maksimal {$limit}). Selesaikan dulu sebelum submit payreq baru.";
        }

        return null;
    }

    /**
     * @return array{count: int, limit: int, blocked: bool}
     */
    public function summary(int $userId): array
    {
        $limit = $this->limit();
        $count = $this->submittedCount($userId);

        return [
            'count' => $count,
            'limit' => $limit,
            'blocked' => $count >= $limit,
        ];
    }
}
