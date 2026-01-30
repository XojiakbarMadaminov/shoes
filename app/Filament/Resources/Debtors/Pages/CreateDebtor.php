<?php

namespace App\Filament\Resources\Debtors\Pages;

use App\Models\Client;
use App\Models\Debtor;
use Illuminate\Support\Carbon;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use App\Filament\Resources\Debtors\DebtorResource;

class CreateDebtor extends CreateRecord
{
    protected static string $resource = DebtorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $storeId = auth()->user()?->current_store_id;

        $rawPhone = $data['phone'] ?? null;
        $fullName = $data['full_name'] ?? null;

        $digits = is_string($rawPhone)
            ? preg_replace('/\D/', '', (string) $rawPhone)
            : null;

        if (!$digits) {
            throw ValidationException::withMessages([
                'phone' => 'Telefon raqam to\'g\'ri kiritilmagan.',
            ]);
        }

        // Canonicalize to +998XXXXXXXXX and search strictly by this format
        $last9     = substr($digits, -9);
        $canonical = '+998' . $last9;

        $client = Client::withTrashed()
            ->where('phone', $canonical)
            ->first();

        if ($client) {
            // If client exists and has active debtor (> 0) in current store, block creation.
            $hasActiveDebt = Debtor::query()
                ->where('store_id', $storeId)
                ->where('client_id', $client->id)
                ->where('amount', '>', 0)
                ->exists();

            if ($hasActiveDebt) {
                Notification::make()
                    ->title("Ushbu mijoz qarzdorlar ro'yxatida allaqachon mavjud")
                    ->danger()
                    ->persistent()
                    ->send();

                throw new Halt;
            }

            if ($client->trashed()) {
                $client->restore();
            }

            $client->update([
                'full_name' => $fullName,
            ]);
        } else {
            // Create client if not exists
            $client = Client::create([
                'full_name' => $fullName,
                'phone'     => $canonical,
            ]);
        }

        // Bind to debtor
        $data['client_id'] = $client->id;
        $data['store_id']  = $storeId;

        // Remove non-debtor fields
        unset($data['phone'], $data['full_name']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $dateTime = Carbon::parse($this->record->date)
            ->setTimeFromTimeString(now()->format('H:i:s'));

        $this->record->transactions()->create([
            'type'   => 'debt',
            'amount' => $this->record->amount,
            'date'   => $dateTime,
            'note'   => 'Dastlabki qarz',
        ]);
    }
}
