<?php

namespace Database\Seeders;

use App\Models\Webhook;
use Illuminate\Database\Seeder;

class WebhooksSeeder extends Seeder
{
    public function run(): void
    {
        $webhooks = [
            [
                'name' => 'Webhook Appels Ringover',
                'url' => 'https://api.example.com/ringover/calls',
                'event' => 'call.started',
                'is_active' => true,
                'description' => 'Webhook pour les appels démarrés via Ringover',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => 'CRM-Filament',
                ],
                'secret' => 'ringover_call_secret_123',
            ],
            [
                'name' => 'Webhook Appels Terminés',
                'url' => 'https://api.example.com/ringover/calls/ended',
                'event' => 'call.ended',
                'is_active' => true,
                'description' => 'Webhook pour les appels terminés',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => 'CRM-Filament',
                ],
                'secret' => 'ringover_ended_secret_456',
            ],
            [
                'name' => 'Webhook Appels Manqués',
                'url' => 'https://api.example.com/ringover/calls/missed',
                'event' => 'call.missed',
                'is_active' => true,
                'description' => 'Webhook pour les appels manqués',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => 'CRM-Filament',
                ],
                'secret' => 'ringover_missed_secret_789',
            ],
            [
                'name' => 'Webhook Création Client',
                'url' => 'https://api.example.com/clients/created',
                'event' => 'client.created',
                'is_active' => true,
                'description' => 'Webhook pour la création de nouveaux clients',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => 'CRM-Filament',
                ],
                'secret' => 'client_created_secret_abc',
            ],
            [
                'name' => 'Webhook Création Prospect',
                'url' => 'https://api.example.com/prospects/created',
                'event' => 'prospect.created',
                'is_active' => true,
                'description' => 'Webhook pour la création de nouveaux prospects',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => 'CRM-Filament',
                ],
                'secret' => 'prospect_created_secret_def',
            ],
            [
                'name' => 'Webhook Création RDV',
                'url' => 'https://api.example.com/rdv/created',
                'event' => 'rdv.created',
                'is_active' => true,
                'description' => 'Webhook pour la création de rendez-vous',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => 'CRM-Filament',
                ],
                'secret' => 'rdv_created_secret_ghi',
            ],
        ];

        foreach ($webhooks as $webhook) {
            Webhook::updateOrCreate(
                ['name' => $webhook['name']],
                $webhook
            );
        }
    }
}
