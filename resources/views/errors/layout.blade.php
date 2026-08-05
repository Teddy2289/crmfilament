@props(["pageTitle", "statusCode", "message", "description"])

<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} — CRM NS Conseil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="h-full bg-slate-50">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-4xl rounded-[2rem] border border-slate-200 bg-white shadow-2xl shadow-slate-200/70 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-fuchsia-600 p-10 text-white">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white/15 text-white shadow-lg shadow-black/10">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 9v4"></path>
                                <path d="M12 17h.01"></path>
                                <path d="M21 12A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-white/70">Erreur {{ $statusCode }}</p>
                            <h1 class="mt-3 text-4xl font-semibold leading-tight">{{ $pageTitle }}</h1>
                        </div>
                    </div>
                    <div class="rounded-3xl border border-white/20 bg-white/10 px-5 py-4 text-sm text-white/90 shadow-lg shadow-black/10">
                        <p class="font-medium">Retournez à l'accueil ou réessayez plus tard.</p>
                    </div>
                </div>
            </div>
            <div class="p-10 sm:p-12">
                <p class="text-lg text-slate-700">{{ $message }}</p>
                <p class="mt-4 text-sm leading-6 text-slate-500">{{ $description }}</p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="/" class="inline-flex items-center justify-center rounded-full bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800">Retour à l'accueil</a>
                    <a href="javascript:history.back()" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-slate-50">Revenir en arrière</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
