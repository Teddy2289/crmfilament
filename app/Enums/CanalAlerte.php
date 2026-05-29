<?php

namespace App\Enums;

enum CanalAlerte: string
{
    case SMS = 'SMS';
    case Appel = 'Appel';
    case LesDeux = 'Les deux';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match($this) {
            self::SMS => 'info',
            self::Appel => 'success',
            self::LesDeux => 'primary',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::SMS => 'heroicon-o-chat-bubble-left',
            self::Appel => 'heroicon-o-phone',
            self::LesDeux => 'heroicon-o-bell-alert',
        };
    }
}
