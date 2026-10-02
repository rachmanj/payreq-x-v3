<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class BankStatementPdfPageSplitter
{
    /**
     * @return list<string> Single-page PDF binaries in page order (1-based source pages).
     */
    public function splitIntoSinglePagePdfs(string $pdfBinary): array
    {
        $reader = StreamReader::createByString($pdfBinary);
        $probe = new Fpdi;

        try {
            $pageCount = $probe->setSourceFile($reader);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Failed to read bank statement PDF for per-page splitting: '.$e->getMessage(),
                0,
                $e
            );
        }

        if ($pageCount < 1) {
            throw new \RuntimeException('Bank statement PDF contains no pages.');
        }

        $pages = [];
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $pages[] = $this->extractSinglePagePdf($pdfBinary, $pageNo);
        }

        return $pages;
    }

    public function extractSinglePagePdf(string $pdfBinary, int $pageNo): string
    {
        $pdf = new Fpdi;
        $reader = StreamReader::createByString($pdfBinary);
        $pdf->setSourceFile($reader);

        $templateId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($templateId);
        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

        return $pdf->Output('S');
    }
}
