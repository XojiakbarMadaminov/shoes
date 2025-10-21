<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpensesTodayOverview extends BaseWidget
{
    use HasWidgetShield;
    protected function getCards(): array
    {
        $total = (float) Expense::query()
            ->whereDate('date', today())
            ->sum('amount');

        $formatted = number_format($total) . " so'm";

        return [
            Stat::make('Bugungi xarajatlar', $formatted)
                ->color('danger'),
        ];
    }
}

