<?php

namespace App\Filament\Widgets;

use App\Models\SaleItem;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class LeastSellingProductsChart extends ChartWidget
{
    protected ?string $heading = 'Eng kam sotilgan 10 ta tovar';

    public ?string $start_date = null;
    public ?string $end_date = null;

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    protected function getData(): array
    {
        $start = Carbon::parse($this->start_date ?? now())->startOfDay();
        $end = Carbon::parse($this->end_date ?? now())->endOfDay();

        $leastProducts = SaleItem::with(['product' => fn ($query) => $query->withTrashed()])
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('product_id, SUM(qty) as total_qty')
            ->groupBy('product_id')
            ->orderBy('total_qty')
            ->take(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Sotilgan soni',
                    'data' => $leastProducts->pluck('total_qty'),
                ],
            ],
            'labels' => $leastProducts->map(fn ($item) => $item->product->name ?? 'Nomaʼlum')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

