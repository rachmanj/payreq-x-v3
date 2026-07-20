<?php

namespace App\Services\Notulen;

class NotulenChunker
{
    /**
     * @return array<int, string>
     */
    public function chunk(string $text): array
    {
        $text = $this->normalizeWhitespace($text);
        if ($text === '') {
            return [];
        }

        $chunkSize = max(200, (int) config('notulen.chunk_size'));
        $overlap = max(0, min((int) config('notulen.chunk_overlap'), $chunkSize - 1));

        if (mb_strlen($text, 'UTF-8') <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $start = 0;
        $length = mb_strlen($text, 'UTF-8');

        while ($start < $length) {
            $end = min($start + $chunkSize, $length);

            if ($end < $length) {
                $slice = mb_substr($text, $start, $chunkSize, 'UTF-8');
                $breakAt = $this->findBreakPoint($slice);
                if ($breakAt !== null && $breakAt > (int) ($chunkSize * 0.5)) {
                    $end = $start + $breakAt;
                }
            }

            $chunk = trim(mb_substr($text, $start, $end - $start, 'UTF-8'));
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            if ($end >= $length) {
                break;
            }

            $start = max(0, $end - $overlap);
        }

        return $chunks;
    }

    protected function normalizeWhitespace(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    protected function findBreakPoint(string $slice): ?int
    {
        $candidates = [
            mb_strrpos($slice, "\n\n", 0, 'UTF-8'),
            mb_strrpos($slice, '. ', 0, 'UTF-8'),
            mb_strrpos($slice, "\n", 0, 'UTF-8'),
            mb_strrpos($slice, ' ', 0, 'UTF-8'),
        ];

        foreach ($candidates as $pos) {
            if ($pos !== false && $pos > 0) {
                return $pos + 1;
            }
        }

        return null;
    }
}
