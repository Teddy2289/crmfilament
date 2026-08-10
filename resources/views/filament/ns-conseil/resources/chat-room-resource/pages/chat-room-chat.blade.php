<x-filament-panels::page>
    <x-slot name="header">
        <h2 class="text-2xl font-bold">Chat: {{ $chatRoom->nom ?? 'Conversation' }}</h2>
    </x-slot>

    <div class="space-y-4">
        <!-- Zone de messages -->
        <div class="bg-white rounded-lg shadow p-4 h-96 overflow-y-auto" id="messages-container">
            @forelse($messages as $message)
                <div class="mb-4 {{ $message->user_id === auth()->id() ? 'text-right' : 'text-left' }}">
                    <div class="inline-block max-w-md">
                        <div class="text-xs text-gray-500 mb-1">
                            {{ $message->user->name }} - {{ $message->created_at->format('H:i') }}
                        </div>
                        <div class="{{ $message->user_id === auth()->id() ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' }} rounded-lg px-4 py-2">
                            {{ $message->contenu }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-500 py-8">
                    Aucun message pour le moment. Soyez le premier à écrire !
                </div>
            @endforelse
        </div>

        <!-- Formulaire d'envoi -->
        <form wire:submit="sendMessage" class="flex gap-2">
            <input 
                type="text" 
                wire:model="newMessage" 
                placeholder="Écrivez votre message..." 
                class="flex-1 border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                maxlength="5000"
            >
            <button 
                type="submit" 
                class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition"
            >
                Envoyer
            </button>
        </form>

        @error('newMessage')
            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <script>
        // Auto-scroll vers le bas
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });

        // Polling pour nouveaux messages (optionnel)
        @this.on('message-sent', function() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    </script>
</x-filament-panels::page>
