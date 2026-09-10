<?php

namespace App\Support;

class SapLineMemoTruncator
{
    public const MAX_LENGTH = 200;

    public static function truncate(string $text, ?string $vjNomor = null): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }

        if ($vjNomor) {
            $suffix = ' (lihat VJ '.$vjNomor.')';
            $maxBase = self::MAX_LENGTH - mb_strlen($suffix);
            if ($maxBase > 10) {
                return rtrim(mb_substr($text, 0, $maxBase - 1)).'…'.$suffix;
            }
        }

        return rtrim(mb_substr($text, 0, self::MAX_LENGTH - 1)).'…';
    }
}
