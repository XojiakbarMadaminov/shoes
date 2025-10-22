<?php

namespace App\Filament\Resources\Expenses;

use BackedEnum;
use App\Models\Expense;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use App\Enums\NavigationGroup;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\Expenses\Pages\EditExpense;
use App\Filament\Resources\Expenses\Pages\ViewExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Schemas\ExpenseForm;
use App\Filament\Resources\Expenses\Tables\ExpensesTable;
use App\Filament\Resources\Expenses\Schemas\ExpenseInfolist;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::Finance;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;
    protected static ?int $navigationSort                   = 3;
    protected static ?string $label                         = 'Chiqimlar';
    protected static ?string $navigationLabel               = 'Chiqimlar';

    public static function form(Schema $schema): Schema
    {
        return ExpenseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExpenseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpensesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'view'   => ViewExpense::route('/{record}'),
            'edit'   => EditExpense::route('/{record}/edit'),
        ];
    }
}
