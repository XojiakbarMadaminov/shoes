<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountType: string implements HasLabel
{
    case GlobalPercent           = 'global_percent';
    case SelectedProductsPercent = 'selected_products_percent';
    case CategoryPercent         = 'category_percent';
    case OrderAmountPercent      = 'order_amount_percent';

    public function getLabel(): string
    {
        return match ($this) {
            self::GlobalPercent           => 'Barcha tovarlarga foiz',
            self::SelectedProductsPercent => 'Tanlangan tovarlarga foiz',
            self::CategoryPercent         => "Kategoriya bo'yicha foiz",
            self::OrderAmountPercent      => 'Buyurtma summasiga foiz',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->getLabel()])
            ->all();
    }

    public function isProductScope(): bool
    {
        return in_array($this, [
            self::GlobalPercent,
            self::SelectedProductsPercent,
            self::CategoryPercent,
        ], true);
    }

    public function isOrderScope(): bool
    {
        return $this === self::OrderAmountPercent;
    }
}
