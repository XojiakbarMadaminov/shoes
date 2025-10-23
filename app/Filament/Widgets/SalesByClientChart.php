<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\Client;
use Livewire\Attributes\On;
use Illuminate\Support\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Forms\Concerns\InteractsWithForms;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class SalesByClientChart extends ChartWidget
{
    use HasWidgetShield, InteractsWithForms;

    public ?string $start_date = null;
    public ?string $end_date   = null;
    protected int $maxRecords  = 10;

    protected ?string $heading = 'Top mijozlar';

    public function getColumnSpan(): int|string|array
    {
        return 'small';
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

        $totals = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('client_id, SUM(total_amount) as total_amount, COUNT(*) as sales_count')
            ->groupBy('client_id')
            ->orderByDesc('total_amount')
            ->limit($this->maxRecords)
            ->get()
            ->keyBy('client_id');

        $clientNames = Client::query()
            ->whereIn('id', $totals->keys()->filter())
            ->pluck('full_name', 'id');

        $colors = [
            '#3b82f6',
            '#10b981',
            '#f59e0b',
            '#ef4444',
            '#8b5cf6',
            '#ec4899',
            '#14b8a6',
            '#f97316',
            '#64748b',
            '#22c55e',
        ];

        $labels           = [];
        $data             = [];
        $backgroundColors = [];

        foreach ($totals->values()->take($this->maxRecords) as $index => $row) {
            $clientName = $clientNames[$row->client_id] ?? 'Anonim klient';
            $labels[]   = sprintf(
                '%s — %s soʼm (%d ta)',
                $clientName,
                number_format((float) $row->total_amount, 0, '.', ' '),
                $row->sales_count
            );
            $data[]             = (float) $row->total_amount;
            $backgroundColors[] = $colors[$index % count($colors)];
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Sotuvlar',
                    'data'            => $data,
                    'backgroundColor' => $backgroundColors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
