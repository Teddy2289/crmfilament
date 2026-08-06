<?php

namespace App\Traits;

use App\Support\DepartementHelper;

trait HasDepartementLabel
{
    public function getDepartementLabelAttribute(): string
    {
        return DepartementHelper::labelFormat($this->departement);
    }
}
