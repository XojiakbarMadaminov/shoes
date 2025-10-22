<?php

namespace App\Filament\Resources\SupplierDebts;

use Filament\Tables\Table;
use App\Models\SupplierDebt;
use Filament\Actions\Action;
use App\Enums\NavigationGroup;
use Filament\Resources\Resource;
use App\Models\SupplierDebtTransaction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\SupplierDebts\Pages\ViewSupplierDebt;
use App\Filament\Resources\SupplierDebts\Pages\ListSupplierDebts;
use App\Filament\Resources\SupplierDebts\RelationManagers\SupplierTransactionsRelationManager;

class SupplierDebtResource extends Resource
{
    protected static ?string $model = SupplierDebt::class;

    protected static string|null|\UnitEnum $navigationGroup  = NavigationGroup::SupplierActions;
    protected static ?string $navigationLabel                = 'Ta’minotchilardan qarz';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $label                          = 'Ta’minotchilardan qarz';
    protected static ?string $pluralLabel                    = 'Ta’minotchilardan qarz';
    protected static ?int $navigationSort                    = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $latestDateSubquery = SupplierDebtTransaction::select('date')
                    ->whereColumn('supplier_debt_transactions.supplier_debt_id', 'supplier_debts.id')
                    ->latest('date')
                    ->limit(1);

                $query
                    ->with('latestTransaction')
                    ->orderBy($latestDateSubquery, 'desc');
            })
            ->columns([
                TextColumn::make('supplier.full_name')
                    ->label('Ta’minotchi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier.phone')
                    ->label('Telefon')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Qarz summasi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('latestTransaction.date')
                    ->label('Oxirgi operatsiya')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordUrl(fn (SupplierDebt $record) => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('add_payment')
                    ->label('To‘lov qilish')
                    ->color('success')
                    ->schema(fn (SupplierDebt $record) => [
                        TextInput::make('amount')
                            ->label('To‘lov summasi')
                            ->prefix($record->currency)
                            ->numeric()
                            ->required()
                            ->rule('lte:' . $record->amount) // `amount` dan katta bo‘lmasin
                            ->helperText('Maksimum: ' . $record->amount . ' ' . $record->currency),

                        DateTimePicker::make('date')
                            ->label('To‘lov sanasi')
                            ->default(now())
                            ->required(),

                        Textarea::make('note')
                            ->label('Izoh')
                            ->nullable(),
                    ])
                    ->action(function (array $data, SupplierDebt $record) {
                        if ($data['amount'] > $record->amount) {
                            Notification::make()
                                ->title('To‘lov summasi mavjud qarzdan katta bo‘lishi mumkin emas')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->transactions()->create([
                            'type'   => 'payment',
                            'amount' => $data['amount'],
                            'date'   => $data['date'],
                            'note'   => $data['note'] ?? null,
                        ]);

                        $record->decrement('amount', $data['amount']);
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SupplierTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplierDebts::route('/'),
            'view'  => ViewSupplierDebt::route('/{record}'),
        ];
    }
}
