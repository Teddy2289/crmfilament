<?php

namespace App\Console\Commands;

use App\Models\Prospect;
use App\Support\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PhoneSearchSmokeTest extends Command
{
    protected $signature = 'crm:phone-search-smoke {--phone= : Numéro à tester en lecture seule} {--prospect-id=3438 : ID du prospect de test utilisé si --phone est absent}';

    protected $description = 'Vérifie en lecture seule la recherche téléphonique et ses variantes nationales/internationales.';

    public function handle(): int
    {
        $rawPhone = $this->option('phone');

        if (! $rawPhone) {
            $prospectId = (int) ($this->option('prospect-id') ?: env('PHONE_SEARCH_SMOKE_PROSPECT_ID', 3438));
            $rawPhone = Prospect::query()
                ->whereKey($prospectId)
                ->value('telephone');

            if (! $rawPhone) {
                $this->error("Le prospect de test #{$prospectId} est introuvable ou ne possède pas de téléphone.");
                return self::FAILURE;
            }
        }

        if (! is_string($rawPhone) || trim($rawPhone) === '') {
            $this->error('Aucun numéro fourni pour le smoke test.');
            return self::FAILURE;
        }

        $digits = PhoneNumber::digits($rawPhone);
        $variants = array_values(array_unique(array_filter([
            $rawPhone,
            $digits,
            strlen($digits) === 10 && str_starts_with($digits, '0')
                ? '+33 ' . substr($digits, 1)
                : null,
            strlen($digits) === 11 && str_starts_with($digits, '33')
                ? '0' . substr($digits, 2)
                : null,
            strlen($digits) === 10
                ? substr($digits, 0, 2) . '-' . substr($digits, 2, 2) . '-' . substr($digits, 4, 2) . '-' . substr($digits, 6, 2) . '-' . substr($digits, 8, 2)
                : null,
        ])));

        $failures = [];

        foreach ($variants as $variant) {
            $found = PhoneNumber::applySearch(
                Prospect::query(),
                'telephone',
                $variant,
            )->exists();

            if (! $found) {
                $failures[] = $variant;
            }
        }

        $context = [
            'prospect_id' => $this->option('phone') ? null : (int) ($this->option('prospect-id') ?: env('PHONE_SEARCH_SMOKE_PROSPECT_ID', 3438)),
            'raw_phone' => $rawPhone,
            'variants' => $variants,
            'failed_variants' => $failures,
        ];

        if ($failures !== []) {
            Log::error('Phone search smoke test failed', $context);
            $this->error('Échec du smoke test téléphonique pour : ' . implode(', ', $failures));
            return self::FAILURE;
        }

        Log::info('Phone search smoke test passed', $context);
        $this->info('Smoke test téléphonique réussi en lecture seule pour ' . count($variants) . ' variante(s).');
        return self::SUCCESS;
    }
}
