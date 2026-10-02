<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\Dokumen;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankStatementParserService
{
    private const LARGE_PDF_THRESHOLD_BYTES = 1_572_864;

    private const MAX_PAGE_PDF_BYTES = 1_572_864;

    public function __construct(
        protected OpenRouterService $openRouter,
        protected ReconciliationMatchingService $matchingService,
        protected ?BankStatementPdfPageSplitter $pdfPageSplitter = null,
    ) {}

    public function parseAndPersist(BankReconciliation $reconciliation): void
    {
        $dokumen = $reconciliation->dokumen;
        if ($dokumen === null) {
            throw new \InvalidArgumentException('Reconciliation has no dokumen attached.');
        }

        $path = $this->resolveStatementPdfPath($dokumen);
        $pdfBinary = $this->readStatementPdfFromLocalPath($path);

        $payload = $this->extractStatementPayloadFromPdf($pdfBinary);

        DB::transaction(function () use ($reconciliation, $payload): void {
            $this->clearMatchGroupsForBankLines($reconciliation);

            $reconciliation->bankStatementLines()->delete();

            $opening = $this->nullableFloat(data_get($payload, 'opening_balance'));
            $closing = $this->nullableFloat(data_get($payload, 'closing_balance'));

            $reconciliation->update([
                'opening_balance_bank' => $opening,
                'closing_balance_bank' => $closing,
            ]);

            $lines = data_get($payload, 'lines', []);
            if (! is_array($lines)) {
                return;
            }

            foreach ($lines as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                BankStatementLine::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'transaction_date' => $this->parseDate(data_get($row, 'transaction_date')),
                    'value_date' => $this->parseDate(data_get($row, 'value_date')),
                    'description' => $this->truncateString((string) data_get($row, 'description', ''), 65535),
                    'reference' => $this->truncateString((string) (data_get($row, 'reference') ?? ''), 191),
                    'debit' => $this->floatAmount(data_get($row, 'debit', 0)),
                    'credit' => $this->floatAmount(data_get($row, 'credit', 0)),
                    'balance' => $this->nullableFloat(data_get($row, 'balance')),
                    'is_ai_extracted' => true,
                    'ai_confidence' => $this->nullableFloat(data_get($row, 'confidence')),
                    'matched_status' => BankStatementLine::MATCH_UNMATCHED,
                    'line_order' => $index + 1,
                ]);
            }
        });
    }

    /**
     * @return array{opening_balance: mixed, closing_balance: mixed, lines: array<int, array<string, mixed>>}
     */
    protected function extractStatementPayloadFromPdf(string $pdfBinary): array
    {
        if (! $this->shouldSplitPdfByPages(strlen($pdfBinary))) {
            return $this->openRouter->extractBankStatementFromPdfBase64(base64_encode($pdfBinary));
        }

        $splitter = $this->pdfPageSplitter ?? new BankStatementPdfPageSplitter;
        $pageBinaries = $splitter->splitIntoSinglePagePdfs($pdfBinary);

        $pagePayloads = [];
        foreach ($pageBinaries as $index => $pageBinary) {
            $pageNumber = $index + 1;
            $pageBinary = $this->ensurePageWithinSizeLimit($pageBinary, $pageNumber);

            try {
                $pagePayloads[] = $this->openRouter->extractBankStatementFromPdfBase64(base64_encode($pageBinary));
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf(
                    'Failed to extract bank statement from PDF page %d (%d bytes): %s',
                    $pageNumber,
                    strlen($pageBinary),
                    $e->getMessage()
                ), 0, $e);
            }
        }

        return $this->mergeExtractedPagePayloads($pagePayloads);
    }

    protected function shouldSplitPdfByPages(int $byteLength): bool
    {
        return $byteLength > self::LARGE_PDF_THRESHOLD_BYTES;
    }

    protected function ensurePageWithinSizeLimit(string $pageBinary, int $pageNumber): string
    {
        if (strlen($pageBinary) <= self::MAX_PAGE_PDF_BYTES) {
            return $pageBinary;
        }

        $shrunk = $this->attemptShrinkOversizedPagePdf($pageBinary);
        if ($shrunk !== null && strlen($shrunk) <= self::MAX_PAGE_PDF_BYTES) {
            return $shrunk;
        }

        $reportedSize = strlen($shrunk ?? $pageBinary);

        throw new \RuntimeException(sprintf(
            'Bank statement PDF page %d is too large for OpenRouter extraction (%d bytes; limit %d bytes).',
            $pageNumber,
            $reportedSize,
            self::MAX_PAGE_PDF_BYTES
        ));
    }

    protected function attemptShrinkOversizedPagePdf(string $pageBinary): ?string
    {
        try {
            $splitter = $this->pdfPageSplitter ?? new BankStatementPdfPageSplitter;
            $reshaped = $splitter->extractSinglePagePdf($pageBinary, 1);
        } catch (\Throwable) {
            return null;
        }

        if (strlen($reshaped) < strlen($pageBinary)) {
            return $reshaped;
        }

        return null;
    }

    /**
     * @param  array<int, array{opening_balance?: mixed, closing_balance?: mixed, lines?: mixed}>  $pagePayloads
     * @return array{opening_balance: mixed, closing_balance: mixed, lines: array<int, array<string, mixed>>}
     */
    protected function mergeExtractedPagePayloads(array $pagePayloads): array
    {
        $opening = null;
        $closing = null;
        $lines = [];

        foreach ($pagePayloads as $payload) {
            if ($opening === null) {
                $opening = data_get($payload, 'opening_balance');
            }

            $pageClosing = data_get($payload, 'closing_balance');
            if ($pageClosing !== null && $pageClosing !== '') {
                $closing = $pageClosing;
            }

            $pageLines = data_get($payload, 'lines', []);
            if (! is_array($pageLines)) {
                continue;
            }

            foreach ($pageLines as $row) {
                if (is_array($row)) {
                    $lines[] = $row;
                }
            }
        }

        return [
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'lines' => $lines,
        ];
    }

    protected function clearMatchGroupsForBankLines(BankReconciliation $reconciliation): void
    {
        $bankLineIds = $reconciliation->bankStatementLines()->pluck('id');

        if ($bankLineIds->isEmpty()) {
            return;
        }

        $groupIds = \App\Models\MatchGroupBankLine::query()
            ->whereIn('bank_statement_line_id', $bankLineIds)
            ->pluck('reconciliation_match_group_id')
            ->unique()
            ->values();

        $groups = $reconciliation->matchGroups()
            ->whereIn('id', $groupIds)
            ->get();

        foreach ($groups as $group) {
            $this->matchingService->deleteMatchGroup($group);
        }
    }

    protected function resolveStatementPdfPath(Dokumen $dokumen): string
    {
        $raw = $dokumen->getRawOriginal('filename1');
        if ($raw === null || $raw === '') {
            $raw = $dokumen->getAttributes()['filename1'] ?? '';
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            throw new \RuntimeException('Dokumen has no statement file.');
        }

        $pathOrName = parse_url($raw, PHP_URL_PATH);
        $basename = basename(is_string($pathOrName) && $pathOrName !== '' ? $pathOrName : $raw);
        if ($basename === '' || $basename === '.' || $basename === '..') {
            throw new \RuntimeException('Invalid statement filename stored on dokumen.');
        }

        $candidates = [
            public_path('dokumens/'.$basename),
            storage_path('app/private/dokumens/'.$basename),
            storage_path('app/public/dokumens/'.$basename),
            storage_path('app/dokumens/'.$basename),
        ];

        foreach ($candidates as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        Log::warning('Koran PDF path resolution failed', [
            'dokumen_id' => $dokumen->getKey(),
            'raw_filename1' => $dokumen->getRawOriginal('filename1'),
            'basename' => $basename,
            'candidates' => $candidates,
        ]);

        throw new \RuntimeException(sprintf(
            'Koran file not found locally: %s (searched: %s)',
            $basename,
            implode(', ', $candidates)
        ));
    }

    protected function readStatementPdfFromLocalPath(string $absolutePath): string
    {
        if (preg_match('#^https?://#i', $absolutePath)) {
            throw new \RuntimeException('Koran file must be read from local disk; HTTP download is not allowed.');
        }

        $pdfBinary = file_get_contents($absolutePath);
        if ($pdfBinary === false || $pdfBinary === '') {
            throw new \RuntimeException('Statement PDF could not be read (empty or unreadable file).');
        }

        $detectedMime = $this->detectStatementPdfMime($pdfBinary);
        if (! $this->isAllowedStatementPdfMime($detectedMime)) {
            throw new \RuntimeException(sprintf(
                'Unsupported MIME type: %s (accepted: application/pdf, application/x-pdf, application/octet-stream, application/pdf; charset=binary)',
                $detectedMime
            ));
        }

        return $pdfBinary;
    }

    protected function detectStatementPdfMime(string $binary): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary);

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }

    protected function isAllowedStatementPdfMime(string $detectedMime): bool
    {
        $normalized = strtolower($detectedMime);

        $allowedFragments = [
            'application/pdf',
            'application/x-pdf',
            'application/octet-stream',
        ];

        foreach ($allowedFragments as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }

    protected function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function floatAmount(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    protected function nullableFloat(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    protected function truncateString(string $value, int $max): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max);
    }
}
