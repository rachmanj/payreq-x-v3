<?php

namespace App\Support;

use App\Models\TransferAccount;
use Illuminate\Validation\Validator;

class PayreqPaymentMethod
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'payment_method' => 'required|in:cash,transfer',
            'transfer_account_id' => 'required_if:payment_method,transfer|nullable|integer|exists:transfer_accounts,id',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{payment_method: string, transfer_account_id: int|null}
     */
    public static function normalizedAttributes(array $data): array
    {
        $paymentMethod = $data['payment_method'] ?? 'cash';

        return [
            'payment_method' => $paymentMethod,
            'transfer_account_id' => $paymentMethod === 'transfer'
                ? (isset($data['transfer_account_id']) ? (int) $data['transfer_account_id'] : null)
                : null,
        ];
    }

    public static function assertTransferAccountOwnership(Validator $validator, ?int $transferAccountId, int $userId): void
    {
        if (! $transferAccountId) {
            return;
        }

        $owned = TransferAccount::query()
            ->where('id', $transferAccountId)
            ->where('user_id', $userId)
            ->exists();

        if (! $owned) {
            $validator->errors()->add('transfer_account_id', 'Akun transfer tidak valid atau bukan milik Anda.');
        }
    }
}
