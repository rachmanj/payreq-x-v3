<?php

namespace Tests\Unit;

use App\Models\Dokumen;
use App\Services\BankStatementParserService;
use App\Services\OpenRouterService;
use App\Services\ReconciliationMatchingService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class BankStatementParserFileResolutionTest extends TestCase
{
    private const MINIMAL_PDF = '%PDF-1.4 minimal test';

    /** @var list<string> */
    private array $createdPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function it_resolves_local_file_from_basename_in_public_dokumens(): void
    {
        $basename = 'koran_resolution_'.uniqid('', true).'.pdf';
        $absolutePath = $this->placePdfInPublicDokumens($basename);

        $dokumen = $this->makeDokumen($basename);
        $resolved = $this->invokeResolvePath($dokumen);

        $this->assertSame($absolutePath, $resolved);
    }

    #[Test]
    public function it_resolves_local_file_when_filename_is_full_application_url(): void
    {
        $basename = 'koran_resolution_url_'.uniqid('', true).'.pdf';
        $absolutePath = $this->placePdfInPublicDokumens($basename);

        $dokumen = $this->makeDokumen('https://one.arka-acc.online/dokumens/'.$basename);
        $resolved = $this->invokeResolvePath($dokumen);

        $this->assertSame($absolutePath, $resolved);
    }

    #[Test]
    public function it_throws_clear_error_when_local_file_is_missing(): void
    {
        $basename = 'koran_missing_'.uniqid('', true).'.pdf';
        $dokumen = $this->makeDokumen($basename);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not found locally');
        $this->expectExceptionMessage($basename);

        try {
            $this->invokeResolvePath($dokumen);
        } catch (\RuntimeException $exception) {
            $this->assertStringNotContainsString('Unsupported MIME type', $exception->getMessage());

            throw $exception;
        }
    }

    #[Test]
    public function it_accepts_application_pdf_mime_variants(): void
    {
        $service = $this->makeParserService();

        $this->assertTrue($this->invokeIsAllowedMime($service, 'application/pdf'));
        $this->assertTrue($this->invokeIsAllowedMime($service, 'application/pdf; charset=binary'));
        $this->assertTrue($this->invokeIsAllowedMime($service, 'application/x-pdf'));
        $this->assertTrue($this->invokeIsAllowedMime($service, 'application/octet-stream'));
    }

    #[Test]
    public function it_reads_local_pdf_without_mime_rejection(): void
    {
        $basename = 'koran_read_'.uniqid('', true).'.pdf';
        $absolutePath = $this->placePdfInPublicDokumens($basename);
        $service = $this->makeParserService();

        $binary = $this->invokeReadLocal($service, $absolutePath);

        $this->assertSame(self::MINIMAL_PDF, $binary);
    }

    private function makeParserService(): BankStatementParserService
    {
        return new BankStatementParserService(
            $this->createMock(OpenRouterService::class),
            $this->createMock(ReconciliationMatchingService::class),
        );
    }

    private function makeDokumen(string $filename1): Dokumen
    {
        $dokumen = new Dokumen;
        $dokumen->setRawAttributes([
            'id' => 1,
            'filename1' => $filename1,
        ]);

        return $dokumen;
    }

    private function placePdfInPublicDokumens(string $basename): string
    {
        $directory = public_path('dokumens');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory.DIRECTORY_SEPARATOR.$basename;
        file_put_contents($path, self::MINIMAL_PDF);
        $this->createdPaths[] = $path;

        return $path;
    }

    private function invokeResolvePath(Dokumen $dokumen): string
    {
        $method = new ReflectionMethod(BankStatementParserService::class, 'resolveStatementPdfPath');
        $method->setAccessible(true);

        return $method->invoke($this->makeParserService(), $dokumen);
    }

    private function invokeReadLocal(BankStatementParserService $service, string $path): string
    {
        $method = new ReflectionMethod(BankStatementParserService::class, 'readStatementPdfFromLocalPath');
        $method->setAccessible(true);

        return $method->invoke($service, $path);
    }

    private function invokeIsAllowedMime(BankStatementParserService $service, string $mime): bool
    {
        $method = new ReflectionMethod(BankStatementParserService::class, 'isAllowedStatementPdfMime');
        $method->setAccessible(true);

        return $method->invoke($service, $mime);
    }
}
