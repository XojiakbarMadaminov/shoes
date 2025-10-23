<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\SaleItem;
use Livewire\Attributes\On;
use Illuminate\Support\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Forms\Concerns\InteractsWithForms;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class SalesStatsOverview extends BaseWidget
{
    use HasWidgetShield, InteractsWithForms;

    protected ?string $pollingInterval = '30s';

    public ?string $start_date = null;
    public ?string $end_date   = null;

    #[On('refreshStats')]
    public function updateFilters($start_date, $end_date)
    {
        $this->start_date = $start_date;
        $this->end_date   = $end_date;
    }

    protected function getCards(): array
    {
        $start = Carbon::parse($this->start_date ?? now())->startOfDay();
        $end   = Carbon::parse($this->end_date ?? now())->endOfDay();

        $sales = Sale::whereBetween('created_at', [$start, $end])
            ->where('status', Sale::STATUS_COMPLETED)
            ->get();
        $totalSales = $sales->sum('total_amount');

        $totalExpenses = Expense::query()
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $debtSales = $sales
            ->where('payment_type', 'debt')
            ->sum('total_amount');

        $totalProfit = SaleItem::whereIn('sale_items.sale_id', $sales->pluck('id'))
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->selectRaw('COALESCE(SUM( (sale_items.price - products.initial_price) * sale_items.quantity ), 0) AS profit')
            ->value('profit');

        return [
            Stat::make('Umumiy sotuvlar', number_format($totalSales) . " so'm")
                ->description('Tanlangan davr uchun umumiy sotuvlar')
                ->icon('heroicon-o-wallet')
                ->color('success'),
            Stat::make('Foyda', number_format($totalProfit) . " so'm")
                ->description('Tanlangan davr uchun foyda')
                ->icon('heroicon-o-wallet')
                ->color('primary'),
            Stat::make('Xarajatlar', number_format($totalExpenses) . " so'm")
                ->description('Tanlangan davr uchun xarajatlar')
                ->icon('heroicon-o-wallet')
                ->color('danger'),
            Stat::make('Qarzga sotuvlar', number_format($debtSales) . " so'm")
                ->icon('heroicon-o-wallet')
                ->description('Tanlangan davr uchun qarzga sotuvlar')
                ->color('warning'),
        ];
    }
}
