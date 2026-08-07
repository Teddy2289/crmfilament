<?php

namespace Tests\Unit\Filament\NsConseil;

use App\Filament\NsConseil\Concerns\HasDashboardDateRange;
use PHPUnit\Framework\TestCase;

class HasDashboardDateRangeTest extends TestCase
{
    public function test_it_uses_default_month_range_when_filters_are_empty(): void
    {
        $widget = new class {
            use HasDashboardDateRange;

            public array $filters = [];
        };

        [$startDate, $endDate] = $widget->getDashboardDateRange();

        $this->assertSame(now()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s'), $startDate->format('Y-m-d H:i:s'));
        $this->assertSame(now()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s'));
    }

    public function test_it_parses_custom_filter_dates(): void
    {
        $widget = new class {
            use HasDashboardDateRange;

            public array $filters = [
                'startDate' => '2026-08-01',
                'endDate' => '2026-08-31',
            ];
        };

        [$startDate, $endDate] = $widget->getDashboardDateRange();

        $this->assertSame('2026-08-01', $startDate->format('Y-m-d'));
        $this->assertSame('2026-08-31', $endDate->format('Y-m-d'));
    }
}
