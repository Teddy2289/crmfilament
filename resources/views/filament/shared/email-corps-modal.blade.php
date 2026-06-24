<div class="p-4 prose max-w-none dark:prose-invert">
    @if($email->corps)
        <pre class="whitespace-pre-wrap text-sm font-sans">{{ $email->corps }}</pre>
    @else
        <p class="text-gray-500">Aucun contenu disponible.</p>
    @endif
</div>
