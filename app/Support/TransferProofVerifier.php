<?php

namespace App\Support;

class TransferProofVerifier
{
    /**
     * @param  array<string, mixed>  $extracted
     * @param  array{bank_name: string, account_number: string, account_name: string, amount: int}  $expected
     * @return array{status: 'verified'|'mismatch', details: array<string, array{extracted: mixed, expected: mixed, note?: string}>}
     */
    public static function compare(array $extracted, array $expected): array
    {
        $details = [];

        $fields = ['bank_name', 'account_number', 'account_name', 'amount'];

        foreach ($fields as $field) {
            $extractedValue = $extracted[$field] ?? null;
            $expectedValue = $expected[$field] ?? null;

            if ($extractedValue === null || $extractedValue === '') {
                $details[$field] = [
                    'extracted' => $extractedValue,
                    'expected' => $expectedValue,
                    'note' => 'tidak terbaca',
                ];

                continue;
            }

            $normalizedExtracted = self::normalizeField($field, $extractedValue);
            $normalizedExpected = self::normalizeField($field, $expectedValue);

            if ($normalizedExtracted !== $normalizedExpected) {
                $details[$field] = [
                    'extracted' => $extractedValue,
                    'expected' => $expectedValue,
                ];
            }
        }

        return [
            'status' => $details === [] ? 'verified' : 'mismatch',
            'details' => $details,
        ];
    }

    protected static function normalizeField(string $field, mixed $value): mixed
    {
        if ($field === 'amount') {
            return (int) $value;
        }

        if ($field === 'bank_name') {
            $normalized = strtolower((string) $value);
            $normalized = str_replace(['bank', ' ', '.'], '', $normalized);

            return $normalized;
        }

        if ($field === 'account_number') {
            return preg_replace('/[\s.\-]/', '', (string) $value) ?? '';
        }

        if ($field === 'account_name') {
            $normalized = strtolower(trim((string) $value));

            return preg_replace('/\s+/', ' ', $normalized) ?? '';
        }

        return $value;
    }
}
