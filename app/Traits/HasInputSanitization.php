<?php

namespace App\Traits;

trait HasInputSanitization
{
    /**
     * Champs à nettoyer automatiquement
     */
    protected array $sanitizeFields = [];

    /**
     * Hook de sanitization avant sauvegarde
     */
    protected static function bootHasInputSanitization(): void
    {
        static::saving(function ($model) {
            $model->sanitizeInputs();
        });
    }

    /**
     * Nettoie les inputs du modèle
     */
    protected function sanitizeInputs(): void
    {
        foreach ($this->sanitizeFields as $field) {
            if (isset($this->$field)) {
                $this->$field = $this->sanitizeField($this->$field, $field);
            }
        }
    }

    /**
     * Nettoie un champ spécifique selon son type
     */
    protected function sanitizeField($value, string $field)
    {
        if (is_string($value)) {
            return $this->sanitizeString($value, $field);
        }

        if (is_array($value)) {
            return $this->sanitizeArray($value, $field);
        }

        return $value;
    }

    /**
     * Nettoie une chaîne de caractères
     */
    protected function sanitizeString(string $value, string $field): string
    {
        // Supprimer les espaces en trop
        $value = preg_replace('/\s+/', ' ', $value);

        // Supprimer les caractères de contrôle invisibles
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);

        // Nettoyer selon le type de champ
        if (str_contains($field, 'email')) {
            return $this->sanitizeEmail($value);
        }

        if (str_contains($field, 'telephone') || str_contains($field, 'tel')) {
            return $this->sanitizePhone($value);
        }

        if (str_contains($field, 'nom') || str_contains($field, 'prenom') || str_contains($field, 'ville')) {
            return $this->sanitizeName($value);
        }

        if (str_contains($field, 'siret')) {
            return $this->sanitizeSiret($value);
        }

        // Nettoyage par défaut
        return trim($value);
    }

    /**
     * Nettoie un tableau
     */
    protected function sanitizeArray(array $value, string $field): array
    {
        return array_map(function ($item) use ($field) {
            return $this->sanitizeField($item, $field);
        }, $value);
    }

    /**
     * Nettoie un email
     */
    protected function sanitizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Nettoie un numéro de téléphone
     */
    protected function sanitizePhone(string $phone): string
    {
        // Garder uniquement les chiffres, +, -, (, ) et espaces
        $phone = preg_replace('/[^0-9\+\-\(\)\s]/', '', $phone);
        
        return trim($phone);
    }

    /**
     * Nettoie un nom (première lettre en majuscule)
     */
    protected function sanitizeName(string $name): string
    {
        $name = trim($name);
        
        // Mettre la première lettre de chaque mot en majuscule
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
        
        return $name;
    }

    /**
     * Nettoie un numéro SIRET
     */
    protected function sanitizeSiret(string $siret): string
    {
        // Garder uniquement les chiffres
        $siret = preg_replace('/[^0-9]/', '', $siret);
        
        return trim($siret);
    }

    /**
     * Nettoie un code postal
     */
    protected function sanitizeCodePostal(string $codePostal): string
    {
        // Garder uniquement les chiffres
        $codePostal = preg_replace('/[^0-9]/', '', $codePostal);
        
        return trim($codePostal);
    }

    /**
     * Nettoie un commentaire ou texte libre
     */
    protected function sanitizeText(string $text): string
    {
        // Supprimer les balises HTML
        $text = strip_tags($text);
        
        // Convertir les entités HTML
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        return trim($text);
    }

    /**
     * Définit les champs à nettoyer pour les modèles de personnes
     */
    protected function setPersonneSanitizeFields(): void
    {
        $this->sanitizeFields = [
            'nom',
            'prenom',
            'email',
            'telephone',
            'adresse',
            'code_postal',
            'ville',
            'region',
            'departement',
        ];
    }

    /**
     * Définit les champs à nettoyer pour les modèles d'entreprises
     */
    protected function setEntrepriseSanitizeFields(): void
    {
        $this->sanitizeFields = [
            'nom',
            'entreprise',
            'raison_sociale',
            'siret',
            'email',
            'telephone',
            'adresse',
            'code_postal',
            'ville',
            'region',
            'departement',
            'notes',
            'commentaires',
        ];
    }
}
