<?php

namespace App\Filament\Widgets;

use App\Models\SaleItem;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Widgets\BarChartWidget;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class TopSellingProductsChart extends ChartWidget
{
    use InteractsWithForms;

    public ?string $start_date = null;
    public ?string $end_date = null;

    protected ?string $heading = 'Top 10 sotilgan tovarlar';

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    #[On('refreshStats')]
    public function updateFilters($start_date, $end_date)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    protected function getData(): array
    {
        $start = Carbon::parse($this->start_date ?? now())->startOfDay();
        $end = Carbon::parse($this->end_date ?? now())->endOfDay();

        $topProducts = SaleItem::with(['product' => fn ($query) => $query->withTrashed()])
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('product_id, SUM(qty) as total_qty')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Sotilgan soni',
                    'data' => $topProducts->pluck('total_qty'),
                ],
            ],
            'labels' => $topProducts->map(fn ($item) => $item->product->name ?? 'Nomaʼlum')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
