<?php

namespace App\Filament\Widgets;

use App\Models\Debtor;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class DebtorStatsOverview extends BaseWidget
{
    use HasWidgetShield;

    protected function getCards(): array
    {
        $debts = Debtor::query()
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        return [
            Stat::make('Jami qarz summa', number_format($debts['uzs'] ?? 0, 0, '.', ' ') . " so'm"),
        ];
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
}
