<?php

namespace App\Services\Notulen;

class NotulenChunker
{
    /**
     * @return array<int, string>
     */
    public function chunk(string $text): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return [];
        }

        $chunkSize = max(200, (int) config('notulen.chunk_size'));
        $overlap = max(0, min((int) config('notulen.chunk_overlap'), $chunkSize - 1));

        if (strlen($text) <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $start = 0;
        $length = strlen($text);

        while ($start < $length) {
            $end = min($start + $chunkSize, $length);

            if ($end < $length) {
                $slice = substr($text, $start, $chunkSize);
                $breakAt = $this->findBreakPoint($slice);
                if ($breakAt !== null && $breakAt > (int) ($chunkSize * 0.5)) {
                    $end = $start + $breakAt;
                }
            }

            $chunk = trim(substr($text, $start, $end - $start));
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

    protected function findBreakPoint(string $slice): ?int
    {
        $candidates = [
            strrpos($slice, "\n\n"),
            strrpos($slice, '. '),
            strrpos($slice, ' '),
        ];

        foreach ($candidates as $pos) {
            if ($pos !== false && $pos > 0) {
                return $pos + 1;
            }
        }

        return null;
    }
}
