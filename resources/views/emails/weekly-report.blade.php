@php
    /** @var array $rapport */
    [$debut, $fin] = $rapport['periode'];
@endphp
<x-mail::message>
# Rapport hebdomadaire CRM

Bonjour {{ $rapport['user']->prenom }},

Voici votre récapitulatif pour la semaine du **{{ $debut->format('d/m/Y') }}** au **{{ $fin->format('d/m/Y') }}**.

@if ($rapport['role'] === 'teleprospecteur')
## Activité de la semaine

<x-mail::table>
| Indicateur | Valeur |
|:---|:---:|
| Appels passés | {{ $rapport['appels_semaine'] }} |
| CSE joints | {{ $rapport['cse_joints'] ?? '—' }} |
| RDV QF validés | {{ $rapport['qf'] }} |
| Taux de conversion | {{ $rapport['taux_conversion'] ?? '—' }}% |
</x-mail::table>

## Statuts en cours

<x-mail::table>
| Indicateur | Valeur |
|:---|:---:|
| AC — À contacter | {{ $rapport['base_ac'] ?? '—' }} fiches restantes |
| RP — Rappels planifiés | {{ $rapport['rp'] ?? '—' }} (dont {{ $rapport['rappels_aujourd_hui'] ?? 0 }} aujourd'hui) |
| RPC — À planifier | {{ $rapport['rpc'] ?? '—' }} |
</x-mail::table>

## Résultats

<x-mail::table>
| Indicateur | Valeur |
|:---|:---:|
| STD-NR | {{ $rapport['std_nr'] ?? '—' }} |
| KO / Refus | {{ $rapport['ko'] ?? '—' }} |
</x-mail::table>

@if (!empty($rapport['prochains_rappels']) && $rapport['prochains_rappels']->count() > 0)
## Prochains rappels (cette semaine)

<x-mail::table>
| Date | Nom | Téléphone |
|:---|:---|:---|
@foreach ($rapport['prochains_rappels'] as $rappel)
| {{ $rappel->rappel_planifie_at->format('d/m H:i') }} | {{ $rappel->nom }} | {{ $rappel->telephone }} |
@endforeach
</x-mail::table>
@endif

## Pipeline prospects
<x-mail::table>
| Statut | Nombre |
|:---|:---:|
@foreach (($rapport['prospects_par_statut'] ?? []) as $statut => $nombre)
| {{ $statut }} | {{ $nombre }} |
@endforeach
</x-mail::table>
@elseif ($rapport['role'] === 'commercial')
## Partenaires actifs ({{ $rapport['partenaires_actifs_count'] ?? $rapport['partenaires_actifs']->count() }})

@if ($rapport['partenaires_actifs'] instanceof \Illuminate\Support\Collection && $rapport['partenaires_actifs']->count() > 0)
<x-mail::table>
| Nom | Statut |
|:---|:---|
@foreach ($rapport['partenaires_actifs']->take(10) as $p)
| {{ $p->nom }} | {{ $p->statut }} |
@endforeach
</x-mail::table>
@endif

## RDV semaine passée

<x-mail::table>
| | Nombre |
|:---|:---:|
| Réalisés | {{ $rapport['rdv_realises'] ?? '—' }} |
| Annulés | {{ $rapport['rdv_annules'] ?? '—' }} |
| Décalés | {{ $rapport['rdv_decales'] ?? '—' }} |
| No-show | {{ $rapport['taux_no_show'] ?? '—' }}% |
</x-mail::table>

@if (!empty($rapport['rdv_a_venir']) && (is_countable($rapport['rdv_a_venir']) ? count($rapport['rdv_a_venir']) : $rapport['rdv_a_venir']->count()) > 0)
## RDV semaine à venir

<x-mail::table>
| Date | Interlocuteur | Lieu |
|:---|:---|:---|
@foreach ($rapport['rdv_a_venir'] as $rdv)
| {{ $rdv->date_heure->format('d/m H:i') }} | {{ $rdv->interlocuteur_nom ?? '—' }} | {{ $rdv->lieu ?? $rdv->adresse_lieu ?? '—' }} |
@endforeach
</x-mail::table>
@endif

## Prospects en attente

- RP / RPC à relancer : **{{ $rapport['prospects_en_attente'] ?? '—' }}**
- Nouveaux prospects : **{{ $rapport['nouveaux_prospects'] ?? '—' }}**

@elseif ($rapport['role'] === 'team_leader')
## Consolidé Phoning

@if (!empty($rapport['phoning_consolide']))
<x-mail::table>
| Téléprospecteur | Appels | CSE joints | QF | Taux conv. | Base AC |
|:---|:---:|:---:|:---:|:---:|:---:|
@foreach ($rapport['phoning_consolide'] as $tp)
| {{ $tp['user']->prenom }} {{ $tp['user']->nom }} | {{ $tp['appels_semaine'] }} | {{ $tp['cse_joints'] }} | {{ $tp['qf'] }} | {{ $tp['taux_conversion'] }}% | {{ $tp['base_ac'] }} |
@endforeach
</x-mail::table>
@endif

## Consolidé Commerciaux

@if (!empty($rapport['commercial_consolide']))
<x-mail::table>
| Commercial | RDV sem. | Réalisés | Annulés | No-show | Partenaires |
|:---|:---:|:---:|:---:|:---:|:---:|
@foreach ($rapport['commercial_consolide'] as $com)
| {{ $com['user']->prenom }} {{ $com['user']->nom }} | {{ $com['rdv_total'] }} | {{ $com['rdv_realises'] }} | {{ $com['rdv_annules'] }} | {{ $com['taux_no_show'] }}% | {{ $com['partenaires_actifs_count'] }} |
@endforeach
</x-mail::table>
@endif

## Alertes

@if (!empty($rapport['alertes']))
@if ($rapport['alertes']['tp_sans_appel_2j']->count() > 0)
- **TP sans appel 2j+ :** {{ $rapport['alertes']['tp_sans_appel_2j']->map(fn ($u) => $u->prenom.' '.$u->nom)->implode(', ') }}
@endif
- **RPC > 5j sans suite :** {{ $rapport['alertes']['rpc_sans_suite_5j'] }}
- **RP non traités :** {{ $rapport['alertes']['rp_non_traites'] }}
- **QF à valider :** {{ $rapport['alertes']['qf_a_valider'] }}
@endif
@endif
Bonne semaine,<br>
{{ config('app.name') }}
</x-mail::message>
