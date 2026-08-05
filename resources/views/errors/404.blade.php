@php
    $pageTitle = 'Page non trouvée';
    $statusCode = 404;
    $message = 'L’URL demandée n’existe pas ou a peut-être été déplacée.';
    $description = 'Vérifiez l’adresse ou revenez à l’accueil pour reprendre votre navigation.';
@endphp

@include('errors.layout', compact('pageTitle', 'statusCode', 'message', 'description'))
