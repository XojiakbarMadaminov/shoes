<?php

namespace App\Filament\Resources\Discounts\Pages;

use App\Enums\DiscountType;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Discounts\DiscountResource;

class CreateDiscount extends CreateRecord
{
    protected static string $resource = DiscountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->typeValue($data['type'] ?? null) !== DiscountType::OrderAmountPercent->value) {
            $data['min_order_amount'] = null;
        }

        return $data;
    }

    protected function afterCreate(): void
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
