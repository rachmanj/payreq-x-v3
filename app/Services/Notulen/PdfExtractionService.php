<?php

namespace App\Services\Notulen;

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

        $contents = file_get_contents($absolutePath);
        if ($contents === false || $contents === '') {
            return '';
        }

        return trim($this->openRouterClient->extractTextFromPdfBase64(base64_encode($contents)));
    }
}
