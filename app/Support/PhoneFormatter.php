<?php

namespace App\Support;

class PhoneFormatter
{
    /**
     * Convert international format (33...) to national French format (0...)
     * 33285290000 → 02 85 29 00 00
     */
    public static function toNationalFrench(string $phoneNumber): ?string
    {
        if (empty($phoneNumber)) {
            return null;
        }

        $clean = preg_replace('/[^\d]/', '', $phoneNumber);
        
        if (empty($clean)) {
            return null;
        }

        // If starts with 33 (France country code), convert to 0X
        if (str_starts_with($clean, '33')) {
            $clean = '0' . substr($clean, 2);
        }

        // Format as: 0X XX XX XX XX
        if (strlen($clean) === 10 && str_starts_with($clean, '0')) {
            return substr($clean, 0, 2) . ' ' . 
                   substr($clean, 2, 2) . ' ' . 
                   substr($clean, 4, 2) . ' ' . 
                   substr($clean, 6, 2) . ' ' . 
                   substr($clean, 8, 2);
        }

        return $clean;
    }

    /**
     * Convert national French format (0...) to international format (33...)
     * 02 85 29 00 00 → 33285290000
     */
    public static function toInternational(string $phoneNumber): ?string
    {
        if (empty($phoneNumber)) {
            return null;
        }

        $clean = preg_replace('/[^\d]/', '', $phoneNumber);

        if (empty($clean)) {
            return null;
        }

        // If starts with 0, replace with 33
        if (str_starts_with($clean, '0')) {
            $clean = '33' . substr($clean, 1);
        }

        return $clean;
    }

    /**
     * Format to display: both formats side by side
     * 33285290000 → "02 85 29 00 00 · +33 2 85 29 00 00"
     */
    public static function formatDual(string $phoneNumber): ?string
    {
        if (empty($phoneNumber)) {
            return null;
        }

        $national = self::toNationalFrench($phoneNumber);
        $international = self::toInternational($phoneNumber);
        
        if (!$national || !$international) {
            return null;
        }
        
        // Format international with +
        $internationalFormatted = '+' . substr($international, 0, 2) . ' ' . 
                                 substr($international, 2, 1) . ' ' . 
                                 substr($international, 3, 2) . ' ' . 
                                 substr($international, 5, 2) . ' ' . 
                                 substr($international, 7, 2) . ' ' . 
                                 substr($international, 9, 2);

        return "{$national} · {$internationalFormatted}";
    }
}
