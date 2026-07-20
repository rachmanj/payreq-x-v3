<?php

namespace App\Services\Notulen;

use App\Exceptions\NotulenOcrException;
use Smalot\PdfParser\Parser;

class PdfExtractionService
{
    public function __construct(
        protected ?Parser $parser = null,
        protected ?NotulenOpenRouterClient $openRouterClient = null,
    ) {
        $this->parser = $parser ?? new Parser;
        $this->openRouterClient = $openRouterClient ?? app(NotulenOpenRouterClient::class);
    }

    public function extractFromPath(string $absolutePath): string
    {
        $pdf = $this->parser->parseFile($absolutePath);
        $text = trim($pdf->getText());

        if ($text !== '') {
            return $text;
        }

        if (! config('notulen.ocr_fallback_enabled')) {
            return '';
        }

        $pageCount = count($pdf->getPages());
        $this->assertOcrAllowed($absolutePath, $pageCount);

        $contents = file_get_contents($absolutePath);
        if ($contents === false || $contents === '') {
            throw new NotulenOcrException('Unable to read PDF file for OCR fallback.');
        }

        // Whole-PDF OCR: page-by-page rasterization requires Imagick/pdftoppm (not installed).
        // Size/page guards above prevent oversized single-request OCR failures.
        $ocrText = trim($this->openRouterClient->extractTextFromPdfBase64(base64_encode($contents)));

        if ($ocrText === '') {
            throw new NotulenOcrException('OCR fallback returned empty text for scanned PDF.');
        }

        return $ocrText;
    }

    protected function assertOcrAllowed(string $absolutePath, int $pageCount): void
    {
        $maxPages = max(1, (int) config('notulen.ocr_max_pages', 50));
        $maxMb = max(0.1, (float) config('notulen.ocr_max_mb', 20));

        if ($pageCount > $maxPages) {
            throw new NotulenOcrException(
                "Scanned PDF has {$pageCount} pages which exceeds OCR limit of {$maxPages} pages. Split the document or increase NOTULEN_OCR_MAX_PAGES."
            );
        }

        $bytes = @filesize($absolutePath);
        if ($bytes === false) {
            throw new NotulenOcrException('Unable to determine PDF file size for OCR fallback.');
        }

        $mb = $bytes / 1024 / 1024;
        if ($mb > $maxMb) {
            $rounded = round($mb, 2);
            throw new NotulenOcrException(
                "Scanned PDF is {$rounded} MB which exceeds OCR limit of {$maxMb} MB. Compress the document or increase NOTULEN_OCR_MAX_MB."
            );
        }
    }
}
