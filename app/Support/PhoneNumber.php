<?php
namespace App\Support;

final class PhoneNumber
{
    public static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') return $value;
        $raw = trim($value);
        if (preg_match('/[A-Za-z]/', $raw)) return $value;
        $digits = self::digits($raw);
        if (str_starts_with($digits, '00') && strlen($digits) > 4) {
            $digits = substr($digits, 2);
        }
        if ($digits === '') return $value;
        if (str_starts_with($digits, '0033')) $digits = '33' . substr($digits, 4);
        if (str_starts_with($digits, '33') && strlen($digits) === 11) return '+' . $digits;
        if (str_starts_with($digits, '0') && strlen($digits) === 10) return '+33' . substr($digits, 1);
        return $raw[0] === '+' ? '+' . $digits : $digits;
    }

    public static function format(?string $value): ?string
    {
        $normalized = self::normalize($value);
        if ($normalized === null || $normalized === '' || preg_match('/[A-Za-z]/', $normalized)) return $value;
        $digits = self::digits($normalized);
        if (str_starts_with($digits, '33') && strlen($digits) === 11) $digits = '0' . substr($digits, 2);
        return strlen($digits) === 10 && str_starts_with($digits, '0')
            ? implode(' ', str_split($digits, 2))
            : $normalized;
    }

    public static function searchVariants(?string $value): array
    {
        $digits = self::digits($value);
        if (str_starts_with($digits, '00') && strlen($digits) > 4) $digits = substr($digits, 2);
        if ($digits === '') return [];
        if (str_starts_with($digits, '0033')) $digits = '33' . substr($digits, 4);
        $variants = [$digits];
        if (str_starts_with($digits, '0') && strlen($digits) === 10) $variants[] = '33' . substr($digits, 1);
        if (str_starts_with($digits, '33') && strlen($digits) === 11) $variants[] = '0' . substr($digits, 2);
        return array_values(array_unique($variants));
    }

    public static function applySearch($query, string $column, ?string $search)
    {
        $variants = self::searchVariants($search);
        if ($variants === []) return $query->where($column, 'like', '%' . $search . '%');
        $qualified = $query->getModel()->getTable() . '.' . $column;
        $expression = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE($qualified, ''), ' ', ''), '.', ''), '-', ''), '(', ''), ')', ''), '+', '')";
        return $query->where(function ($q) use ($expression, $variants) {
            foreach ($variants as $i => $variant) {
                $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                $q->{$method}($expression . ' LIKE ?', ['%' . $variant . '%']);
            }
        });
    }
}
