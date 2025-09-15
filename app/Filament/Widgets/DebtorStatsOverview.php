<?php

namespace App\Filament\Widgets;

use App\Models\Debtor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DebtorStatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        $debts = Debtor::query()
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        return [
            Stat::make("Qarzdorlik (UZS)", number_format($debts['uzs'] ?? 0, 0, '.', ' ') . " so'm"),
            Stat::make("Qarzdorlik (USD)", number_format($debts['usd'] ?? 0, 2) . " $"),
        ];
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
}
