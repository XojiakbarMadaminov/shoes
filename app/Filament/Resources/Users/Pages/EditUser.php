<?php

namespace App\Filament\Resources\Users\Pages;

use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Users\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (User $record): bool => $record->email !== 'super@gmail.com'),
        ];
    }
}
