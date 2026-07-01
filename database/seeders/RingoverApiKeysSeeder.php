<?php

namespace Database\Seeders;

use App\Models\RingoverApiKey;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RingoverApiKeysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $keys = [
            [
                'name' => 'Admin Ringover',
                'api_key' => '8c99a1e6b9518c28e98b31e6d4b5550cdc13c811',
                'type' => 'admin',
                'is_active' => true,
                'description' => 'Clé API admin pour Ringover - accès complet',
            ],
            [
                'name' => 'Télépro 1',
                'api_key' => 'eb6c4ad4c65e157420adf31b9db263248a2b1915',
                'type' => 'telepro',
                'is_active' => true,
                'description' => 'Clé API pour le téléprospecteur 1',
            ],
            [
                'name' => 'Télépro 2',
                'api_key' => '6d2b233e4d656aac234095495b3b6de22fdac42f',
                'type' => 'telepro',
                'is_active' => true,
                'description' => 'Clé API pour le téléprospecteur 2',
            ],
            [
                'name' => 'Télépro 3',
                'api_key' => '1ee2e0a1120cecee0efd8ea9e5c873e438065b9b',
                'type' => 'telepro',
                'is_active' => true,
                'description' => 'Clé API pour le téléprospecteur 3',
            ],
        ];

        foreach ($keys as $key) {
            RingoverApiKey::updateOrCreate(
                ['api_key' => $key['api_key']],
                $key
            );
        }

        $this->command->info('Clés API Ringover créées avec succès.');
        $this->command->info('Clés créées : ' . count($keys));
    }
}
