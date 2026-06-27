<?php

namespace App\Filament\Resources\Discounts\Pages;

use App\Enums\DiscountType;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Discounts\DiscountResource;

class EditDiscount extends EditRecord
{
    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->typeValue($data['type'] ?? $this->record->type) !== DiscountType::OrderAmountPercent->value) {
            $data['min_order_amount'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->type !== DiscountType::SelectedProductsPercent) {
            $this->record->products()->detach();
        }

        if ($this->record->type !== DiscountType::CategoryPercent) {
            $this->record->categories()->detach();
        }
    }

    private function typeValue(mixed $state): ?string
    {
        if ($state instanceof DiscountType) {
            return $state->value;
        }

        return is_string($state) ? $state : null;
    }
}
