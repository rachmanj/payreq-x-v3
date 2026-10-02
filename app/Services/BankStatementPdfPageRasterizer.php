<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BankStatementPdfPageRasterizer
{
    public const MAX_IMAGE_BYTES = 1_572_864;

    /** @var list<int> */
    private const DPI_STEPS = [150, 120, 100];

    /**
     * @param  (callable(string): ?string)|null  $shellExecutor  Receives full shell command; returns stdout or null on failure.
     */
    public function __construct(
        protected mixed $shellExecutor = null,
    ) {}

    public function renderPageUnderSizeLimit(string $pagePdfBinary, int $pageNumber): string
    {
        $lastAttemptBytes = strlen($pagePdfBinary);

        $tool = $this->resolveRasterCliTool();
        if ($tool !== null) {
            $jpeg = $this->renderWithRasterCli($pagePdfBinary, $tool);
            if ($jpeg !== null) {
                $jpeg = $this->ensureJpegUnderByteLimit($jpeg, $tool);
                if ($jpeg !== null) {
                    $lastAttemptBytes = strlen($jpeg);
                    if ($lastAttemptBytes <= self::MAX_IMAGE_BYTES) {
                        return $jpeg;
                    }
                }
            }
        }

        $embedded = $this->extractLargestEmbeddedJpeg($pagePdfBinary);
        if ($embedded !== null) {
            $jpeg = $this->ensureJpegUnderByteLimit($embedded, 'embedded-dct');
            if ($jpeg !== null) {
                $lastAttemptBytes = strlen($jpeg);
                if ($lastAttemptBytes <= self::MAX_IMAGE_BYTES) {
                    return $jpeg;
                }
            }
        }

        $reportedSize = $lastAttemptBytes;

        throw new \RuntimeException(sprintf(
            'Bank statement PDF page %d is too large for OpenRouter extraction (%d bytes; limit %d bytes). Raster tools available: %s.',
            $pageNumber,
            $reportedSize,
            self::MAX_IMAGE_BYTES,
            $this->describeAvailableTools()
        ));
    }

    public function describeAvailableTools(): string
    {
        $available = [];
        foreach (['pdftoppm', 'pdftocairo'] as $command) {
            if ($this->isCommandAvailable($command)) {
                $available[] = $command;
            }
        }

        $gd = extension_loaded('gd') ? 'gd' : null;
        if ($gd !== null) {
            $available[] = $gd;
        }

        if ($available === []) {
            return 'none';
        }

        return implode(', ', $available);
    }

    public function resolveRasterCliTool(): ?string
    {
        foreach (['pdftoppm', 'pdftocairo'] as $command) {
            if ($this->isCommandAvailable($command)) {
                return $command;
            }
        }

        return null;
    }

    protected function renderWithRasterCli(string $pagePdfBinary, string $tool): ?string
    {
        $tempDir = sys_get_temp_dir();
        $token = bin2hex(random_bytes(8));
        $inputPdf = $tempDir.'/koran_page_'.$token.'.pdf';
        $outputPrefix = $tempDir.'/koran_page_'.$token.'_out';

        $tempFiles = [$inputPdf, $outputPrefix.'.jpg'];

        try {
            if (file_put_contents($inputPdf, $pagePdfBinary) === false) {
                return null;
            }

            $lastJpeg = null;
            $lastDpi = null;

            foreach (self::DPI_STEPS as $dpi) {
                $this->deleteIfExists($outputPrefix.'.jpg');

                if (! $this->runRasterCli($tool, $inputPdf, $outputPrefix, $dpi)) {
                    continue;
                }

                if (! is_readable($outputPrefix.'.jpg')) {
                    continue;
                }

                $jpeg = file_get_contents($outputPrefix.'.jpg');
                if ($jpeg === false || $jpeg === '') {
                    continue;
                }

                $lastJpeg = $jpeg;
                $lastDpi = $dpi;

                if (strlen($jpeg) <= self::MAX_IMAGE_BYTES) {
                    Log::info('Bank statement page rasterized for OpenRouter', [
                        'tool' => $tool,
                        'dpi' => $dpi,
                        'bytes' => strlen($jpeg),
                    ]);

                    return $jpeg;
                }
            }

            if ($lastJpeg !== null && $lastDpi !== null) {
                Log::info('Bank statement page rasterized above size limit at lowest DPI; will attempt GD resize', [
                    'tool' => $tool,
                    'dpi' => $lastDpi,
                    'bytes' => strlen($lastJpeg),
                ]);

                return $lastJpeg;
            }

            return null;
        } finally {
            foreach ($tempFiles as $path) {
                $this->deleteIfExists($path);
            }
        }
    }

    protected function runRasterCli(string $tool, string $inputPdf, string $outputPrefix, int $dpi): bool
    {
        $escapedInput = escapeshellarg($inputPdf);
        $escapedOutput = escapeshellarg($outputPrefix);

        if ($tool === 'pdftoppm') {
            $command = sprintf(
                'pdftoppm -jpeg -r %d -f 1 -l 1 -singlefile %s %s 2>/dev/null',
                $dpi,
                $escapedInput,
                $escapedOutput
            );
        } elseif ($tool === 'pdftocairo') {
            $command = sprintf(
                'pdftocairo -jpeg -r %d -f 1 -l 1 -singlefile %s %s 2>/dev/null',
                $dpi,
                $escapedInput,
                $escapedOutput
            );
        } else {
            return false;
        }

        $this->runShell($command);

        return is_readable($outputPrefix.'.jpg');
    }

    protected function ensureJpegUnderByteLimit(string $jpeg, string $source): ?string
    {
        if (strlen($jpeg) <= self::MAX_IMAGE_BYTES) {
            return $jpeg;
        }

        $resized = $this->resizeJpegWithGd($jpeg);
        if ($resized !== null && strlen($resized) <= self::MAX_IMAGE_BYTES) {
            Log::info('Bank statement page JPEG resized for OpenRouter', [
                'source' => $source,
                'bytes' => strlen($resized),
            ]);

            return $resized;
        }

        return $resized ?? $jpeg;
    }

    protected function resizeJpegWithGd(string $jpeg): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $image = @imagecreatefromstring($jpeg);
        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 1 || $height < 1) {
            imagedestroy($image);

            return null;
        }

        $bestOverLimit = null;

        foreach ([1.0, 0.85, 0.7, 0.55, 0.45, 0.35] as $scale) {
            foreach ([85, 75, 65, 55] as $quality) {
                $newWidth = max(1, (int) round($width * $scale));
                $newHeight = max(1, (int) round($height * $scale));
                $scaled = imagescale($image, $newWidth, $newHeight, IMG_BILINEAR_FIXED);
                if ($scaled === false) {
                    continue;
                }

                $encoded = $this->encodeJpeg($scaled, $quality);
                imagedestroy($scaled);

                if ($encoded === null) {
                    continue;
                }

                if (strlen($encoded) <= self::MAX_IMAGE_BYTES) {
                    imagedestroy($image);

                    return $encoded;
                }

                $bestOverLimit = $encoded;
            }
        }

        imagedestroy($image);

        return $bestOverLimit;
    }

    protected function encodeJpeg(\GdImage $image, int $quality): ?string
    {
        ob_start();
        $ok = imagejpeg($image, null, $quality);
        $data = ob_get_clean();

        if (! $ok || $data === false || $data === '') {
            return null;
        }

        return $data;
    }

    protected function extractLargestEmbeddedJpeg(string $pdfBinary): ?string
    {
        $candidates = [];
        $offset = 0;

        while (preg_match('/stream\r?\n/', $pdfBinary, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $streamStart = $match[0][1] + strlen($match[0][0]);
            $endPos = strpos($pdfBinary, 'endstream', $streamStart);
            if ($endPos === false) {
                break;
            }

            $headerStart = max(0, $streamStart - 4096);
            $header = substr($pdfBinary, $headerStart, $streamStart - $headerStart);
            $rawStream = substr($pdfBinary, $streamStart, $endPos - $streamStart);

            if (str_contains($header, '/DCTDecode')) {
                $jpeg = $this->decodePdfImageStream($rawStream, $header);
                if ($jpeg !== null && $this->looksLikeJpeg($jpeg)) {
                    $candidates[] = $jpeg;
                }
            }

            $offset = $endPos + 9;
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $candidates[0];
    }

    protected function decodePdfImageStream(string $rawStream, string $dictionaryHeader): ?string
    {
        $data = rtrim($rawStream, "\r\n");

        if (str_contains($dictionaryHeader, '/FlateDecode') && ! str_contains($dictionaryHeader, '/DCTDecode')) {
            return null;
        }

        if (str_contains($dictionaryHeader, '/FlateDecode')) {
            $inflated = @gzuncompress($data);
            if ($inflated === false) {
                $inflated = @gzinflate($data);
            }
            if ($inflated !== false && $this->looksLikeJpeg($inflated)) {
                return $inflated;
            }
        }

        return $data;
    }

    protected function looksLikeJpeg(string $data): bool
    {
        return strlen($data) > 4 && str_starts_with($data, "\xFF\xD8\xFF");
    }

    protected function isCommandAvailable(string $command): bool
    {
        $check = sprintf('command -v %s 2>/dev/null', escapeshellarg($command));
        $path = trim((string) $this->runShell($check));

        return $path !== '';
    }

    protected function runShell(string $command): ?string
    {
        if ($this->shellExecutor !== null) {
            return ($this->shellExecutor)($command);
        }

        $output = shell_exec($command);

        return is_string($output) ? $output : null;
    }

    protected function deleteIfExists(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
