<?php

namespace App\Filament\Pages;

use App\Models\Stock;
use App\Models\Product;
use Filament\Pages\Page;
use App\Models\ProductStock;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Concerns\InteractsWithForms;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class MoveProduct extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected static ?string $navigationLabel                = 'Tovarlarni ko‘chirish';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $title                          = 'Tovarlarni ko‘chirish';
    protected static ?int $navigationSort                    = 4;

    protected string $view = 'filament.pages.move-product';

    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns()
                    ->schema([
                        Select::make('from_stock_id')
                            ->label('Qayerdan')
                            ->options(Stock::scopes('active')->pluck('name', 'id'))
                            ->required(),

                        Select::make('to_stock_id')
                            ->label('Qayerga')
                            ->options(Stock::scopes('active')->pluck('name', 'id'))
                            ->required(),
                    ])->columnSpanFull(),

                Section::make()
                    ->columns()
                    ->schema([
                        Repeater::make('products')
                            ->label('Mahsulotlar')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Mahsulot')
                                    ->searchable()
                                    ->reactive()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return Product::query()
                                            ->where('name', 'ilike', "%{$search}%")
                                            ->orWhere('barcode', 'ilike', "%{$search}%")
                                            ->limit(50)
                                            ->pluck('name', 'id');
                                    })
                                    ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                                    ->afterStateUpdated(function ($set, ?string $state) {
                                        if (!$state) {
                                            $set('sizes', []);
                                            $set('type', null);
                                            $set('package_quantity', 0);

                                            return;
                                        }

                                        $product = Product::with('sizes')->find($state);
                                        $set('type', $product?->type ?? 'size');
                                        $sizes = $product?->sizes?->map(fn ($size) => [
                                            'size_id'   => $size->id,
                                            'size_name' => $size->size,
                                            'quantity'  => 0,
                                        ])->toArray() ?? [];

                                        $set('sizes', $sizes);
                                    })
                                    ->required()
                                    ->autofocus(),

                                Hidden::make('type'),

                                TextInput::make('package_quantity')
                                    ->label('Miqdor (paket)')
                                    ->numeric()
                                    ->default(0)
                                    ->visible(fn (Get $get) => ($get('type') ?? 'size') === 'package'),

                                Repeater::make('sizes')
                                    ->label('Razmerlar')
                                    ->schema([
                                        Hidden::make('size_id'),
                                        Hidden::make('size_name'),
                                        TextInput::make('quantity')
                                            ->columnSpanFull()
                                            ->label(fn ($get) => (($get('size_name') ?? 'Razmer')))
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->required(false),
                                    ])
                                    ->grid(3)
                                    ->columns(3)
                                    ->default([])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->visible(fn (Get $get) => ($get('type') ?? 'size') === 'size'),
                            ])
                            ->grid()
                            ->minItems(1)
                            ->addActionLabel('Mahsulot qo‘shish')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ])
            ->columns()
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $productsData = collect($data['products'] ?? []);

        if ($productsData->isEmpty()) {
            Notification::make()
                ->title('Mahsulot tanlanmagan')
                ->warning()
                ->send();

            return;
        }

        $fromStockId = $data['from_stock_id'];
        $toStockId   = $data['to_stock_id'];

        $productNames = Product::whereIn('id', $productsData->pluck('product_id')->filter())
            ->pluck('name', 'id');

        $hasMovement = false;

        foreach ($productsData as $item) {
            $productId   = $item['product_id'] ?? null;
            $productName = $productNames[$productId] ?? 'Noma’lum mahsulot';

            foreach ($item['sizes'] ?? [] as $sizeItem) {
                $quantity = (int) ($sizeItem['quantity'] ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                $hasMovement = true;

                $sizeId   = $sizeItem['size_id'] ?? null;
                $sizeName = $sizeItem['size_name'] ?? 'Razmer';

                if (!$sizeId) {
                    continue;
                }

                $available = ProductStock::where('product_size_id', $sizeId)
                    ->where('stock_id', $fromStockId)
                    ->value('quantity') ?? 0;

                if ($quantity > $available) {
                    Notification::make()
                        ->title('Xatolik')
                        ->body("{$productName} ({$sizeName}) uchun maksimal {$available} dona ko‘chirishingiz mumkin.")
                        ->danger()
                        ->send();

                    return;
                }
            }
        }

        // Consider package-based items for movement flag
        foreach ($productsData as $item) {
            $type = $item['type'] ?? (Product::find($item['product_id'] ?? null)?->type ?? 'size');
            if ($type === 'package' && (int) ($item['package_quantity'] ?? 0) > 0) {
                $hasMovement = true;
                break;
            }
        }

        if (!$hasMovement) {
            Notification::make()
                ->title('Miqdor kiritilmadi')
                ->body('Har bir razmer uchun ko‘chiriladigan miqdorni kiriting.')
                ->warning()
                ->send();

            return;
        }

        try {
            DB::transaction(function () use ($productsData, $fromStockId, $toStockId) {
                foreach ($productsData as $item) {
                    $type = $item['type'] ?? (Product::find($item['product_id'] ?? null)?->type ?? 'size');
                    if ($type === 'package') {
                        $quantity = (int) ($item['package_quantity'] ?? 0);
                        if ($quantity > 0) {
                            $from = ProductStock::firstOrCreate(
                                [
                                    'product_id'      => $item['product_id'],
                                    'product_size_id' => null,
                                    'stock_id'        => $fromStockId,
                                ],
                                ['quantity' => 0]
                            );
                            if ($from->quantity < $quantity) {
                                throw new \RuntimeException('Yetarli miqdor mavjud emas.');
                            }
                            $from->decrement('quantity', $quantity);

                            $to = ProductStock::firstOrCreate(
                                [
                                    'product_id'      => $item['product_id'],
                                    'product_size_id' => null,
                                    'stock_id'        => $toStockId,
                                ],
                                ['quantity' => 0]
                            );
                            $to->increment('quantity', $quantity);
                        }
                        continue;
                    }
                    foreach ($item['sizes'] ?? [] as $sizeItem) {
                        $quantity = (int) ($sizeItem['quantity'] ?? 0);

                        if ($quantity <= 0) {
                            continue;
                        }

                        $sizeId = $sizeItem['size_id'] ?? null;

                        if (!$sizeId) {
                            continue;
                        }

                        $fromStock = ProductStock::firstOrCreate(
                            [
                                'product_size_id' => $sizeId,
                                'stock_id'        => $fromStockId,
                            ],
                            ['quantity' => 0]
                        );

                        if ($fromStock->quantity < $quantity) {
                            throw new \RuntimeException('Yetarli miqdor mavjud emas.');
                        }

                        $fromStock->decrement('quantity', $quantity);

                        $toStock = ProductStock::firstOrCreate(
                            [
                                'product_size_id' => $sizeId,
                                'stock_id'        => $toStockId,
                            ],
                            ['quantity' => 0]
                        );

                        $toStock->increment('quantity', $quantity);
                    }
                }
            });
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Ko‘chirish amalga oshmadi')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Muvaffaqiyatli')
            ->body('Mahsulotlar razmerlari bo‘yicha ko‘chirildi.')
            ->success()
            ->send();

        $this->form->fill();
    }
}
