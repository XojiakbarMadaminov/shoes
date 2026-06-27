<?php

namespace App\Filament\Resources\Discounts;

use BackedEnum;
use App\Models\Discount;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use App\Enums\NavigationGroup;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\Discounts\Pages\EditDiscount;
use App\Filament\Resources\Discounts\Pages\ViewDiscount;
use App\Filament\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Resources\Discounts\Schemas\DiscountForm;
use App\Filament\Resources\Discounts\Tables\DiscountsTable;
use App\Filament\Resources\Discounts\Schemas\DiscountInfolist;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::ProductsAndCategories;
    protected static ?string $navigationLabel               = 'Chegirmalar';
    protected static ?string $label                         = 'Chegirma';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;
    protected static ?int $navigationSort                   = 10;

    public static function form(Schema $schema): Schema
    {
        return DiscountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
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
            'index' => ListDiscounts::route('/'),
            'view'  => ViewDiscount::route('/{record}'),
            'edit'  => EditDiscount::route('/{record}/edit'),
        ];
    }
}
