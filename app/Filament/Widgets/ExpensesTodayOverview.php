<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Illuminate\Support\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Livewire\Attributes\On;

class ExpensesTodayOverview extends BaseWidget
{
    use HasWidgetShield;
    public ?string $start_date = null;
    public ?string $end_date   = null;

    #[On('refreshStats')]
    public function updateFilters($start_date, $end_date)
    {
        $this->start_date = $start_date;
        $this->end_date   = $end_date;
    }

    /**
     * Disable the base widget stat caching so the card refreshes instantly when the date range changes.
     *
     * @return array<Stat>
     */
    protected function getCachedStats(): array
    {
        return $this->getStats();
    }

    protected function getCards(): array
    {
        $query = Expense::query();

        $start = Carbon::parse($this->start_date ?? now())->startOfDay();
        $end   = Carbon::parse($this->end_date ?? now())->endOfDay();

        if ($start && $end) {
            $query->whereBetween('date', [
                $start,
                $end,
            ]);
        } elseif ($start) {
            $query->where('date', '>=', $start);
        } elseif ($end) {
            $query->where('date', '<=', $end);
        } else {
            $query->whereDate('date', today());
        }

        $total = (float) $query->sum('amount');

        $formatted = number_format($total) . " so'm";
        $label     = 'Tanlangan davr xarajatlari';

        return [
            Stat::make($label, $formatted)
                ->color('danger'),
        ];
    }
}
