<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Carbon\CarbonPeriod;
use Livewire\Attributes\On;
use Illuminate\Support\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Concerns\InteractsWithForms;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class MonthlySalesTrendChart extends ChartWidget
{
    use HasWidgetShield, InteractsWithForms;

    public ?string $start_date = null;
    public ?string $end_date   = null;

    protected ?string $heading = 'Oylik savdo hajmi (oxirgi 12 oy)';

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
        $end   = Carbon::parse($this->end_date ?? now())->endOfMonth();
        $start = $end->copy()->subMonthsNoOverflow(11)->startOfMonth();

        if ($this->start_date) {
            $requestedStart = Carbon::parse($this->start_date)->startOfMonth();

            if ($requestedStart->greaterThan($start)) {
                $start = $requestedStart;
            }
        }

        $driver     = DB::getDriverName();
        $formatExpr = $driver === 'pgsql'
            ? "TO_CHAR(created_at, 'YYYY-MM')"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $monthlyTotals = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("$formatExpr as period, SUM(total_amount) as total_amount")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total_amount', 'period');

        $labels = [];
        $data   = [];

        $monthsUz = [
            1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart', 4 => 'Aprel',
            5 => 'May', 6 => 'Iyun', 7 => 'Iyul', 8 => 'Avgust',
            9 => 'Sentyabr', 10 => 'Oktyabr', 11 => 'Noyabr', 12 => 'Dekabr',
        ];

        foreach (CarbonPeriod::create($start, '1 month', $end) as $month) {
            $key      = $month->format('Y-m');
            $labels[] = $monthsUz[$month->month] . ' ' . $month->year;
            $data[]   = (float) ($monthlyTotals[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Sotuv hajmi',
                    'data'            => $data,
                    'fill'            => false,
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => '#3b82f6',
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
