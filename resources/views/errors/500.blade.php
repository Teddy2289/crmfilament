@php
    $pageTitle = 'Erreur serveur';
    $statusCode = 500;
    $message = 'Une erreur interne est survenue sur le serveur.';
    $description = 'Notre équipe a été informée. Revenez à l’accueil ou réessayez dans quelques instants.';
@endphp

@include('errors.layout', compact('pageTitle', 'statusCode', 'message', 'description'))
