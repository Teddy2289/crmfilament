<?php

namespace App\Filament\NsConseil\Resources\ChatRoomResource\Pages;

use App\Filament\NsConseil\Resources\ChatRoomResource;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;

class ChatRoomChat extends Page
{
    protected static string $resource = ChatRoomResource::class;

    protected static string $view = 'filament.ns-conseil.resources.chat-room-resource.pages.chat-room-chat';

    public $chatRoom;
    public $messages;
    public $newMessage = '';

    public function mount($record): void
    {
        $this->chatRoom = ChatRoomResource::getModel()::findOrFail($record);
        $this->messages = $this->chatRoom->messages()->with('user')->get();
        
        // Marquer comme lu
        $participant = $this->chatRoom->participants()
            ->where('user_id', Auth::id())
            ->first();
        
        if ($participant) {
            $participant->markAsRead();
        }
    }

    public function sendMessage(): void
    {
        $this->validate([
            'newMessage' => 'required|string|max:5000',
        ]);

        $this->chatRoom->messages()->create([
            'user_id' => Auth::id(),
            'contenu' => $this->newMessage,
        ]);

        $this->newMessage = '';
        $this->messages = $this->chatRoom->messages()->with('user')->get();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Textarea::make('newMessage')
                ->label('Nouveau message')
                ->required()
                ->rows(2)
                ->maxLength(5000),
        ];
    }
}
