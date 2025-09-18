<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DebtorStatsOverview;
use App\Filament\Widgets\LeastSellingProductsChart;
use App\Filament\Widgets\SalesStatsOverview;
use App\Filament\Widgets\TopSellingProductsChart;
use App\Filament\Widgets\UnsoldProductsList;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Form;

class Dashboard extends BaseDashboard
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-home';
    protected string $view = 'filament.pages.dashboard';
    public ?string $start_date = null;
    public ?string $end_date = null;

    public function getFooterWidgets(): array
    {
        return [
            SalesStatsOverview::class,
            TopSellingProductsChart::class,
            LeastSellingProductsChart::class,
            UnsoldProductsList::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function mount()
    {
        $this->start_date = now()->subDay()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    public function updateStats()
    {
        $this->dispatch('refreshStats', start_date: $this->start_date, end_date: $this->end_date);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('start_date')
                ->label('Boshlanish sanasi')
                ->default(now()->subDay()->format('Y-m-d'))
                ->reactive()
                ->afterStateUpdated(fn($state) => $this->updateStats()),

            DatePicker::make('end_date')
                ->label('Tugash sanasi')
                ->default(now()->format('Y-m-d'))
                ->reactive()
                ->afterStateUpdated(fn($state) => $this->updateStats()),
        ]);
    }
}
