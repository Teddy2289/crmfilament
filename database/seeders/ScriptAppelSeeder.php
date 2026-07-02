<?php

namespace Database\Seeders;

use App\Models\ScriptAppel;
use Illuminate\Database\Seeder;

class ScriptAppelSeeder extends Seeder
{
    public function run(): void
    {
        static::seedDefaults();
    }

    public static function seedDefaults(): void
    {
        $scripts = require database_path('seeders/data/scripts_appel_ns_conseil.php');

        foreach ($scripts as $script) {
            $record = ScriptAppel::withTrashed()->updateOrCreate(
                ['slug' => $script['slug']],
                array_merge([
                    'campagne_id' => null,
                    'actif' => true,
                    'ordre' => 0,
                ], $script)
            );

            if ($record->trashed()) {
                $record->restore();
            }
        }
    }
}
