<?php

namespace App\Filament\Resources\Debtors;

use App\Filament\Resources\Debtors\Pages\CreateDebtor;
use App\Filament\Resources\Debtors\Pages\EditDebtor;
use App\Filament\Resources\Debtors\Pages\ListDebtors;
use App\Filament\Resources\Debtors\Pages\ViewDebtor;
use App\Filament\Resources\Debtors\RelationManagers\TransactionRelationManager;
use App\Filament\Resources\Debtors\Schemas\DebtorForm;
use App\Filament\Resources\Debtors\Schemas\DebtorInfolist;
use App\Filament\Resources\Debtors\Tables\DebtorsTable;
use App\Models\Debtor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DebtorResource extends Resource
{
    protected static ?string $model = Debtor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static ?string $label = 'Qarzdorlar';

    public static function form(Schema $schema): Schema
    {
        return DebtorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DebtorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DebtorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TransactionRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDebtors::route('/'),
            'create' => CreateDebtor::route('/create'),
            'view' => ViewDebtor::route('/{record}'),
            'edit' => EditDebtor::route('/{record}/edit'),
        ];
    }
}
