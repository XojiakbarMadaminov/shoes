<?php

namespace App\Filament\Resources\Debtors\Pages;

use App\Filament\Resources\Debtors\DebtorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDebtor extends CreateRecord
{
    protected static string $resource = DebtorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = auth()->user()?->current_store_id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->transactions()->create([
            'type'   => 'debt',
            'amount' => $this->record->amount,
            'date'   => $this->record->date,
            'note'   => 'Dastlabki qarz',
        ]);
    }
}
