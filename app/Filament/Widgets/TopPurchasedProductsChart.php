<?php

namespace App\Filament\Widgets;

use Livewire\Attributes\On;
use App\Models\PurchaseItem;
use Illuminate\Support\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Forms\Concerns\InteractsWithForms;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class TopPurchasedProductsChart extends ChartWidget
{
    use HasWidgetShield, InteractsWithForms;

    protected static bool $isLazy      = true;
    protected ?string $pollingInterval = '5m';

    public ?string $start_date = null;
    public ?string $end_date   = null;

    protected ?string $heading = 'Top 10 ta\'minotchilardan olingan tovarlar';

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    #[On('refreshStats')]
    public function updateFilters($start_date, $end_date): void
    {
        $this->start_date = $start_date;
        $this->end_date   = $end_date;
    }

    protected function getData(): array
    {
        $start = Carbon::parse($this->start_date ?? now())->startOfDay();
        $end   = Carbon::parse($this->end_date ?? now())->endOfDay();

        $topProducts = PurchaseItem::query()
            ->with(['product' => fn ($query) => $query->withTrashed()])
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('product_id, SUM(quantity) as total_qty')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Sotib olingan soni',
                    'data'            => $topProducts->pluck('total_qty'),
                    'backgroundColor' => '#10b981',
                ],
            ],
            'labels' => $topProducts
                ->map(fn ($item) => $item->product->name ?? 'Nomaʼlum')
                ->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
