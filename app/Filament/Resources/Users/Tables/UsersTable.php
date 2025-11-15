<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ismi')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('stores.name')
                    ->label('Biriktirilgan magazinlar')
                    ->badge()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')->label('Qo‘shilgan sana')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                //                BulkActionGroup::make([
                //                    DeleteBulkAction::make(),
                //                ]),
            ]);
    }
}
