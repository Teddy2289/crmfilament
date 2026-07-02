<?php
namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class GoogleEventModal extends Component
{
    public bool $show = false;
    public array $eventData = [];

    #[On('show-google-event')]
    public function open(array $eventData): void
    {
        $this->eventData = $eventData;
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->eventData = [];
    }

    public function render()
    {
        return view('livewire.google-event-modal');
    }
}