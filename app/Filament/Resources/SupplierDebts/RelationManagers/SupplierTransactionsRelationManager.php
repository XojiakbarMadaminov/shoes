<?php

namespace App\Filament\Resources\SupplierDebts\RelationManagers;

use Filament\Tables;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;

class SupplierTransactionsRelationManager extends RelationManager
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

                TextColumn::make('purchase_id')
                    ->label('Purchase ID')
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'text-blue-600 underline cursor-pointer'])
                    ->action(
                        Action::make('view_purchase')
                            ->label("Ko'rish")
                            ->modalHeading('Xarid tafsilotlari')
                            ->modalSubmitAction(false)
                            ->hidden(fn ($record) => blank($record->purchase_id))
                            ->modalContent(fn ($record) => view('filament.purchases.partials.purchase-details', [
                                'purchase' => optional($record->purchase)?->load(['supplier', 'items.product', 'items.productSize', 'stock', 'createdBy']),
                            ]))
                    ),

                TextColumn::make('amount')
                    ->label('Summasi')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Sana')
                    ->dateTime('Y-m-d H:i'),

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
            ])
            ->defaultSort('date', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_debt')
                ->label("Qarz qo'shish")
                ->color('danger')
                ->icon('heroicon-m-plus')
                ->form([
                    TextInput::make('amount')
                        ->label('Qarz summasi')
                        ->numeric()
                        ->required(),
                    DatePicker::make('date')
                        ->label('Sana')
                        ->default(today())
                        ->required(),
                    Textarea::make('note')
                        ->label('Izoh')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $this->ownerRecord->transactions()->create([
                        'type'   => 'debt',
                        'amount' => $data['amount'],
                        'date'   => $data['date'],
                        'note'   => $data['note'] ?? null,
                    ]);

                    $this->ownerRecord->increment('amount', $data['amount']);

                    Notification::make()
                        ->title("Qarz qo'shildi")
                        ->success()
                        ->send();
                }),

            Action::make('add_payment')
                ->label("To'lov qilish")
                ->color('success')
                ->icon('heroicon-m-cash')
                ->form([
                    TextInput::make('amount')
                        ->label("To'lov summasi")
                        ->numeric()
                        ->required(),
                    DatePicker::make('date')
                        ->label("To'lov sanasi")
                        ->default(today())
                        ->required(),
                    Textarea::make('note')
                        ->label('Izoh')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $amount  = (float) $data['amount'];
                    $current = (float) $this->ownerRecord->amount;

                    if ($amount > $current) {
                        Notification::make()
                            ->title('To‘lov summasi mavjud qarzdan katta bo‘lishi mumkin emas')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->ownerRecord->transactions()->create([
                        'type'   => 'payment',
                        'amount' => $amount,
                        'date'   => $data['date'],
                        'note'   => $data['note'] ?? null,
                    ]);

                    $this->ownerRecord->decrement('amount', $amount);

                    Notification::make()
                        ->title("To'lov qabul qilindi")
                        ->success()
                        ->send();
                }),
        ];
    }
}
