<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Enums\NavigationGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Form;
use Filament\Forms\Components\DatePicker;
use App\Filament\Widgets\SalesByClientChart;
use App\Filament\Widgets\SalesByCashierChart;
use App\Filament\Widgets\MonthlySalesTrendChart;
use App\Filament\Widgets\SupplierPurchasesChart;

class SaleReport extends Page
{
    protected string $view = 'filament.pages.sale-report';

    protected static string|null|\UnitEnum $navigationGroup  = NavigationGroup::Statistics;
    protected static ?string $navigationLabel                = 'Savdo tahlili';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::ChartPie;
    protected static ?int $navigationSort                    = 2;

    public ?string $start_date = null;
    public ?string $end_date   = null;

    public function getFooterWidgets(): array
    {
        return [
            SalesByCashierChart::class,
            SalesByClientChart::class,
            MonthlySalesTrendChart::class,
            SupplierPurchasesChart::class,
        ];
    }

    public function mount()
    {
        $this->start_date = now()->startOfDay()->format('Y-m-d');
        $this->end_date   = now()->endOfDay()->format('Y-m-d');
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
                ->reactive()
                ->afterStateUpdated(fn ($state) => $this->updateStats()),

            DatePicker::make('end_date')
                ->label('Tugash sanasi')
                ->reactive()
                ->afterStateUpdated(fn ($state) => $this->updateStats()),
        ]);
    }
}
