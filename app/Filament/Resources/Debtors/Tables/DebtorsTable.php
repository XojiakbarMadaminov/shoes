<?php

namespace App\Filament\Resources\Debtors\Tables;

use App\Models\Debtor;
use App\Models\DebtorTransaction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Debtors\DebtorResource;

class DebtorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $latestDateSubquery = DebtorTransaction::select('date')
                    ->whereColumn('debtor_transactions.debtor_id', 'debtors.id')
                    ->latest('date')
                    ->limit(1);

                $query
                    ->with('latestTransaction')
                    ->orderBy($latestDateSubquery, 'desc');
            })
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('client.full_name')
                    ->searchable()
                    ->label('To`liq ism'),
                TextColumn::make('client.phone')
                    ->searchable()
                    ->label('Telefon nomer'),
                TextColumn::make('amount')
                    ->sortable()
                    ->label('Qarz summasi'),
                TextColumn::make('latestTransaction.date')
                    ->label('Oxirgi operatsiya')
                    ->sortable(),
            ])
            ->recordUrl(fn ($record) => DebtorResource::getUrl('view', ['record' => $record]))
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('add_payment')
                    ->label('To‘lov qilish')
                    ->color('success')
                    ->schema(fn (Debtor $record) => [
                        TextInput::make('amount')
                            ->label('To‘lov summasi')
                            ->prefix($record->currency)
                            ->numeric()
                            ->required()
                            ->rule('lte:' . $record->amount) // `amount` dan katta bo‘lmasin
                            ->helperText('Maksimum: ' . $record->amount . ' ' . $record->currency),

                        DateTimePicker::make('date')
                            ->label('To‘lov sanasi')
                            ->default(now())
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
                            'type'   => 'payment',
                            'amount' => $data['amount'],
                            'date'   => $data['date'],
                            'note'   => $data['note'] ?? null,
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
                    DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ]);
    }
}
