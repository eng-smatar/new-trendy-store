<?php

namespace App\Services;

class ArabicText
{
    /** Arabic-Indic and Persian digits to 0-9, as typed on Arabic keyboards. */
    public static function digits(string $text): string
    {
        return strtr($text, array_combine(
            [...mb_str_split('٠١٢٣٤٥٦٧٨٩'), ...mb_str_split('۰۱۲۳۴۵۶۷۸۹')],
            [...range(0, 9), ...range(0, 9)],
        ));
    }

    public static function normalize(?string $text): string
    {
        // Diacritics and tatweel
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', (string) $text);
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ة' => 'ه', 'ى' => 'ي']);

        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($text)));
    }
}
