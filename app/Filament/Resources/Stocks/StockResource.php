<?php

namespace App\Filament\Resources\Stocks;

use App\Filament\Resources\Stocks\Pages\CreateStock;
use App\Filament\Resources\Stocks\Pages\EditStock;
use App\Filament\Resources\Stocks\Pages\ListStocks;
use App\Filament\Resources\Stocks\Pages\ViewStock;
use App\Filament\Resources\Stocks\Schemas\StockForm;
use App\Filament\Resources\Stocks\Schemas\StockInfolist;
use App\Filament\Resources\Stocks\Tables\StocksTable;
use App\Models\Stock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Stock';

    public static function form(Schema $schema): Schema
    {
        return StockForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StocksTable::configure($table);
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
            'index' => ListStocks::route('/'),
            'create' => CreateStock::route('/create'),
            'view' => ViewStock::route('/{record}'),
            'edit' => EditStock::route('/{record}/edit'),
        ];
    }
}
