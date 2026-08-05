@php
    $pageTitle = 'Accès refusé';
    $statusCode = 403;
    $message = 'Vous n’êtes pas autorisé à accéder à cette page.';
    $description = 'Retournez à l’accueil pour choisir une autre section ou contactez un administrateur si nécessaire.';
@endphp

@include('errors.layout', compact('pageTitle', 'statusCode', 'message', 'description'))
