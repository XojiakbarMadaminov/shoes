<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\Client;
use Filament\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Collection;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('send_sms')->label('SMS yuborish')->boolean()->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle'),
                TextColumn::make('send_sms_interval')->label('SMS yuborish intervali'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('update_send_sms_interval')
                        ->label('SMS intervalini yangilash')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            TextInput::make('send_sms_interval')
                                ->label('Interval (kun)')
                                ->numeric()
                                ->required()
                                ->minValue(1),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            Client::query()
                                ->whereIn('id', $records->pluck('id'))
                                ->update([
                                    'send_sms_interval' => $data['send_sms_interval'],
                                ]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('SMS interval yangilandi')
                        ->modalHeading('SMS intervalini yangilash'),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
