<?php

namespace App\Filament\Resources\Debtors\Tables;

use App\Models\Debtor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DebtorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('full_name')
                    ->searchable()
                    ->label('To`liq ism'),
                TextColumn::make('phone')
                    ->searchable()
                    ->label('Telefon nomer'),
                TextColumn::make('amount')
                    ->sortable()
                    ->label('Qarz summasi'),
                TextColumn::make('currency')
                    ->label('Valyuta'),
                TextColumn::make('date')
                    ->sortable()
                    ->label('Sana')
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('add_debt')
                    ->label('Qarz qo‘shish')
                    ->color('danger')
                    ->form([
                        TextInput::make('amount')
                            ->label('Qarz summasi')
                            ->prefix(fn(Debtor $record) => $record->currency)
                            ->numeric()
                            ->required(),
                        Textarea::make('note')
                            ->label('Izoh')
                            ->nullable(),
                        DatePicker::make('date')
                            ->label('Sana')
                            ->default(today()),
                    ])
                    ->action(function (array $data, Debtor $record) {
                        // Bazaga yozish
                        $record->transactions()->create([
                            'type' => 'debt',
                            'amount' => $data['amount'],
                            'date' => $data['date'],
                            'note' => $data['note'] ?? null,
                        ]);

                        $record->increment('amount', $data['amount']); // total qarz yangilanadi
                    }),
                Action::make('add_payment')
                    ->label('To‘lov qilish')
                    ->color('success')
                    ->form(fn(Debtor $record) => [
                        TextInput::make('amount')
                            ->label('To‘lov summasi')
                            ->prefix($record->currency)
                            ->numeric()
                            ->required()
                            ->rule('lte:' . $record->amount) // `amount` dan katta bo‘lmasin
                            ->helperText('Maksimum: ' . $record->amount . ' ' . $record->currency),

                        DatePicker::make('date')
                            ->label('To‘lov sanasi')
                            ->default(today())
                            ->required(),

                        Textarea::make('note')
                            ->label('Izoh')
                            ->nullable(),
                    ])
                    ->action(function (array $data, Debtor $record) {
                        if ($data['amount'] > $record->amount) {
                            Notification::make()
                                ->title('To‘lov summasi mavjud qarzdan katta bo‘lishi mumkin emas')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->transactions()->create([
                            'type' => 'payment',
                            'amount' => $data['amount'],
                            'date' => $data['date'],
                            'note' => $data['note'] ?? null,
                        ]);

                        $record->decrement('amount', $data['amount']);
                    }),
                Action::make('view_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document')
                    ->url(fn ($record) => route('debtor.check.pdf', $record))
                    ->openUrlInNewTab()
                    ->color('gray'),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
