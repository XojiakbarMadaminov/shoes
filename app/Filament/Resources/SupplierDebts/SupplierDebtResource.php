<?php

namespace App\Filament\Resources\SupplierDebts;

use Filament\Tables\Table;
use App\Models\SupplierDebt;
use Filament\Resources\Resource;
use App\Models\SupplierDebtTransaction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\SupplierDebts\Pages\ViewSupplierDebt;
use App\Filament\Resources\SupplierDebts\Pages\ListSupplierDebts;
use App\Filament\Resources\SupplierDebts\RelationManagers\SupplierTransactionsRelationManager;

class SupplierDebtResource extends Resource
{
    protected static ?string $model = SupplierDebt::class;

    protected static ?string $navigationLabel                = 'Ta’minotchilar qarzlari';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $label                          = 'Ta’minotchilar qarzi';
    protected static ?string $pluralLabel                    = 'Ta’minotchilar qarzlari';
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
            ->recordUrl(fn (SupplierDebt $record) => static::getUrl('view', ['record' => $record]));
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
