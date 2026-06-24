<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CRM NS Conseil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <!-- Logo ou Icône de substitution propre -->
        <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-200">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <h2 class="mt-6 text-3xl font-extrabold tracking-tight text-slate-900">CRM NS Conseil</h2>
        <p class="mt-2 text-sm text-slate-500">Bienvenue sur votre portail d'accès</p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-xl shadow-slate-100 rounded-2xl sm:px-10 border border-slate-100">
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-slate-900 mb-2">Sélectionnez votre espace :</h3>
                    <p class="text-xs text-slate-400 mb-4">Vous allez être redirigé vers l'interface de connexion correspondante.</p>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <!-- Espace NS Conseil -->
                    <a href="/ns-conseil" class="flex items-center p-4 rounded-xl border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50/20 transition-all group">
                        <div class="p-2.5 rounded-lg bg-indigo-50 group-hover:bg-indigo-100 text-indigo-600 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">Espace NS Conseil</span>
                            <span class="block text-xs text-slate-400 mt-0.5">Accès au CRM principal</span>
                        </div>
                        <svg class="h-5 w-5 text-slate-300 group-hover:text-indigo-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>

                    <!-- Espace AlloPro -->
                    <a href="/allopro" class="flex items-center p-4 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/20 transition-all group">
                        <div class="p-2.5 rounded-lg bg-emerald-50 group-hover:bg-emerald-100 text-emerald-600 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 00.996.81h4l.002-.001a1 1 0 00.996-.81l.547-2.2a1 1 0 01.94-.725H19a2 2 0 012 2v3a2 2 0 01-2 2h-3.28a1 1 0 01-.94-.725l-.547-2.2a1 1 0 00-.996-.81h-4a1 1 0 00-.996.81l-.547 2.2a1 1 0 01-.94.725H5a2 2 0 01-2-2V5z" />
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-emerald-600 transition-colors">Espace AlloPro</span>
                            <span class="block text-xs text-slate-400 mt-0.5">Accès au CRM d'appels</span>
                        </div>
                        <svg class="h-5 w-5 text-slate-300 group-hover:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>

                    <!-- Espace Super Admin -->
                    <a href="/super-admin" class="flex items-center p-4 rounded-xl border border-slate-200 hover:border-rose-500 hover:bg-rose-50/20 transition-all group">
                        <div class="p-2.5 rounded-lg bg-rose-50 group-hover:bg-rose-100 text-rose-600 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <span class="block text-sm font-semibold text-slate-900 group-hover:text-rose-600 transition-colors">Espace Super Admin</span>
                            <span class="block text-xs text-slate-400 mt-0.5">Administration système</span>
                        </div>
                        <svg class="h-5 w-5 text-slate-300 group-hover:text-rose-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
