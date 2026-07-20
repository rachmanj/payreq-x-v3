<?php

namespace Tests\Feature\Notulen;

use App\Services\Notulen\NotulenChunker;
use Tests\TestCase;

class NotulenChunkerTest extends TestCase
{
    public function test_does_not_split_multibyte_characters(): void
    {
        config([
            'notulen.chunk_size' => 20,
            'notulen.chunk_overlap' => 4,
        ]);

        $text = str_repeat('Rapat keputusan anggaran bersama peserta. ', 5).'Nama: Budi Santoso dan Rini Wijaya.';
        $chunks = (new NotulenChunker)->chunk($text);

        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertSame($chunk, mb_convert_encoding($chunk, 'UTF-8', 'UTF-8'));
            $this->assertFalse(preg_match('/\x{FFFD}/u', $chunk) === 1);
        }
    }

    public function test_prefers_paragraph_break_points(): void
    {
        config([
            'notulen.chunk_size' => 120,
            'notulen.chunk_overlap' => 10,
        ]);

        $paragraphA = str_repeat('Keputusan anggaran disetujui oleh peserta. ', 4);
        $paragraphB = str_repeat('Action item ditugaskan ke finance team. ', 4);
        $text = trim($paragraphA)."\n\n".trim($paragraphB);

        $chunks = (new NotulenChunker)->chunk($text);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertStringContainsString('Keputusan anggaran', $chunks[0]);
        $this->assertStringNotContainsString('Action item', $chunks[0]);
    }

    public function test_preserves_newlines_and_overlap(): void
    {
        config([
            'notulen.chunk_size' => 60,
            'notulen.chunk_overlap' => 15,
        ]);

        $text = "Agenda:\n1. Anggaran\n2. Jadwal\n\n".str_repeat('Pembahasan detail keputusan rapat. ', 8);
        $chunks = (new NotulenChunker)->chunk($text);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertStringContainsString("\n", $chunks[0]);

        $joined = implode('', $chunks);
        $this->assertStringContainsString('Agenda:', $joined);
        $this->assertStringContainsString('Pembahasan detail', $joined);
    }

    public function test_collapses_spaces_but_keeps_paragraph_breaks(): void
    {
        config([
            'notulen.chunk_size' => 1500,
            'notulen.chunk_overlap' => 200,
        ]);

        $text = "Judul   rapat\tQ1\n\n\n\nKeputusan   disetujui.";
        $chunks = (new NotulenChunker)->chunk($text);

        $this->assertCount(1, $chunks);
        $this->assertSame("Judul rapat Q1\n\nKeputusan disetujui.", $chunks[0]);
    }
}
