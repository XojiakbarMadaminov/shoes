<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use App\Enums\NavigationGroup;
use App\Models\ExchangeOperation;
use App\Models\InventoryAdjustment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ExchangeHistoryPage extends Page implements HasTable
{
    use /* HasPageShield, */
        InteractsWithTable;

    protected static string|null|\UnitEnum $navigationGroup  = NavigationGroup::Finance;
    protected static ?string $navigationLabel                = 'Almashinuv tarixi';
    protected static ?string $title                          = 'Almashinuv tarixi';
    protected static ?string $slug                           = 'exchange-history';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::ArrowsRightLeft;
    protected static ?int $navigationSort                    = 3;

    protected string $view = 'filament.pages.exchange-history-page';

    public string $datePreset   = 'today';
    public ?string $customStart = null;
    public ?string $customEnd   = null;
    public string $historyType  = 'exchange';

    public function mount(): void
    {
        $this->datePreset = 'today';
    }

    public function table(Table $table): Table
    {
        $isReturn = $this->historyType === 'return';

        return $table
            ->searchable(false)
            ->defaultSort('created_at', 'desc')
            ->query(function (): Builder {
                if ($this->historyType === 'return') {
                    return InventoryAdjustment::query()
                        ->with(['product', 'productSize', 'handledBy'])
                        ->where('adjustment_type', InventoryAdjustment::TYPE_RETURN);
                }

                return ExchangeOperation::query()
                    ->with(['inProduct', 'inProductSize', 'outProduct', 'outProductSize', 'handledBy']);
            })
            ->modifyQueryUsing(function (Builder $query): void {
                $this->applyDateFilter($query, 'created_at');
            })
            ->columns($isReturn ? $this->getReturnColumns() : $this->getExchangeColumns())
            ->filters($isReturn ? [] : $this->getExchangeFilters())
            ->headerActions([
                Action::make('show_exchange')
                    ->label('Almashinuvlar')
                    ->color(fn (): string => $this->historyType === 'exchange' ? 'primary' : 'gray')
                    ->action(function (): void {
                        $this->historyType = 'exchange';
                        $this->resetTable();
                    }),
                Action::make('show_returns')
                    ->label('Qaytarishlar')
                    ->color(fn (): string => $this->historyType === 'return' ? 'primary' : 'gray')
                    ->action(function (): void {
                        $this->historyType = 'return';
                        $this->resetTable();
                    }),
                Action::make('today')
                    ->label('Bugun')
                    ->color(fn (): string => $this->datePreset === 'today' ? 'primary' : 'gray')
                    ->action(function (): void {
                        $this->datePreset  = 'today';
                        $this->customStart = $this->customEnd = null;
                    }),
                Action::make('week')
                    ->label('Hafta')
                    ->color(fn (): string => $this->datePreset === 'week' ? 'primary' : 'gray')
                    ->action(function (): void {
                        $this->datePreset  = 'week';
                        $this->customStart = $this->customEnd = null;
                    }),
                Action::make('month')
                    ->label('Oy')
                    ->color(fn (): string => $this->datePreset === 'month' ? 'primary' : 'gray')
                    ->action(function (): void {
                        $this->datePreset  = 'month';
                        $this->customStart = $this->customEnd = null;
                    }),
                Action::make('custom')
                    ->label('Oraliq')
                    ->color(fn (): string => $this->datePreset === 'custom' ? 'primary' : 'gray')
                    ->schema([
                        DatePicker::make('start')
                            ->label('Boshlanish sana')
                            ->native(false)
                            ->required(),
                        DatePicker::make('end')
                            ->label('Tugash sana')
                            ->native(false)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $this->datePreset  = 'custom';
                        $this->customStart = $data['start'] ?? null;
                        $this->customEnd   = $data['end'] ?? null;
                    }),
            ])
            ->recordActions($isReturn ? $this->getReturnActions() : $this->getExchangeActions())
            ->emptyStateHeading($isReturn ? 'Qaytarishlar topilmadi' : 'Almashinuvlar topilmadi')
            ->emptyStateDescription('Filtrlarni o‘zgartirib ko‘ring.');
    }

    protected function applyDateFilter(Builder $query, string $column): Builder
    {
        $preset = $this->datePreset;

        if ($preset === 'today') {
            $query->whereDate($column, today());
        } elseif ($preset === 'week') {
            $query->whereBetween($column, [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($preset === 'month') {
            $query->whereBetween($column, [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($preset === 'custom') {
            $start = $this->customStart ? Carbon::parse($this->customStart)->startOfDay() : null;
            $end   = $this->customEnd ? Carbon::parse($this->customEnd)->endOfDay() : null;

            if ($start && $end) {
                $query->whereBetween($column, [$start, $end]);
            } elseif ($start) {
                $query->where($column, '>=', $start);
            } elseif ($end) {
                $query->where($column, '<=', $end);
            }
        }

        return $query;
    }

    protected function getExchangeColumns(): array
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->toggleable(),

            TextColumn::make('inProduct.display_label')
                ->label('Qaytarilgan tovar')
                ->placeholder('-')
                ->wrap()
                ->searchable(isIndividual: true)
                ->formatStateUsing(fn (?string $state, ExchangeOperation $record) => $this->formatProductLabel($state, $record->inProductSize?->size))
                ->toggleable(),

            TextColumn::make('outProduct.display_label')
                ->label('Berilgan tovar')
                ->placeholder('-')
                ->wrap()
                ->searchable(isIndividual: true)
                ->formatStateUsing(fn (?string $state, ExchangeOperation $record) => $this->formatProductLabel($state, $record->outProductSize?->size))
                ->toggleable(),

            TextColumn::make('price_difference')
                ->label('Narx farqi')
                ->numeric(decimalPlaces: 0, thousandsSeparator: ' ')
                ->sortable()
                ->color(fn (?int $state): ?string => match (true) {
                    $state === null => null,
                    $state > 0      => 'success',
                    $state < 0      => 'danger',
                    default         => 'gray',
                }),

            TextColumn::make('difference_state')
                ->label('Holat')
                ->badge()
                ->getStateUsing(fn (ExchangeOperation $record): string => match (true) {
                    $record->price_difference > 0 => 'Mijoz to‘laydi',
                    $record->price_difference < 0 => 'Mijozga qaytarildi',
                    default                       => 'Narx farqi yo‘q',
                })
                ->color(fn (ExchangeOperation $record): string => match (true) {
                    $record->price_difference > 0 => 'success',
                    $record->price_difference < 0 => 'danger',
                    default                       => 'gray',
                }),

            TextColumn::make('handledBy.name')
                ->label('Kassir')
                ->badge()
                ->color('primary')
                ->placeholder('-')
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('Sana')
                ->dateTime('Y-m-d H:i')
                ->sortable(),
        ];
    }

    protected function getReturnColumns(): array
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->toggleable(),

            TextColumn::make('product.display_label')
                ->label('Mahsulot')
                ->placeholder('-')
                ->wrap()
                ->searchable(isIndividual: true)
                ->toggleable(),

            TextColumn::make('quantity')
                ->label('Miqdor')
                ->numeric()
                ->sortable(),

            TextColumn::make('productSize.size')
                ->label('Razmer')
                ->placeholder('-')
                ->toggleable(),

            TextColumn::make('unit_price')
                ->label('Birlik narxi')
                ->numeric()
                ->sortable(),

            TextColumn::make('total_value')
                ->label('Jami')
                ->state(fn (InventoryAdjustment $record): float => (float) ($record->unit_price ?? 0) * (float) ($record->quantity ?? 0))
                ->numeric()
                ->sortable(),

            TextColumn::make('reason')
                ->label('Izoh')
                ->placeholder('-')
                ->wrap()
                ->toggleable(),

            TextColumn::make('handledBy.name')
                ->label('Qabul qilgan kassir')
                ->badge()
                ->color('primary')
                ->placeholder('-')
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('Sana')
                ->dateTime('Y-m-d H:i')
                ->sortable(),
        ];
    }

    protected function getExchangeActions(): array
    {
        return [
            Action::make('view_details')
                ->label('Ko‘rish')
                ->icon('heroicon-o-eye')
                ->modalHeading('Almashinuv tafsilotlari')
                ->modalWidth('3xl')
                ->modalSubmitAction(false)
                ->modalContent(function (ExchangeOperation $record) {
                    $record->loadMissing(['inProduct', 'outProduct']);

                    return view('filament.exchanges.partials.exchange-details', [
                        'operation' => $record,
                    ]);
                }),
        ];
    }

    protected function getReturnActions(): array
    {
        return [
            Action::make('view_return_details')
                ->label('Ko‘rish')
                ->icon('heroicon-o-eye')
                ->modalHeading('Qaytarish tafsilotlari')
                ->modalWidth('lg')
                ->modalSubmitAction(false)
                ->modalContent(function (InventoryAdjustment $record) {
                    $record->loadMissing(['product', 'handledBy']);

                    return view('filament.exchanges.partials.return-details', [
                        'adjustment' => $record,
                    ]);
                }),
        ];
    }

    protected function getExchangeFilters(): array
    {
        return [
            SelectFilter::make('difference_direction')
                ->label('Narx farqi')
                ->options([
                    'positive' => 'Mijoz to‘laydi',
                    'negative' => 'Mijozga qaytariladi',
                    'zero'     => 'Farqsiz',
                ])
                ->placeholder('Hammasi')
                ->query(function (Builder $query, array $data): Builder {
                    return match ($data['value'] ?? null) {
                        'positive' => $query->where('price_difference', '>', 0),
                        'negative' => $query->where('price_difference', '<', 0),
                        'zero'     => $query->where('price_difference', 0),
                        default    => $query,
                    };
                }),
        ];
    }

    protected function formatProductLabel(?string $label, ?string $size): string
    {
        $label ??= '-';

        if ($size) {
            return trim($label . ' (' . $size . ')');
        }

        return $label;
    }
}
