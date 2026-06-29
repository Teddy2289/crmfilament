<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Pages;

use App\Filament\NsConseil\Resources\PartenaireResource;
use Filament\Resources\Pages\Page;

class PartenaireKanban extends Page
{
    protected static string $resource = PartenaireResource::class;

    protected static string $view = 'livewire.partenaire-kanban';

    protected static ?string $navigationLabel = 'Pipeline Kanban';

    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $title = 'Pipeline Partenaires';
}
