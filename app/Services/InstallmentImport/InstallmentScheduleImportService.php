<?php

namespace App\Services\InstallmentImport;

use App\Models\Installment;
use App\Models\Loan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InstallmentScheduleImportService
{
    public const FORMAT_BCA_OUTSTANDING = 'bca_outstanding';

    /**
     * @return array{success: bool, message?: string, preview_token?: string, units?: list<array<string, mixed>>, verification?: array<string, mixed>, matched_unit?: array<string, mixed>|null}
     */
    public function preview(UploadedFile $file, Loan $loan, string $format): array
    {
        if ($format !== self::FORMAT_BCA_OUTSTANDING) {
            return [
                'success' => false,
                'message' => 'Format import tidak dikenali.',
            ];
        }

        $parsed = (new BcaStyleExcelParser)->parse($file->getRealPath());
        $matchedUnit = $this->matchUnitForLoan($parsed['units'], $loan);

        if ($matchedUnit === null) {
            return [
                'success' => false,
                'message' => 'Kontrak '.$loan->loan_code.' tidak ditemukan di file import.',
            ];
        }

        $verification = $this->buildVerification($matchedUnit, $loan);
        $previewToken = Str::uuid()->toString();

        Cache::put($this->cacheKey($previewToken), [
            'loan_id' => $loan->id,
            'format' => $format,
            'unit' => $matchedUnit,
        ], now()->addHour());

        return [
            'success' => true,
            'preview_token' => $previewToken,
            'units' => [$matchedUnit],
            'matched_unit' => $matchedUnit,
            'verification' => $verification,
        ];
    }

    /**
     * @return array{success: bool, message: string, updated?: int, skipped?: list<array{no: int, reason: string}>}
     */
    public function import(string $previewToken, Loan $loan): array
    {
        $cached = Cache::get($this->cacheKey($previewToken));

        if (! $cached || (int) ($cached['loan_id'] ?? 0) !== $loan->id) {
            return [
                'success' => false,
                'message' => 'Preview tidak valid atau sudah kedaluwarsa. Silakan upload ulang.',
            ];
        }

        $unit = $cached['unit'] ?? null;
        if (! is_array($unit) || empty($unit['rows'])) {
            return [
                'success' => false,
                'message' => 'Data preview tidak ditemukan.',
            ];
        }

        $updated = 0;
        $skipped = [];

        foreach ($unit['rows'] as $row) {
            $installment = Installment::query()
                ->where('loan_id', $loan->id)
                ->where('angsuran_ke', $row['no'])
                ->first();

            if (! $installment) {
                $skipped[] = [
                    'no' => (int) $row['no'],
                    'reason' => 'Angsuran ke-'.$row['no'].' tidak ditemukan di kontrak.',
                ];

                continue;
            }

            if ($installment->principal_amount !== null) {
                $skipped[] = [
                    'no' => (int) $row['no'],
                    'reason' => 'Split pokok/bunga sudah terisi.',
                ];

                continue;
            }

            $updates = [
                'principal_amount' => $row['principal'],
                'interest_amount' => $row['interest'],
            ];

            if ($installment->due_date === null && ! empty($row['due_date'])) {
                $updates['due_date'] = $row['due_date'];
            }

            if ($installment->bilyet_amount === null && $row['total'] > 0) {
                $updates['bilyet_amount'] = $row['total'];
            }

            $installment->update($updates);
            $updated++;
        }

        Cache::forget($this->cacheKey($previewToken));

        return [
            'success' => true,
            'message' => $updated.' baris angsuran berhasil diisi.',
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $units
     * @return array<string, mixed>|null
     */
    protected function matchUnitForLoan(array $units, Loan $loan): ?array
    {
        $loanCode = trim((string) $loan->loan_code);
        $kodeUnit = trim((string) ($loan->kode_unit ?? ''));

        foreach ($units as $unit) {
            $kontrak = trim((string) ($unit['kontrak'] ?? ''));
            $unitLabel = trim((string) ($unit['unit'] ?? ''));
            $unitLabelNormalized = preg_replace('/\s+/', ' ', $unitLabel) ?? '';

            if ($kontrak !== '' && strcasecmp($kontrak, $loanCode) === 0) {
                return $unit;
            }

            if ($kodeUnit !== '' && strcasecmp($unitLabelNormalized, preg_replace('/\s+/', ' ', $kodeUnit) ?? '') === 0) {
                return $unit;
            }
        }

        if (count($units) === 1) {
            return $units[0];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $unit
     * @return array{principal_total: float, interest_total: float, loan_principal: float, loan_total_bunga: float, principal_diff: float, interest_diff: float, warnings: list<string>}
     */
    protected function buildVerification(array $unit, Loan $loan): array
    {
        $principalTotal = array_sum(array_column($unit['rows'], 'principal'));
        $interestTotal = array_sum(array_column($unit['rows'], 'interest'));
        $loanPrincipal = (float) $loan->principal;
        $loanTotalBunga = (float) ($loan->total_bunga ?? 0);
        $principalDiff = abs($principalTotal - $loanPrincipal);
        $interestDiff = abs($interestTotal - $loanTotalBunga);

        $warnings = [];
        if ($principalDiff > 1000) {
            $warnings[] = 'Total pokok import ('.number_format($principalTotal, 0, ',', '.').') berbeda > Rp1.000 dari pokok kontrak ('.number_format($loanPrincipal, 0, ',', '.').').';
        }
        if ($loanTotalBunga > 0 && $interestDiff > 1000) {
            $warnings[] = 'Total bunga import ('.number_format($interestTotal, 0, ',', '.').') berbeda > Rp1.000 dari total bunga kontrak ('.number_format($loanTotalBunga, 0, ',', '.').').';
        }

        return [
            'principal_total' => $principalTotal,
            'interest_total' => $interestTotal,
            'loan_principal' => $loanPrincipal,
            'loan_total_bunga' => $loanTotalBunga,
            'principal_diff' => $principalDiff,
            'interest_diff' => $interestDiff,
            'warnings' => $warnings,
        ];
    }

    protected function cacheKey(string $token): string
    {
        return 'installment_import_preview:'.$token;
    }
}
