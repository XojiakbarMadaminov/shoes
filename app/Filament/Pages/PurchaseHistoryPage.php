<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Purchase;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use App\Enums\NavigationGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class PurchaseHistoryPage extends Page implements HasTable
{
    use HasPageShield, InteractsWithTable;

    protected static string|null|\UnitEnum $navigationGroup  = NavigationGroup::SupplierActions;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $title                          = 'Ta\'minotchidan xaridlar tarixi';
    protected static ?string $navigationLabel                = 'Ta\'minotchidan xaridlar tarixi';
    protected static ?string $slug                           = 'purchases/history';
    protected static ?int $navigationSort                    = 3;
    protected string $view                                   = 'filament.pages.purchase-history-page';

    public string $datePreset   = 'today';
    public ?string $customStart = null;
    public ?string $customEnd   = null;

    public function mount(): void
    {
        $this->datePreset = 'today';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Purchase::query()
                    ->with(['supplier', 'items.product', 'items.productSize', 'createdBy', 'stock'])
            )
            ->modifyQueryUsing(function (Builder $query) {
                $preset = $this->datePreset;

                if ($preset === 'today') {
                    $query->whereDate('purchase_date', today());
                } elseif ($preset === 'week') {
                    $query->whereBetween('purchase_date', [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($preset === 'month') {
                    $query->whereBetween('purchase_date', [now()->startOfMonth(), now()->endOfMonth()]);
                } elseif ($preset === 'custom') {
                    $start = $this->customStart ? Carbon::parse($this->customStart)->startOfDay() : null;
                    $end   = $this->customEnd ? Carbon::parse($this->customEnd)->endOfDay() : null;

                    if ($start && $end) {
                        $query->whereBetween('purchase_date', [$start, $end]);
                    } elseif ($start) {
                        $query->where('purchase_date', '>=', $start);
                    } elseif ($end) {
                        $query->where('purchase_date', '<=', $end);
                    }
                }
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('supplier.full_name')
                    ->label('Ta’minotchi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.phone')
                    ->label('Telefon')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('stock.name')
                    ->label('Sklad')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('payment_type')
                    ->label('To‘lov turi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cash'    => 'Naqd',
                        'card'    => 'Karta',
                        'debt'    => 'Qarz',
                        'partial' => 'Qisman',
                        default   => ucfirst($state ?? '-'),
                    })
                    ->color(fn ($state) => match ($state) {
                        'debt'    => 'danger',
                        'partial' => 'warning',
                        default   => 'success',
                    }),

                TextColumn::make('total_amount')
                    ->label('Jami')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label('To‘langan')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label('Qolgan')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('Kassir')
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                TextColumn::make('purchase_date')
                    ->label('Sana')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('stock_id')
                    ->label('Sklad')
                    ->options(fn () => Stock::scopes('active')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        $stockId = $data['value'] ?? null;

                        if ($stockId) {
                            $query->where('stock_id', $stockId);
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
                    ->form([
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
                    ->modalHeading('Xarid tafsilotlari')
                    ->modalSubmitAction(false)
                    ->modalWidth('4xl')
                    ->modalContent(function (Purchase $record) {
                        $record->loadMissing(['supplier', 'items.product', 'items.productSize', 'stock', 'createdBy']);

                        return view('filament.purchases.partials.purchase-details', [
                            'purchase' => $record,
                        ]);
                    }),
            ])
            ->emptyStateHeading('Xaridlar topilmadi')
            ->emptyStateDescription('Filtrlarni o‘zgartirib ko‘ring.');
    }
}
