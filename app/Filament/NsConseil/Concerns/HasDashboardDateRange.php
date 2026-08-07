<?php

namespace App\Filament\NsConseil\Concerns;

use Carbon\Carbon;

trait HasDashboardDateRange
{
    public function getDashboardDateRange(): array
    {
        $startDate = ! empty($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : now()->startOfMonth();

        $endDate = ! empty($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : now()->endOfMonth();

        return [$startDate, $endDate];
    }
}
