<div class="space-y-4">
    <p class="text-sm text-gray-600 dark:text-gray-300">Les deux fichiers restent liés au prospect et peuvent être ouverts séparément.</p>
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Document Word</div>
            @if ($docx)
                <div class="mt-2 truncate text-sm font-medium">{{ $docx->nom_fichier }}</div>
                <a class="mt-3 inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white" href="{{ Storage::disk('public')->url($docx->path) }}" target="_blank" rel="noopener">Ouvrir le Word</a>
            @else
                <div class="mt-2 text-sm text-gray-500">Aucun Word lié.</div>
            @endif
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Document PDF</div>
            @if ($appel?->fiche_word_path)
                <div class="mt-2 truncate text-sm font-medium">{{ basename(parse_url($appel->fiche_word_path, PHP_URL_PATH) ?: $appel->fiche_word_path) }}</div>
                <a class="mt-3 inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white" href="{{ $appel->fiche_word_path }}" target="_blank" rel="noopener">Ouvrir le PDF</a>
            @else
                <div class="mt-2 text-sm text-gray-500">Aucun PDF lié.</div>
            @endif
        </div>
    </div>
</div>
