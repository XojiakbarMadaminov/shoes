<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class SalesStatsOverview extends BaseWidget
{
    use InteractsWithForms;

    public ?string $start_date = null;
    public ?string $end_date = null;

    #[On('refreshStats')]
    public function updateFilters($start_date, $end_date)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    protected function getCards(): array
    {
        $start = Carbon::parse($this->start_date ?? now())->startOfDay();
        $end = Carbon::parse($this->end_date ?? now())->endOfDay();

        $sales = Sale::whereBetween('created_at', [$start, $end])->get();
        $totalSales = $sales->sum('total');

        $totalProfit = SaleItem::whereIn('sale_items.sale_id', $sales->pluck('id'))
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->selectRaw('COALESCE(SUM( (sale_items.price - products.initial_price) * sale_items.qty ), 0) AS profit')
            ->value('profit');


        return [
            Stat::make('Umumiy Sotuvlar', number_format($totalSales, 2) . " so'm"),
            Stat::make('Foyda', number_format($totalProfit, 2) . " so'm"),
        ];
    }
}
