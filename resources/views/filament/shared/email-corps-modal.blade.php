<div class="p-4 prose max-w-none dark:prose-invert">
    @if($email->corps)
        @if(str_contains($email->corps, '<'))
            {!! $email->corps !!}
        @else
            {{-- Ancien envoi en texte brut : déjà échappé par EmailTemplate::renderCorps(). --}}
            <pre class="whitespace-pre-wrap text-sm font-sans">{!! $email->corps !!}</pre>
        @endif
    @else
        <p class="text-gray-500">Aucun contenu disponible.</p>
    @endif
</div>
