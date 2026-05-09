<?php

namespace App\Services\Help;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class HelpManualChunker
{
    protected const MAX_CHUNK_CHARS = 30000;

    /**
     * @return array<int, array{chunk_key:string,source_path:string,heading:?string,locale:string,content:string}>
     */
    public function chunkAll(): array
    {
        $chunks = [];

        $manualsPath = config('help.manuals_path');

        if (is_string($manualsPath) && File::isDirectory($manualsPath)) {
            foreach (File::glob($manualsPath.DIRECTORY_SEPARATOR.'*.md') ?: [] as $path) {
                $chunks = array_merge($chunks, $this->chunkMarkdownFile($path));
            }
        }

        $navPath = config('help.navigation_json_path');
        if (is_string($navPath) && File::exists($navPath)) {
            $chunks = array_merge($chunks, $this->chunkNavigationJson($navPath));
        }

        return $chunks;
    }

    /**
     * @return array<int, array{chunk_key:string,source_path:string,heading:?string,locale:string,content:string}>
     */
    public function chunkMarkdownFile(string $absolutePath): array
    {
        $content = File::get($absolutePath);
        $relative = Str::replaceFirst(base_path().DIRECTORY_SEPARATOR, '', $absolutePath);
        $relative = str_replace('\\', '/', $relative);
        $basename = basename($absolutePath);

        $locale = 'both';
        if (preg_match('/-id\.md$/i', $basename)) {
            $locale = 'id';
        } elseif (preg_match('/-en\.md$/i', $basename)) {
            $locale = 'en';
        }

        $sections = preg_split('/\n(?=## )/', $content, -1, PREG_SPLIT_NO_EMPTY);
        if ($sections === false) {
            return [];
        }

        $chunks = [];

        foreach ($sections as $index => $block) {
            $blockTrim = trim($block);
            if ($blockTrim === '') {
                continue;
            }

            $heading = null;
            if (preg_match('/^##\s+(.+)$/m', $blockTrim, $m)) {
                $heading = trim($m[1]);
            }

            $text = $this->truncate($blockTrim);
            $key = hash('sha256', $relative.'|'.$index.'|'.($heading ?? ''));

            $chunks[] = [
                'chunk_key' => $key,
                'source_path' => $relative,
                'heading' => $heading,
                'locale' => $locale,
                'content' => $text,
            ];
        }

        return $chunks;
    }

    /**
     * @return array<int, array{chunk_key:string,source_path:string,heading:?string,locale:string,content:string}>
     */
    public function chunkNavigationJson(string $absolutePath): array
    {
        $raw = File::get($absolutePath);
        $relative = Str::replaceFirst(base_path().DIRECTORY_SEPARATOR, '', $absolutePath);
        $relative = str_replace('\\', '/', $relative);

        $decoded = json_decode($raw, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        $entries = [];
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $entries = $decoded['items'];
        } elseif (is_array($decoded)) {
            $entries = array_is_list($decoded) ? $decoded : [$decoded];
        }

        $chunks = [];
        foreach ($entries as $index => $entry) {
            $heading = is_array($entry) && isset($entry['title']) && is_string($entry['title'])
                ? $entry['title']
                : 'Navigation '.$index;

            try {
                $content = is_string($entry) ? $entry : json_encode(['navigation' => $entry], JSON_UNESCAPED_SLASHES);
            } catch (\JsonException) {
                $content = json_encode([]);
            }

            $text = $this->truncate($content);

            $key = hash('sha256', $relative.'|'.$index.'|'.$heading);

            $chunks[] = [
                'chunk_key' => $key,
                'source_path' => $relative,
                'heading' => $heading,
                'locale' => 'both',
                'content' => $text,
            ];
        }

        return $chunks;
    }

    protected function truncate(string $text): string
    {
        if (strlen($text) <= self::MAX_CHUNK_CHARS) {
            return $text;
        }

        return substr($text, 0, self::MAX_CHUNK_CHARS);
    }
}
