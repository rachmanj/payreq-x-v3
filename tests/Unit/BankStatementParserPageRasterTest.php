<?php

namespace Tests\Unit;

use App\Services\BankStatementParserService;
use App\Services\BankStatementPdfPageRasterizer;
use App\Services\BankStatementPdfPageSplitter;
use App\Services\OpenRouterService;
use App\Services\ReconciliationMatchingService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class BankStatementParserPageRasterTest extends TestCase
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
    public function it_uses_image_extraction_when_page_pdf_exceeds_limit_and_raster_succeeds(): void
    {
        $oversizedPagePdf = $this->buildSinglePagePdfWithEmbeddedJpeg(2800, 3600, 95);
        $this->assertGreaterThan(BankStatementPdfPageRasterizer::MAX_IMAGE_BYTES, strlen($oversizedPagePdf));

        $smallJpeg = $this->createJpegBinary(800, 600, 75);
        $this->assertLessThanOrEqual(BankStatementPdfPageRasterizer::MAX_IMAGE_BYTES, strlen($smallJpeg));

        $rasterizer = new RasterizerStub($smallJpeg);

        $openRouter = $this->createMock(OpenRouterService::class);
        $openRouter->expects($this->once())
            ->method('extractBankStatementFromImageBase64')
            ->with(
                $this->callback(fn (string $b64): bool => base64_decode($b64, true) === $smallJpeg),
                'image/jpeg'
            )
            ->willReturn($this->samplePayload(1));
        $openRouter->expects($this->never())->method('extractBankStatementFromPdfBase64');

        $service = new BankStatementParserServiceForcePageSplit(
            $openRouter,
            $this->createMock(ReconciliationMatchingService::class),
            new BankStatementPdfPageSplitter,
            $rasterizer,
        );

        $payload = $this->invokeExtractPayload($service, $oversizedPagePdf);

        $this->assertSame(100.0, $payload['opening_balance']);
        $this->assertTrue($rasterizer->rasterCliInvoked);
    }

    #[Test]
    public function rasterizer_stops_dpi_reduction_when_output_is_under_limit(): void
    {
        $pagePdf = $this->buildMinimalPdf();
        $outputDir = sys_get_temp_dir().'/koran_raster_test_'.uniqid('', true);
        mkdir($outputDir);

        $dpiUsed = [];
        $rasterizer = new BankStatementPdfPageRasterizer(function (string $command) use (&$dpiUsed): ?string {
            if (preg_match('/command -v \'pdftoppm\'/', $command)) {
                return '/usr/bin/pdftoppm';
            }

            if (preg_match('/pdftoppm -jpeg -r (\d+)/', $command, $matches)) {
                $dpi = (int) $matches[1];
                $dpiUsed[] = $dpi;

                if (preg_match('/\'([^\']+_out)\'/', $command, $outMatch)) {
                    $prefix = $outMatch[1];
                    $jpeg = $this->createJpegBinary(1200, 900, $dpi <= 120 ? 60 : 95);
                    file_put_contents($prefix.'.jpg', $jpeg);
                }
            }

            return '';
        });

        $result = $rasterizer->renderPageUnderSizeLimit($pagePdf, 1);

        $this->assertLessThanOrEqual(BankStatementPdfPageRasterizer::MAX_IMAGE_BYTES, strlen($result));
        $this->assertContains(150, $dpiUsed);
        $this->assertNotContains(100, $dpiUsed);

        @rmdir($outputDir);
    }

    #[Test]
    public function rasterizer_error_includes_page_number_size_and_available_tools(): void
    {
        $rasterizer = new BankStatementPdfPageRasterizer(static fn (string $command): ?string => '');

        $pagePdf = $this->buildMinimalPdf();

        try {
            $rasterizer->renderPageUnderSizeLimit($pagePdf, 4);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $message = $exception->getMessage();
            $this->assertStringContainsString('page 4', $message);
            $this->assertStringContainsString('limit '.BankStatementPdfPageRasterizer::MAX_IMAGE_BYTES, $message);
            $this->assertStringContainsString('Raster tools available:', $message);
        }
    }

    #[Test]
    public function embedded_jpeg_fallback_resizes_under_limit_without_cli(): void
    {
        $largeJpeg = $this->createJpegBinary(4200, 5600, 100);
        $pagePdf = $this->buildPdfWithRawDctStream($largeJpeg);
        $this->assertGreaterThan(BankStatementPdfPageRasterizer::MAX_IMAGE_BYTES, strlen($pagePdf));

        $rasterizer = new BankStatementPdfPageRasterizer(static fn (string $command): ?string => '');

        $result = $rasterizer->renderPageUnderSizeLimit($pagePdf, 2);

        $this->assertLessThanOrEqual(BankStatementPdfPageRasterizer::MAX_IMAGE_BYTES, strlen($result));
        $this->assertStringStartsWith("\xFF\xD8\xFF", $result);
    }

    /**
     * @return array{opening_balance: float, closing_balance: float, lines: array<int, array<string, mixed>>}
     */
    private function samplePayload(int $pageNumber): array
    {
        return [
            'opening_balance' => 100.0,
            'closing_balance' => 200.0,
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

    private function buildMinimalPdf(): string
    {
        $pdf = new Fpdi;
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(40, 10, 'Raster test');

        return $pdf->Output('S');
    }

    private function buildSinglePagePdfWithEmbeddedJpeg(int $width, int $height, int $quality): string
    {
        $imagePath = $this->createJpegTempFile($width, $height, $quality);

        $pdf = new Fpdi;
        $pdf->AddPage();
        $pdf->Image($imagePath, 0, 0, 210);

        return $pdf->Output('S');
    }

    private function buildPdfWithRawDctStream(string $jpegBinary): string
    {
        $length = strlen($jpegBinary);

        return "%PDF-1.4\n"
            ."1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n"
            ."2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n"
            ."3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources<< /XObject<< /Im1 5 0 R >> >> >>endobj\n"
            ."4 0 obj<< /Length 44 >>stream\n"
            ."q 612 0 0 792 0 0 cm /Im1 Do Q\n"
            ."endstream\n"
            ."endobj\n"
            ."5 0 obj<< /Type /XObject /Subtype /Image /Width 100 /Height 100 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$length} >>stream\n"
            .$jpegBinary
            ."\nendstream\n"
            ."endobj\n"
            ."xref\n0 6\n0000000000 65535 f \n"
            ."trailer<< /Size 6 /Root 1 0 R >>\n"
            ."startxref\n0\n%%EOF";
    }

    private function createJpegTempFile(int $width, int $height, int $quality): string
    {
        $path = sys_get_temp_dir().'/koran_raster_'.uniqid('', true).'.jpg';
        $this->tempFiles[] = $path;
        imagejpeg($this->createNoiseImage($width, $height), $path, $quality);

        return $path;
    }

    private function createJpegBinary(int $width, int $height, int $quality): string
    {
        $image = $this->createNoiseImage($width, $height);
        ob_start();
        imagejpeg($image, null, $quality);
        $binary = ob_get_clean();
        imagedestroy($image);

        return is_string($binary) ? $binary : '';
    }

    private function createNoiseImage(int $width, int $height): \GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x += 11) {
                $color = imagecolorallocate($image, ($x + $y) % 256, ($x * 2) % 256, ($y * 3) % 256);
                imagesetpixel($image, $x, $y, $color);
            }
        }

        return $image;
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

final class RasterizerStub extends BankStatementPdfPageRasterizer
{
    public bool $rasterCliInvoked = false;

    public function __construct(private string $jpegBinary) {}

    public function resolveRasterCliTool(): ?string
    {
        return 'pdftoppm';
    }

    protected function renderWithRasterCli(string $pagePdfBinary, string $tool): ?string
    {
        $this->rasterCliInvoked = true;

        return $this->jpegBinary;
    }
}
