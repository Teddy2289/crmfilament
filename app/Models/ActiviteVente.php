<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Activité VENTE liée à un partenaire + consultant.
 * (anciennement fusionné avec permanence — maintenant séparé selon le MEA)
 */
class ActiviteVente extends Model
{
    protected $table = 'activite_ventes';

    protected $fillable = [
        'partenaire_id',
        'consultant_id',
        'nombre_ventes_total',
        'derniere_vente',
        'ventes_2025',
        'ventes_2026',
    ];

    protected $casts = [
        'nombre_ventes_total' => 'integer',
        'ventes_2025' => 'integer',
        'ventes_2026' => 'integer',
        'derniere_vente' => 'date',
    ];

    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'partenaire_id', 'partenaire_id');
    }

    public function dossiersFormation()
    {
        return $this->hasManyThrough(
            DossierFormation::class,
            Client::class,
            'partenaire_id',
            'personne_id',
            'partenaire_id',
            'id'
        );
    }

    public static function calculerDepuisClients(?int $partenaireId): array
    {
        if (! $partenaireId) {
            return [
                'nombre_ventes_total' => 0,
                'derniere_vente' => null,
                'ventes_2025' => 0,
                'ventes_2026' => 0,
            ];
        }

        $query = DossierFormation::query()
            ->whereNotNull('date_vente')
            ->whereHas('personne', fn ($client) => $client->where('partenaire_id', $partenaireId));

        return [
            'nombre_ventes_total' => (clone $query)->count(),
            'derniere_vente' => (clone $query)->max('date_vente'),
            'ventes_2025' => (clone $query)->whereYear('date_vente', 2025)->count(),
            'ventes_2026' => (clone $query)->whereYear('date_vente', 2026)->count(),
        ];
    }

    public static function actualiserPourPartenaire(?int $partenaireId): ?self
    {
        if (! $partenaireId) {
            return null;
        }

        $partenaire = Partenaire::query()->find($partenaireId);
        if (! $partenaire) {
            return null;
        }

        $activite = static::query()->firstOrNew(['partenaire_id' => $partenaireId]);

        if (! $activite->consultant_id && $partenaire->conseiller_id) {
            $activite->consultant_id = $partenaire->conseiller_id;
        }

        $activite->recalculerDepuisClients();

        return $activite->refresh();
    }

    public function recalculerDepuisClients(): self
    {
        $totaux = static::calculerDepuisClients($this->partenaire_id);

        if (! $this->consultant_id && $this->partenaire?->conseiller_id) {
            $this->consultant_id = $this->partenaire->conseiller_id;
        }

        $this->forceFill($totaux)->save();

        $this->partenaire?->updateQuietly([
            'nombre_ventes_liees' => $this->nombre_ventes_total,
        ]);

        return $this;
    }
}
