<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Filament\NsConseil\Resources\ProspectResource;
use Filament\Resources\Pages\Page;

class ProspectKanban extends Page
{
    protected static string $resource = ProspectResource::class;

    protected static string $view = 'livewire.prospect-kanban';

    protected static ?string $navigationLabel = 'Pipeline Kanban';

    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $title = 'Pipeline Prospects';
}
