<?php

namespace Tests\Feature\Notulen;

use App\Services\Notulen\NotulenOpenRouterClient;
use App\Services\Notulen\PdfExtractionService;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class PdfExtractionServiceTest extends TestCase
{
    public function test_returns_parser_text_when_available(): void
    {
        $parser = $this->createMock(Parser::class);
        $pdf = $this->createMock(\Smalot\PdfParser\Document::class);
        $pdf->method('getText')->willReturn('  Isi notulen rapat.  ');
        $parser->method('parseFile')->willReturn($pdf);

        $client = $this->createMock(NotulenOpenRouterClient::class);
        $client->expects($this->never())->method('extractTextFromPdfBase64');

        $service = new PdfExtractionService($parser, $client);

        $this->assertSame('Isi notulen rapat.', $service->extractFromPath('/tmp/sample.pdf'));
    }

    public function test_falls_back_to_openrouter_ocr_when_parser_returns_empty(): void
    {
        $parser = $this->createMock(Parser::class);
        $pdf = $this->createMock(\Smalot\PdfParser\Document::class);
        $pdf->method('getText')->willReturn('');
        $parser->method('parseFile')->willReturn($pdf);

        $temp = tempnam(sys_get_temp_dir(), 'notulen');
        file_put_contents($temp, '%PDF-1.4 fake');

        $client = $this->createMock(NotulenOpenRouterClient::class);
        $client->expects($this->once())
            ->method('extractTextFromPdfBase64')
            ->willReturn('Teks hasil OCR dari scan PDF.');

        config(['notulen.ocr_fallback_enabled' => true]);

        $service = new PdfExtractionService($parser, $client);

        $this->assertSame(
            'Teks hasil OCR dari scan PDF.',
            $service->extractFromPath($temp),
        );

        @unlink($temp);
    }
}
