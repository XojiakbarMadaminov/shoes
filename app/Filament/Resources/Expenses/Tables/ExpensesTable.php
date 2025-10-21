<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('createdBy.name')
                    ->label('Shaxs')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Miqdor')
                    ->color('danger')
                    ->numeric()
                    ->sortable(),
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
                    ),
                TextColumn::make('date')
                    ->label('Sana')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('created_by')
                    ->relationship('createdBy', 'name')
                    ->label('Shaxs'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
