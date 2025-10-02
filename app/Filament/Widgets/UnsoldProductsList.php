<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UnsoldProductsList extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Umuman sotilmagan tovarlar';

    protected function getTableQuery(): Builder
    {
        return Product::query()
            ->whereDoesntHave('saleItems');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')->label('ID'),
            TextColumn::make('name')->label('Mahsulot nomi')->searchable(),
            TextColumn::make('category.name')->label('Kategoriyasi')->searchable(),
            TextColumn::make('barcode')->label('Barkod')->toggleable()->searchable(),
            TextColumn::make('yuan_price')->label('Yuan narxi'),
            TextColumn::make('initial_price')->label('Kelgan narxi'),
            TextColumn::make('price')->label('Narxi'),
            TextColumn::make('created_at')->label('Yaratilgan sana')->sortable(),
        ];
    }
}
