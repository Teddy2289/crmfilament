<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'nom',
        'cle',
        'sujet',
        'corps',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public static function findByCle(string $cle): ?self
    {
        return static::where('cle', $cle)->where('actif', true)->first();
    }

    public function renderSujet(array $variables): string
    {
        return $this->remplacerVariables($this->sujet, $variables);
    }

    public function renderCorps(array $variables): string
    {
        return $this->remplacerVariables($this->corps, $variables);
    }

    private function remplacerVariables(string $texte, array $variables): string
    {
        foreach ($variables as $cle => $valeur) {
            $texte = str_replace('{{' . $cle . '}}', (string) $valeur, $texte);
        }
        return $texte;
    }
}
