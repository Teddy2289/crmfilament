<?php

namespace App\Services;

use App\Models\EnvSetting;
use Illuminate\Support\Facades\File;

class EnvSettingsService
{
    protected string $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    /**
     * Synchronise toutes les variables de la base de données vers le fichier .env
     */
    public function syncToEnv(): void
    {
        $settings = EnvSetting::where('is_editable', true)->get();
        
        if ($settings->isEmpty()) {
            return;
        }

        $envContent = File::get($this->envPath);
        $lines = explode("\n", $envContent);
        $updatedLines = [];
        $processedKeys = [];

        foreach ($lines as $line) {
            if (empty(trim($line)) || str_starts_with(trim($line), '#')) {
                $updatedLines[] = $line;
                continue;
            }

            $key = $this->extractKeyFromLine($line);
            
            if ($key && $this->isManagedKey($key, $settings)) {
                $setting = $settings->firstWhere('key', $key);
                if ($setting) {
                    $updatedLines[] = $this->formatEnvLine($setting->key, $setting->value);
                    $processedKeys[] = $key;
                }
            } else {
                $updatedLines[] = $line;
            }
        }

        // Ajouter les nouvelles variables qui ne sont pas encore dans le fichier
        foreach ($settings as $setting) {
            if (! in_array($setting->key, $processedKeys, true)) {
                $updatedLines[] = $this->formatEnvLine($setting->key, $setting->value);
            }
        }

        File::put($this->envPath, implode("\n", $updatedLines));
    }

    /**
     * Importe les variables du fichier .env vers la base de données
     */
    public function importFromEnv(): void
    {
        $envContent = File::get($this->envPath);
        $lines = explode("\n", $envContent);

        foreach ($lines as $line) {
            $key = $this->extractKeyFromLine($line);
            $value = $this->extractValueFromLine($line);

            if ($key && $value !== null) {
                EnvSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'label' => $this->generateLabelFromKey($key),
                        'group' => $this->guessGroupFromKey($key),
                        'type' => $this->guessTypeFromValue($value),
                    ]
                );
            }
        }
    }

    /**
     * Extrait la clé d'une ligne .env
     */
    protected function extractKeyFromLine(string $line): ?string
    {
        if (! str_contains($line, '=')) {
            return null;
        }

        $parts = explode('=', $line, 2);
        return trim($parts[0]);
    }

    /**
     * Extrait la valeur d'une ligne .env
     */
    protected function extractValueFromLine(string $line): ?string
    {
        if (! str_contains($line, '=')) {
            return null;
        }

        $parts = explode('=', $line, 2);
        return isset($parts[1]) ? trim($parts[1]) : null;
    }

    /**
     * Vérifie si une clé est gérée par le système
     */
    protected function isManagedKey(string $key, \Illuminate\Database\Eloquent\Collection $settings): bool
    {
        return $settings->contains('key', $key);
    }

    /**
     * Formate une ligne pour le fichier .env
     */
    protected function formatEnvLine(string $key, string $value): string
    {
        return "{$key}={$value}";
    }

    /**
     * Génère un libellé à partir d'une clé
     */
    protected function generateLabelFromKey(string $key): string
    {
        return str_replace('_', ' ', ucwords(str_replace('_', ' ', strtolower($key))));
    }

    /**
     * Devine le groupe à partir de la clé
     */
    protected function guessGroupFromKey(string $key): string
    {
        $keyLower = strtolower($key);

        if (str_contains($keyLower, 'db') || str_contains($keyLower, 'database')) {
            return 'database';
        }

        if (str_contains($keyLower, 'mail')) {
            return 'mail';
        }

        if (str_contains($keyLower, 'cache') || str_contains($keyLower, 'redis')) {
            return 'cache';
        }

        if (str_contains($keyLower, 'queue')) {
            return 'queue';
        }

        if (str_contains($keyLower, 'storage') || str_contains($keyLower, 'filesystem') || str_contains($keyLower, 's3')) {
            return 'storage';
        }

        if (str_contains($keyLower, 'key') || str_contains($keyLower, 'secret') || str_contains($keyLower, 'password')) {
            return 'security';
        }

        return 'general';
    }

    /**
     * Devine le type à partir de la valeur
     */
    protected function guessTypeFromValue(string $value): string
    {
        if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
            return 'int';
        }

        if (in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no'], true)) {
            return 'bool';
        }

        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            return 'json';
        }

        return 'string';
    }

    /**
     * Vide le cache de configuration
     */
    public function clearConfigCache(): void
    {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
    }
}
