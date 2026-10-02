<?php

namespace Tests\Unit;

use App\Services\BankStatementParserService;
use App\Services\BankStatementPdfPageSplitter;
use App\Services\OpenRouterService;
use App\Services\ReconciliationMatchingService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class BankStatementParserLargePdfTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function it_splits_multi_page_pdf_into_ordered_single_page_binaries(): void
    {
        $multiPagePdf = $this->buildMultiPagePdf(3, false);

        $splitter = new BankStatementPdfPageSplitter;
        $pages = $splitter->splitIntoSinglePagePdfs($multiPagePdf);

        $this->assertCount(3, $pages);
        foreach ($pages as $pageBinary) {
            $this->assertStringStartsWith('%PDF', $pageBinary);
        }

        $probe = new Fpdi;
        foreach ($pages as $pageBinary) {
            $this->assertSame(1, $probe->setSourceFile(
                \setasign\Fpdi\PdfParser\StreamReader::createByString($pageBinary)
            ));
        }
    }

    #[Test]
    public function it_calls_openrouter_once_for_small_pdfs(): void
    {
        $smallPdf = $this->buildMultiPagePdf(1, false);

        $openRouter = $this->createMock(OpenRouterService::class);
        $openRouter->expects($this->once())
            ->method('extractBankStatementFromPdfBase64')
            ->willReturn($this->samplePayload(1));

        $service = new BankStatementParserService(
            $openRouter,
            $this->createMock(ReconciliationMatchingService::class),
        );

        $payload = $this->invokeExtractPayload($service, $smallPdf);

        $this->assertSame(100.0, $payload['opening_balance']);
        $this->assertCount(1, $payload['lines']);
    }

    #[Test]
    public function it_calls_openrouter_per_page_for_large_pdfs_and_merges_lines(): void
    {
        $multiPagePdf = $this->buildMultiPagePdf(3, false);

        $openRouter = $this->createMock(OpenRouterService::class);
        $openRouter->expects($this->exactly(3))
            ->method('extractBankStatementFromPdfBase64')
            ->willReturnOnConsecutiveCalls(
                $this->samplePayload(1, opening: 1000, closing: 1100),
                $this->samplePayload(2, opening: 9999, closing: 1200),
                $this->samplePayload(3, opening: 8888, closing: 1500),
            );

        $service = new BankStatementParserServiceForcePageSplit(
            $openRouter,
            $this->createMock(ReconciliationMatchingService::class),
            new BankStatementPdfPageSplitter,
        );

        $payload = $this->invokeExtractPayload($service, $multiPagePdf);

        $this->assertSame(1000.0, $payload['opening_balance']);
        $this->assertSame(1500.0, $payload['closing_balance']);
        $this->assertCount(3, $payload['lines']);
        $this->assertSame('Line page 1', $payload['lines'][0]['description']);
        $this->assertSame('Line page 3', $payload['lines'][2]['description']);
    }

    #[Test]
    public function it_uses_per_page_extraction_when_file_exceeds_size_threshold(): void
    {
        $multiPagePdf = $this->buildMultiPagePdf(3, false);
        $this->assertGreaterThan(1_000, strlen($multiPagePdf));

        $openRouter = $this->createMock(OpenRouterService::class);
        $openRouter->expects($this->exactly(3))
            ->method('extractBankStatementFromPdfBase64')
            ->willReturnOnConsecutiveCalls(
                $this->samplePayload(1),
                $this->samplePayload(2),
                $this->samplePayload(3),
            );

        $service = new BankStatementParserServiceLowThreshold(
            $openRouter,
            $this->createMock(ReconciliationMatchingService::class),
            new BankStatementPdfPageSplitter,
        );

        $payload = $this->invokeExtractPayload($service, $multiPagePdf);

        $this->assertCount(3, $payload['lines']);
    }

    /**
     * @return array{opening_balance: float, closing_balance: float, lines: array<int, array<string, mixed>>}
     */
    private function samplePayload(int $pageNumber, ?float $opening = null, ?float $closing = null): array
    {
        return [
            'opening_balance' => $opening ?? 100.0,
            'closing_balance' => $closing ?? 200.0,
            'lines' => [
                [
                    'transaction_date' => '2025-11-01',
                    'value_date' => null,
                    'description' => 'Line page '.$pageNumber,
                    'reference' => null,
                    'debit' => 0,
                    'credit' => 100,
                    'balance' => 100,
                    'confidence' => 0.9,
                ],
            ],
        ];
    }

    private function buildMultiPagePdf(int $pageCount, bool|string $embedImage = false): string
    {
        $imagePath = null;
        if ($embedImage === true || $embedImage === 'moderate') {
            $imagePath = $embedImage === 'moderate'
                ? $this->createJpegTempFile(1500, 2000, 88)
                : $this->createJpegTempFile(4200, 5600, 100);
        }

        $pdf = new Fpdi;
        for ($page = 1; $page <= $pageCount; $page++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(40, 10, 'Koran test page '.$page);
            if ($embedImage === 'moderate') {
                $pdf->Image($this->createJpegTempFile(1200, 1600, 88, $page), 10, 20, 180);
            } elseif ($imagePath !== null) {
                $pdf->Image($imagePath, 10, 20, 180);
            }
        }

        return $pdf->Output('S');
    }

    private function createJpegTempFile(int $width, int $height, int $quality, int $seed = 0): string
    {
        $path = sys_get_temp_dir().'/koran_large_'.uniqid('', true).'.jpg';
        $this->tempFiles[] = $path;

        $image = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x += 13) {
                $color = imagecolorallocate($image, ($x + $y + $seed) % 256, ($x * 3 + $seed) % 256, ($y * 5) % 256);
                imagesetpixel($image, $x, $y, $color);
            }
        }
        imagejpeg($image, $path, $quality);
        imagedestroy($image);

        return $path;
    }

    /**
     * @return array{opening_balance: mixed, closing_balance: mixed, lines: array<int, array<string, mixed>>}
     */
    private function invokeExtractPayload(BankStatementParserService $service, string $pdfBinary): array
    {
        $method = new ReflectionMethod(BankStatementParserService::class, 'extractStatementPayloadFromPdf');
        $method->setAccessible(true);

        return $method->invoke($service, $pdfBinary);
    }
}

final class BankStatementParserServiceForcePageSplit extends BankStatementParserService
{
    protected function shouldSplitPdfByPages(int $byteLength): bool
    {
        return true;
    }
}

final class BankStatementParserServiceLowThreshold extends BankStatementParserService
{
    protected function shouldSplitPdfByPages(int $byteLength): bool
    {
        return $byteLength > 1_000;
    }
}
