<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Stock;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;

class SalesHistoryPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel                = 'Sotuv tarixi';
    protected static ?string $title                          = 'Sotuv tarixi';
    protected static ?string $slug                           = 'sales-history';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::ListBullet;
    protected static ?int $navigationSort                    = 5;

    protected string $view = 'filament.pages.sales-history-page';

    public string $datePreset   = 'today'; // today, week, month, custom
    public ?string $customStart = null;
    public ?string $customEnd   = null;

    public function mount(): void
    {
        // Ensure default is always 'today'
        $this->datePreset = 'today';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Sale::query()
                    ->with(['client', 'items.product'])
            )
            ->modifyQueryUsing(function (Builder $query) {
                $preset = $this->datePreset;
                if ($preset === 'today') {
                    $query->whereDate('created_at', today());
                } elseif ($preset === 'week') {
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($preset === 'month') {
                    $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                } elseif ($preset === 'custom') {
                    $start = $this->customStart ? Carbon::parse($this->customStart)->startOfDay() : null;
                    $end   = $this->customEnd ? Carbon::parse($this->customEnd)->endOfDay() : null;
                    if ($start && $end) {
                        $query->whereBetween('created_at', [$start, $end]);
                    } elseif ($start) {
                        $query->where('created_at', '>=', $start);
                    } elseif ($end) {
                        $query->where('created_at', '<=', $end);
                    }
                }
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('client.full_name')
                    ->label('Klient')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('client.phone')
                    ->label('Telefon')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('payment_type')
                    ->label('To‘lov turi')
                    ->color(fn ($state) => match ($state) {
                        'debt'    => 'danger',
                        'partial' => 'warning',
                        default   => 'success',
                    })
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cash'     => 'Naqd',
                        'card'     => 'Karta',
                        'debt'     => 'Qarz',
                        'transfer' => 'O‘tkazma',
                        'partial'  => 'Qisman',
                        'mixed'    => 'Karta + Naqd',
                        default    => $state ?? '-',
                    })
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Jami')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label('To‘langan')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label('Qolgan')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Sana')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                // Stock filter by sale items
                SelectFilter::make('stock_id')
                    ->label('Sklad')
                    ->options(fn () => Stock::scopes('active')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        $stockId = $data['value'] ?? null;
                        if ($stockId) {
                            $query->whereHas('items', fn ($q) => $q->where('stock_id', $stockId));
                        }

                        return $query;
                    }),
            ])
            ->headerActions([
                Action::make('today')
                    ->label('Bugun')
                    ->color(fn (): string => $this->datePreset === 'today' ? 'primary' : 'gray')
                    ->action(function () {
                        $this->datePreset  = 'today';
                        $this->customStart = $this->customEnd = null;
                    }),
                Action::make('week')
                    ->label('Hafta')
                    ->color(fn (): string => $this->datePreset === 'week' ? 'primary' : 'gray')
                    ->action(function () {
                        $this->datePreset  = 'week';
                        $this->customStart = $this->customEnd = null;
                    }),
                Action::make('month')
                    ->label('Oy')
                    ->color(fn (): string => $this->datePreset === 'month' ? 'primary' : 'gray')
                    ->action(function () {
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
                    ->action(function (array $data) {
                        $this->datePreset  = 'custom';
                        $this->customStart = $data['start'] ?? null;
                        $this->customEnd   = $data['end'] ?? null;
                    }),
            ])
            ->recordActions([
                Action::make('view_details')
                    ->label('Ko‘rish')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Sotuv tafsilotlari')
                    ->modalSubmitAction(false)
                    ->modalWidth('4xl')
                    ->modalContent(function (Sale $record) {
                        $record->loadMissing(['client', 'items.product']);

                        return view('filament.sales.partials.sale-details', [
                            'sale' => $record,
                        ]);
                    }),

                Action::make('print_receipt')
                    ->label('Chek')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Sale $record) => route('sale.receipt.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Sotuvlar topilmadi')
            ->emptyStateDescription('Filtrlarni o‘zgartirib ko‘ring.');
    }
}
