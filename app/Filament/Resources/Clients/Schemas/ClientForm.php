<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\Client;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name'),
                TextInput::make('phone')
                    ->label('Telefon raqam')
                    ->maxLength(9)
                    ->prefix('+998')
                    ->placeholder('90 123 45 67 yoki 0')
                    ->required()
                    ->reactive()
                    ->rule('regex:/^[0-9]{0,9}$/')
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Agar foydalanuvchi 0 kiritsa — tekshiruv ishlamasin
                        if ($state === '0') {
                            return;
                        }

                        $phone = '+998' . preg_replace('/\D/', '', $state);

                        $exists = Client::where('phone', $phone)
                            ->exists();

                        if ($exists) {
                            $set('phone', null);
                            Notification::make()
                                ->title('Ushbu raqam klientlar ro‘yxatida mavjud!')
                                ->danger()
                                ->send();
                        }
                    })
                    ->dehydrateStateUsing(fn ($state) => $state === '0' ? '0' : '+998' . preg_replace('/\D/', '', $state))
                    ->formatStateUsing(fn ($state) => $state && $state !== '0'
                        ? ltrim(preg_replace('/^\+998/', '', $state), '0')
                        : $state),
                Toggle::make('send_sms')->label('SMS yuborishga ruhsat')->default(true),

            ]);
    }
}
