<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Stock;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
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

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Sale::query()
                    ->with(['client', 'items.product'])
            )
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
                // Date period + custom range
                Filter::make('date_period')
                    ->label('Sana')
                    ->schema([
                        Select::make('period')
                            ->options([
                                'today'  => 'Bugun',
                                'week'   => 'Hafta',
                                'month'  => 'Oy',
                                'custom' => 'Oraliq',
                            ])
                            ->native(false),
                        DatePicker::make('start_date')
                            ->label('Boshlanish sana')
                            ->native(false),
                        DatePicker::make('end_date')
                            ->label('Tugash sana')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $period = $data['period'] ?? null;
                        if ($period === 'today') {
                            return $query->whereDate('created_at', today());
                        }
                        if ($period === 'week') {
                            return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        }
                        if ($period === 'month') {
                            return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                        }
                        if ($period === 'custom') {
                            $start = $data['start_date'] ?? null;
                            $end   = $data['end_date'] ?? null;
                            if ($start && $end) {
                                return $query->whereBetween('created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]);
                            }
                            if ($start) {
                                return $query->where('created_at', '>=', Carbon::parse($start)->startOfDay());
                            }
                            if ($end) {
                                return $query->where('created_at', '<=', Carbon::parse($end)->endOfDay());
                            }
                        }

                        return $query;
                    }),

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
