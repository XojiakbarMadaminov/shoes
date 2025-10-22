<?php

namespace App\Filament\Widgets;

use App\Models\Debtor;
use App\Models\Purchase;
use App\Models\SupplierDebt;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class SupplierDebtsOverview extends BaseWidget
{
    use HasWidgetShield;

    protected function getCards(): array
    {
        $debts = SupplierDebt::query()->sum('amount');

        return [
            Stat::make('Jami qarzdorlik', number_format($debts ?? 0, 0, '.', ' ') . " so'm"),
        ];
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
}
