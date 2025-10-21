<?php

namespace App\Filament\Resources\Debtors\RelationManagers;

use Filament\Tables;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\RelationManagers\RelationManager;

class TransactionRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';
    protected static ?string $title       = "Qarz/To'lovlar tarixi";

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Turi')
                    ->badge()
                    ->color(fn ($record) => $record->type === 'debt' ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state === 'debt' ? 'Qarz' : "To'lov"),

                TextColumn::make('sale_id')
                    ->label('Sale ID')
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'text-blue-600 underline cursor-pointer'])
                    ->action(
                        Action::make('view_sale')
                            ->label("Ko'rish")
                            ->modalHeading('Sotuv tafsilotlari')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Yopish')
                            ->hidden(fn ($record) => blank($record->sale_id))
                            ->modalContent(fn ($record) => view('filament.sales.partials.sale-details', [
                                'sale' => optional($record->sale)?->load(['client', 'items.product', 'items.productSize', 'items.stock']),
                            ]))
                    ),

                TextColumn::make('amount')
                    ->label('Summasi')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Sana'),

                TextColumn::make('note')
                    ->label('Izoh')
                    ->limit(50)
                    ->wrap()
                    ->extraAttributes(['class' => 'text-blue-600 underline cursor-pointer'])
                    ->action(
                        Action::make('view_note')
                            ->label("Ko'rish")
                            ->modalHeading("To'liq izoh")
                            ->modalDescription(fn ($record) => $record->note ?? 'Izoh mavjud emas')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Yopish')
                    ),
            ])
            ->defaultSort('date', 'desc');
    }

}
