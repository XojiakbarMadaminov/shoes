<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Models\Client;
use App\Models\Supplier;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->label('Ismi va familiyasi')
                    ->required(),
                TextInput::make('phone')
                    ->label('Telefon raqam')
                    ->maxLength(9)
                    ->prefix('+998')
                    ->placeholder('90 123 45 67 yoki 0')
                    ->required()
                    ->reactive()
                    ->rule('regex:/^[0-9]{0,9}$/')
                    ->afterStateUpdated(function ($state, callable $set) {
                        $phone = '+998' . preg_replace('/\D/', '', $state);

                        $exists = Supplier::where('phone', $phone)
                            ->exists();

                        if ($exists) {
                            $set('phone', null);
                            Notification::make()
                                ->title('Ushbu raqam ro‘yxatda mavjud!')
                                ->danger()
                                ->send();
                        }
                    })
                    ->dehydrateStateUsing(fn ($state) => '+998' . preg_replace('/\D/', '', $state))
                    ->formatStateUsing(fn ($state) => $state && $state !== '0'
                        ? ltrim(preg_replace('/^\+998/', '', $state), '0')
                        : $state),
                TextInput::make('address')
                    ->label('Manzil')
                    ->nullable(),
            ]);
    }
}
