<?php

namespace App\Events;

use App\Models\Prospect;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Mail2EnvoyeEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public Prospect $prospect) {}
}
