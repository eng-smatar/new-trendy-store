<?php

namespace App\Services;

class ArabicText
{
    public static function normalize(?string $text): string
    {
        // Diacritics and tatweel
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', (string) $text);
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ة' => 'ه', 'ى' => 'ي']);

        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($text)));
    }
}
