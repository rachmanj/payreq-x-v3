<?php

namespace App\Jobs;

use App\Models\OutgoingAttachment;
use App\Services\OpenRouterService;
use App\Support\TransferProofVerifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class VerifyTransferProofJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public OutgoingAttachment $attachment)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            $this->attachment->load(['outgoing.payreq.transferAccount.bank']);

            $outgoing = $this->attachment->outgoing;
            $transferAccount = $outgoing?->payreq?->transferAccount;

            if ($transferAccount === null) {
                $this->attachment->update([
                    'verification_status' => 'failed',
                    'verification_result' => ['error' => 'Tujuan transfer tidak ditemukan'],
                ]);

                return;
            }

            $mime = (string) ($this->attachment->mime ?? '');
            if ($mime !== '' && ! str_starts_with($mime, 'image/')) {
                $this->attachment->update([
                    'verification_status' => 'failed',
                    'verification_result' => ['error' => 'PDF tidak diverifikasi otomatis'],
                ]);

                return;
            }

            $disk = Storage::disk('outgoing_attachments');
            if (! $disk->exists($this->attachment->stored_path)) {
                $this->attachment->update([
                    'verification_status' => 'failed',
                    'verification_result' => ['error' => 'File tidak ditemukan'],
                ]);

                return;
            }

            $base64 = base64_encode($disk->get($this->attachment->stored_path));
            $extracted = app(OpenRouterService::class)->verifyTransferProofFromImageBase64(
                $base64,
                $mime !== '' ? $mime : 'image/jpeg'
            );

            $expected = [
                'bank_name' => $transferAccount->bank->name,
                'account_number' => $transferAccount->account_number,
                'account_name' => $transferAccount->account_name,
                'amount' => (int) $outgoing->amount,
            ];

            $verdict = TransferProofVerifier::compare($extracted, $expected);

            $this->attachment->update([
                'verification_status' => $verdict['status'],
                'verification_result' => [
                    'extracted' => $extracted,
                    'expected' => $expected,
                    'details' => $verdict['details'],
                ],
            ]);
        } catch (\Throwable $exception) {
            $this->attachment->update([
                'verification_status' => 'failed',
                'verification_result' => ['error' => $exception->getMessage()],
            ]);
        }
    }
}
