<?php

namespace App\Filament\NsConseil\Resources\ChatRoomResource\Pages;

use App\Filament\NsConseil\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use App\Models\ChatRoomParticipant;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateChatRoom extends CreateRecord
{
    protected static string $resource = ChatRoomResource::class;

    protected function afterCreate(): void
    {
        $chatRoom = $this->record;
        
        // Ajouter le créateur comme participant admin
        $chatRoom->addParticipant(Auth::user(), 'admin');
        
        // Ajouter les autres participants si fournis
        if (isset($this->data['participants'])) {
            foreach ($this->data['participants'] as $userId) {
                if ($userId != Auth::id()) {
                    $chatRoom->addParticipant(\App\Models\User::find($userId), 'member');
                }
            }
        }
    }
}
