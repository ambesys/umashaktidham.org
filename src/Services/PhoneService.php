<?php

namespace App\Services;

class PhoneService
{
    /**
     * Normalize a phone string to E.164 for US numbers.
     * - Accepts 10-digit US numbers and prefixes +1
     * - Accepts numbers starting with 1 and 11 digits
     * - If already starts with +, returns as-is
     * Returns normalized string or null if invalid.
     */
    public static function normalizeToE164(?string $raw): ?string
    {
        if (!$raw) return null;
        $raw = trim($raw);
        // if already E.164 (very basic check)
        if (strpos($raw, '+') === 0) {
            return $raw;
        }
        // strip non-digits
        $digits = preg_replace('/\D+/', '', $raw);
        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }
        if (strlen($digits) === 11 && $digits[0] === '1') {
            return '+' . $digits;
        }
        // not a valid US number by fallback rules
        return null;
    }
}
