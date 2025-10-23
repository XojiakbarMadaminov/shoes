<?php

namespace App\Filament\Widgets;

use App\Models\Purchase;
use App\Models\Supplier;
use Livewire\Attributes\On;
use Illuminate\Support\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Forms\Concerns\InteractsWithForms;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class SupplierPurchasesChart extends ChartWidget
{
    use HasWidgetShield, InteractsWithForms;

    protected static bool $isLazy      = true;
    protected ?string $pollingInterval = null;

    public ?string $start_date = null;
    public ?string $end_date   = null;

    protected ?string $heading = 'Ta’minotchilardan xaridlar';

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

        $totals = Purchase::query()
            ->whereBetween('purchase_date', [$start, $end])
            ->selectRaw('supplier_id, SUM(total_amount) as total_amount, COUNT(*) as purchase_count')
            ->groupBy('supplier_id')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get()
            ->keyBy('supplier_id');

        $supplierNames = Supplier::query()
            ->whereIn('id', $totals->keys()->filter())
            ->pluck('full_name', 'id');

        $labels  = [];
        $amounts = [];

        foreach ($totals->values() as $index => $supplierSummary) {
            $name     = $supplierNames[$supplierSummary->supplier_id] ?? 'Noma’lum ta’minotchi';
            $labels[] = sprintf(
                '%s — %s soʼm (%d ta)',
                $name,
                number_format((float) $supplierSummary->total_amount, 0, '.', ' '),
                (int) $supplierSummary->purchase_count
            );
            $amounts[] = (float) $supplierSummary->total_amount;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Jami summa',
                    'data'            => $amounts,
                    'backgroundColor' => '#10b981',
                    'borderColor'     => '#10b981',
                    'barThickness'    => 18,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales'    => [
                'x' => [
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
