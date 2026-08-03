<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait HasModelValidation
{
    /**
     * Règles de validation par défaut
     * À surcharger dans les modèles pour définir des règles spécifiques
     */
    protected array $validationRules = [];

    /**
     * Messages de validation personnalisés
     */
    protected array $validationMessages = [];

    /**
     * Attributs personnalisés pour la validation
     */
    protected array $validationAttributes = [];

    /**
     * Valide les données du modèle avant sauvegarde
     */
    protected function validateModel(array $data = null): void
    {
        $data = $data ?? $this->getAttributes();

        if (empty($this->validationRules)) {
            return;
        }

        $validator = Validator::make($data, $this->validationRules, $this->validationMessages, $this->validationAttributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Hook de validation avant création
     */
    protected static function bootHasModelValidation(): void
    {
        static::creating(function ($model) {
            $model->validateModel();
        });

        static::updating(function ($model) {
            $model->validateModel($model->getDirty());
        });
    }

    /**
     * Définit les règles de validation pour les emails
     */
    protected function emailRules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * Définit les règles de validation pour les téléphones
     */
    protected function phoneRules(): array
    {
        return [
            'telephone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\+\-\s\(\)]+$/'],
        ];
    }

    /**
     * Définit les règles de validation pour les noms
     */
    protected function nameRules(): array
    {
        return [
            'nom' => ['nullable', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Définit les règles de validation pour les adresses
     */
    protected function addressRules(): array
    {
        return [
            'adresse' => ['nullable', 'string', 'max:500'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'ville' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'departement' => ['nullable', 'string', 'max:10'],
        ];
    }

    /**
     * Définit les règles de validation pour les dates
     */
    protected function dateRules(): array
    {
        return [
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'date_signature' => ['nullable', 'date'],
        ];
    }

    /**
     * Définit les règles de validation pour les montants
     */
    protected function amountRules(): array
    {
        return [
            'montant_cpf' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'chiffre_affaires' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Définit les règles de validation pour les SIRET
     */
    protected function siretRules(): array
    {
        return [
            'siret' => ['nullable', 'string', 'size:14', 'regex:/^[0-9]{14}$/'],
        ];
    }
}
