<?php

namespace App\Filament\Resources\Discounts\Schemas;

use App\Enums\DiscountType;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;

class DiscountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Chegirma')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')->label('Nomi'),
                        TextEntry::make('type')
                            ->label('Turi')
                            ->formatStateUsing(fn (mixed $state): ?string => self::typeLabel($state)),
                        TextEntry::make('percent')
                            ->label('Foiz')
                            ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2, '.', ' ') . '%'),
                        IconEntry::make('is_active')->label('Aktiv')->boolean(),
                        TextEntry::make('min_order_amount')
                            ->label('Minimal summa')
                            ->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((float) $state, 0, '.', ' ') : '-'),
                        TextEntry::make('products.name')
                            ->label('Tovarlar')
                            ->listWithLineBreaks()
                            ->bulleted(),
                        TextEntry::make('categories.name')
                            ->label('Kategoriyalar')
                            ->listWithLineBreaks()
                            ->bulleted(),
                        TextEntry::make('starts_at')->label('Boshlanish vaqti')->dateTime('d.m.Y H:i'),
                        TextEntry::make('ends_at')->label('Tugash vaqti')->dateTime('d.m.Y H:i'),
                    ])
                    ->columns(3),
            ]);
    }

    private static function typeLabel(mixed $state): ?string
    {
        if ($state instanceof DiscountType) {
            return $state->getLabel();
        }

        return DiscountType::tryFrom((string) $state)?->getLabel();
    }
}
