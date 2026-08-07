@php
    $statusCode = $status ?? 500;
    $pageTitle  = match ($statusCode) {
        400     => 'Requête incorrecte',
        401     => 'Non autorisé',
        403     => 'Accès refusé',
        404     => 'Page introuvable',
        419     => 'Session expirée',
        422     => 'Données invalides',
        429     => 'Trop de requêtes',
        503     => 'Service indisponible',
        default => 'Erreur serveur',
    };
    $errorMessage = $message ?? 'Une erreur est survenue. Veuillez réessayer ou contacter l\'administrateur.';
    $description  = 'Retournez à l\'accueil ou réessayez dans quelques instants.';
@endphp

@include('errors.layout', [
    'pageTitle'   => $pageTitle,
    'statusCode'  => $statusCode,
    'message'     => $errorMessage,
    'description' => $description,
])
