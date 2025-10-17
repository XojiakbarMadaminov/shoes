<?php

namespace App\Filament\Resources\Debtors\Schemas;

use App\Models\Debtor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class DebtorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()->schema([

                    TextInput::make('full_name')
                        ->label('To‘liq ism')
                        ->placeholder('Ism Familiya')
                        ->required()
                        ->maxLength(100)
                        ->columnSpanFull(),

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

                            $exists = Debtor::where('phone', $phone)
                                ->where('store_id', auth()->user()->current_store_id)
                                ->where('amount', '>', 0)
                                ->exists();

                            if ($exists) {
                                $set('phone', null);
                                Notification::make()
                                    ->title('Ushbu raqam qarzdorlar ro‘yxatida mavjud!')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->dehydrateStateUsing(fn ($state) => $state === '0' ? '0' : '+998' . preg_replace('/\D/', '', $state))
                        ->formatStateUsing(fn ($state) => $state && $state !== '0'
                            ? ltrim(preg_replace('/^\+998/', '', $state), '0')
                            : $state),
//
//
//                    Select::make('currency')
//                        ->label('Valyuta')
//                        ->options([
//                            'uzs' => 'UZS (So‘m)',
//                            'usd' => 'USD (Dollar)',
//                        ])
//                        ->default('uzs')
//                        ->required(),


                    TextInput::make('amount')
                        ->label('Qarz summasi')
                        ->numeric()
                        ->placeholder('Masalan: 150 000')
                        ->required(),

                    DatePicker::make('date')
                        ->label('Qarz sanasi')
                        ->default(today())
                        ->required(),
                ])->columnSpanFull(),

                Textarea::make('note')
                    ->label('Qo‘shimcha qaydlar')
                    ->placeholder('Masalan: Do‘kon tovarlari uchun...')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}
