<?php

namespace Database\Seeders;

use App\Models\EnvSetting;
use Illuminate\Database\Seeder;

class EnvSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Général
            [
                'key' => 'APP_NAME',
                'label' => 'Nom de l\'application',
                'group' => 'general',
                'description' => 'Nom affiché dans l\'application',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'APP_ENV',
                'label' => 'Environnement',
                'group' => 'general',
                'description' => 'Environnement d\'exécution (local, production, staging)',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'APP_DEBUG',
                'label' => 'Mode debug',
                'group' => 'general',
                'description' => 'Activer le mode debug pour le développement',
                'type' => 'bool',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 3,
            ],
            [
                'key' => 'APP_URL',
                'label' => 'URL de l\'application',
                'group' => 'general',
                'description' => 'URL principale de l\'application',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'APP_LOCALE',
                'label' => 'Langue par défaut',
                'group' => 'general',
                'description' => 'Langue par défaut de l\'application',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 5,
            ],

            // Base de données
            [
                'key' => 'DB_CONNECTION',
                'label' => 'Connexion DB',
                'group' => 'database',
                'description' => 'Type de connexion de base de données',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 10,
            ],
            [
                'key' => 'DB_HOST',
                'label' => 'Hôte DB',
                'group' => 'database',
                'description' => 'Adresse du serveur de base de données',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 11,
            ],
            [
                'key' => 'DB_PORT',
                'label' => 'Port DB',
                'group' => 'database',
                'description' => 'Port du serveur de base de données',
                'type' => 'int',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 12,
            ],
            [
                'key' => 'DB_DATABASE',
                'label' => 'Nom de la base',
                'group' => 'database',
                'description' => 'Nom de la base de données',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 13,
            ],
            [
                'key' => 'DB_USERNAME',
                'label' => 'Utilisateur DB',
                'group' => 'database',
                'description' => 'Nom d\'utilisateur de la base de données',
                'type' => 'string',
                'is_sensitive' => true,
                'is_editable' => true,
                'sort_order' => 14,
            ],
            [
                'key' => 'DB_PASSWORD',
                'label' => 'Mot de passe DB',
                'group' => 'database',
                'description' => 'Mot de passe de la base de données',
                'type' => 'string',
                'is_sensitive' => true,
                'is_editable' => true,
                'sort_order' => 15,
            ],

            // Mail
            [
                'key' => 'MAIL_MAILER',
                'label' => 'Driver mail',
                'group' => 'mail',
                'description' => 'Driver d\'envoi d\'emails',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 20,
            ],
            [
                'key' => 'MAIL_HOST',
                'label' => 'Hôte SMTP',
                'group' => 'mail',
                'description' => 'Adresse du serveur SMTP',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 21,
            ],
            [
                'key' => 'MAIL_PORT',
                'label' => 'Port SMTP',
                'group' => 'mail',
                'description' => 'Port du serveur SMTP',
                'type' => 'int',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 22,
            ],
            [
                'key' => 'MAIL_USERNAME',
                'label' => 'Utilisateur SMTP',
                'group' => 'mail',
                'description' => 'Nom d\'utilisateur SMTP',
                'type' => 'string',
                'is_sensitive' => true,
                'is_editable' => true,
                'sort_order' => 23,
            ],
            [
                'key' => 'MAIL_PASSWORD',
                'label' => 'Mot de passe SMTP',
                'group' => 'mail',
                'description' => 'Mot de passe SMTP',
                'type' => 'string',
                'is_sensitive' => true,
                'is_editable' => true,
                'sort_order' => 24,
            ],
            [
                'key' => 'MAIL_ENCRYPTION',
                'label' => 'Encryption SMTP',
                'group' => 'mail',
                'description' => 'Méthode d\'encryption SMTP',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 25,
            ],
            [
                'key' => 'MAIL_FROM_ADDRESS',
                'label' => 'Adresse expéditeur',
                'group' => 'mail',
                'description' => 'Adresse email d\'envoi par défaut',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 26,
            ],
            [
                'key' => 'MAIL_FROM_NAME',
                'label' => 'Nom expéditeur',
                'group' => 'mail',
                'description' => 'Nom de l\'expéditeur par défaut',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 27,
            ],

            // Cache
            [
                'key' => 'CACHE_DRIVER',
                'label' => 'Driver cache',
                'group' => 'cache',
                'description' => 'Driver de cache',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 30,
            ],
            [
                'key' => 'REDIS_HOST',
                'label' => 'Hôte Redis',
                'group' => 'cache',
                'description' => 'Adresse du serveur Redis',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 31,
            ],
            [
                'key' => 'REDIS_PASSWORD',
                'label' => 'Mot de passe Redis',
                'group' => 'cache',
                'description' => 'Mot de passe Redis',
                'type' => 'string',
                'is_sensitive' => true,
                'is_editable' => true,
                'sort_order' => 32,
            ],
            [
                'key' => 'REDIS_PORT',
                'label' => 'Port Redis',
                'group' => 'cache',
                'description' => 'Port du serveur Redis',
                'type' => 'int',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 33,
            ],

            // Queue
            [
                'key' => 'QUEUE_CONNECTION',
                'label' => 'Connexion queue',
                'group' => 'queue',
                'description' => 'Driver de queue',
                'type' => 'string',
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 40,
            ],

            // Sécurité
            [
                'key' => 'APP_KEY',
                'label' => 'Clé application',
                'group' => 'security',
                'description' => 'Clé de chiffrement de l\'application',
                'type' => 'string',
                'is_sensitive' => true,
                'is_editable' => false,
                'sort_order' => 50,
            ],
        ];

        foreach ($settings as $setting) {
            EnvSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
