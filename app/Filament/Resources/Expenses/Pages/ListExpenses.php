<?php

namespace App\Filament\Resources\Expenses\Pages;

use Filament\Actions\Action;
use Illuminate\Support\Carbon;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Widgets\ExpensesTodayOverview;
use App\Filament\Resources\Expenses\ExpenseResource;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;
    public string $datePreset         = 'today';
    public ?string $customStart       = null;
    public ?string $customEnd         = null;

    public function mount(): void
    {
        parent::mount();
        $this->datePreset = 'today';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('today')
                ->label('Bugun')
                ->color(fn (): string => $this->datePreset === 'today' ? 'warning' : 'gray')
                ->action(function () {
                    $this->datePreset  = 'today';
                    $this->customStart = $this->customEnd = null;
                    session()->put('expenses_range', [
                        'start' => today()->toDateString(),
                        'end'   => today()->toDateString(),
                    ]);
                }),
            Action::make('week')
                ->label('Hafta')
                ->color(fn (): string => $this->datePreset === 'week' ? 'warning' : 'gray')
                ->action(function () {
                    $this->datePreset  = 'week';
                    $this->customStart = $this->customEnd = null;
                    session()->put('expenses_range', [
                        'start' => now()->startOfWeek()->toDateString(),
                        'end'   => now()->endOfWeek()->toDateString(),
                    ]);
                }),
            Action::make('month')
                ->label('Oy')
                ->color(fn (): string => $this->datePreset === 'month' ? 'warning' : 'gray')
                ->action(function () {
                    $this->datePreset  = 'month';
                    $this->customStart = $this->customEnd = null;
                    session()->put('expenses_range', [
                        'start' => now()->startOfMonth()->toDateString(),
                        'end'   => now()->endOfMonth()->toDateString(),
                    ]);
                }),
            Action::make('custom')
                ->label('Oraliq')
                ->color(fn (): string => $this->datePreset === 'custom' ? 'warning' : 'gray')
                ->form([
                    DatePicker::make('start')
                        ->label('Boshlanish')
                        ->native(false)
                        ->required(),
                    DatePicker::make('end')
                        ->label('Tugash')
                        ->native(false)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->datePreset  = 'custom';
                    $this->customStart = $data['start'] ?? null;
                    $this->customEnd   = $data['end'] ?? null;
                    session()->put('expenses_range', [
                        'start' => $this->customStart,
                        'end'   => $this->customEnd,
                    ]);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExpensesTodayOverview::class,
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $preset = $this->datePreset;
        if ($preset === 'today') {
            return $query->whereDate('date', today());
        }
        if ($preset === 'week') {
            return $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
        }
        if ($preset === 'month') {
            return $query->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]);
        }
        if ($preset === 'custom') {
            $start = $this->customStart ? Carbon::parse($this->customStart)->startOfDay() : null;
            $end   = $this->customEnd ? Carbon::parse($this->customEnd)->endOfDay() : null;
            if ($start && $end) {
                return $query->whereBetween('date', [$start, $end]);
            }
            if ($start) {
                return $query->where('date', '>=', $start);
            }
            if ($end) {
                return $query->where('date', '<=', $end);
            }
        }

        return $query;
    }
}
