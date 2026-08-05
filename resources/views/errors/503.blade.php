@php
    $pageTitle = 'Service indisponible';
    $statusCode = 503;
    $message = 'Le service est temporairement indisponible.';
    $description = 'Veuillez revenir plus tard. Vous pouvez aussi retourner à l’accueil pour continuer.';
@endphp

@include('errors.layout', compact('pageTitle', 'statusCode', 'message', 'description'))
