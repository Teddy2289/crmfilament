<?php

namespace App\Services;

use App\Models\RendezVous;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class CreneauPropositionService
{
    /**
     * Génère 2 propositions de créneaux pour un RDV
     * 
     * @param User $user Utilisateur concerné (commercial ou téléprospecteur)
     * @param Carbon $dateDebut Date de début de recherche (défaut: demain)
     * @param int $dureeMinutes Durée du RDV en minutes (défaut: 60)
     * @param int $nombrePropositions Nombre de créneaux à proposer (défaut: 2)
     * @return array Tableau de créneaux proposés
     */
    public function genererPropositions(
        User $user,
        ?Carbon $dateDebut = null,
        int $dureeMinutes = 60,
        int $nombrePropositions = 2
    ): array {
        $dateDebut = $dateDebut ?? Carbon::tomorrow()->startOfDay();
        $propositions = [];
        $joursVerifies = 0;
        $maxJours = 14; // Chercher sur 14 jours maximum

        $googleService = app(GoogleCalendarService::class);
        $estConnecteGoogle = $googleService->isConnected($user);

        while (count($propositions) < $nombrePropositions && $joursVerifies < $maxJours) {
            $jour = $dateDebut->copy()->addDays($joursVerifies);

            // Skip week-ends
            if ($jour->isWeekend()) {
                $joursVerifies++;
                continue;
            }

            // Récupérer les créneaux disponibles pour ce jour
            $creneauxJour = $this->getCreneauxDisponiblesJour(
                $jour,
                $dureeMinutes,
                $estConnecteGoogle ? $googleService : null,
                $user
            );

            $propositions = array_merge($propositions, $creneauxJour);
            $joursVerifies++;
        }

        // Limiter au nombre demandé
        return array_slice($propositions, 0, $nombrePropositions);
    }

    /**
     * Récupère les créneaux disponibles pour un jour donné
     */
    protected function getCreneauxDisponiblesJour(
        Carbon $jour,
        int $dureeMinutes,
        ?GoogleCalendarService $googleService,
        User $user
    ): array {
        $creneaux = [];
        $heureDebut = 9; // 9h
        $heureFin = 18; // 18h
        $intervalleMinutes = 30; // Créneaux de 30 min

        // Récupérer les événements Google Calendar si connecté
        $eventsGoogle = [];
        if ($googleService) {
            try {
                $debutJour = $jour->copy()->startOfDay();
                $finJour = $jour->copy()->endOfDay();
                $eventsGoogle = $googleService->getEvents($user, $debutJour, $finJour);
            } catch (\Throwable $e) {
                Log::warning('Impossible de récupérer les événements Google', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Générer les créneaux possibles
        for ($heure = $heureDebut; $heure < $heureFin; $heure++) {
            for ($minute = 0; $minute < 60; $minute += $intervalleMinutes) {
                $debutCreneau = $jour->copy()->setTime($heure, $minute, 0);
                $finCreneau = $debutCreneau->copy()->addMinutes($dureeMinutes);

                // Vérifier que le créneau ne dépasse pas l'heure de fin
                if ($finCreneau->hour >= $heureFin) {
                    continue;
                }

                // Vérifier que le créneau n'est pas dans le passé
                if ($debutCreneau->isPast()) {
                    continue;
                }

                // Vérifier la disponibilité avec Google Calendar
                if ($this->estDisponible($debutCreneau, $finCreneau, $eventsGoogle)) {
                    $creneaux[] = [
                        'debut' => $debutCreneau,
                        'fin' => $finCreneau,
                        'libelle' => $debutCreneau->format('d/m/Y à H:i'),
                        'duree' => $dureeMinutes,
                    ];
                }
            }
        }

        return $creneaux;
    }

    /**
     * Vérifie si un créneau est disponible (pas de chevauchement avec les événements Google)
     */
    protected function estDisponible(Carbon $debut, Carbon $fin, array $eventsGoogle): bool {
        if (empty($eventsGoogle)) {
            return true;
        }

        foreach ($eventsGoogle as $event) {
            // Parser les dates de l'événement Google
            $eventStart = isset($event['start']['dateTime'])
                ? Carbon::parse($event['start']['dateTime'])
                : (isset($event['start']['date']) ? Carbon::parse($event['start']['date'])->startOfDay() : null);
            
            $eventEnd = isset($event['end']['dateTime'])
                ? Carbon::parse($event['end']['dateTime'])
                : (isset($event['end']['date']) ? Carbon::parse($event['end']['date'])->endOfDay() : null);

            if (! $eventStart || ! $eventEnd) {
                continue;
            }

            // Vérifier le chevauchement
            if ($debut < $eventEnd && $fin > $eventStart) {
                return false; // Créneau occupé
            }
        }

        return true; // Créneau libre
    }

    /**
     * Formate les créneaux pour affichage dans un select Filament
     */
    public function formaterPourSelect(array $creneaux): array
    {
        return collect($creneaux)->mapWithKeys(function ($creneau) {
            $label = $creneau['libelle'];
            return [$creneau['debut']->toIso8601String() => $label];
        })->toArray();
    }
}
