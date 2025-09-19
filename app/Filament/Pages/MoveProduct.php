<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Stock;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MoveProduct extends Page implements HasForms
{
    use InteractsWithForms, HasPageShield;

    protected static ?string $navigationLabel = 'Tovarlarni ko‘chirish';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $title = 'Tovarlarni ko‘chirish';

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
                                    ->getSearchResultsUsing(function (string $search) {
                                        return Product::query()
                                            ->where('name', 'ilike', "%{$search}%")
                                            ->orWhere('barcode', 'ilike', "%{$search}%")
                                            ->limit(50)
                                            ->pluck('name', 'id');
                                    })
                                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->name)
                                    ->required()
                                    ->autofocus(),

                                TextInput::make('quantity')
                                    ->label('Miqdor')
                                    ->numeric()
                                    ->required(),
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

        $productIds = collect($data['products'])->pluck('product_id')->all();

        $products = Product::whereIn('id', $productIds)
            ->pluck('name', 'id');

        $fromStockQuantities = ProductStock::whereIn('product_id', $productIds)
            ->where('stock_id', $data['from_stock_id'])
            ->pluck('quantity', 'product_id');

        foreach ($data['products'] as $item) {
            $productName = $products[$item['product_id']] ?? 'Noma’lum mahsulot';
            $currentQty = $fromStockQuantities[$item['product_id']] ?? 0;

            if ($item['quantity'] > $currentQty) {
                Notification::make()
                    ->title('Xatolik')
                    ->body("{$productName} uchun maksimal {$currentQty} dona ko‘chirishingiz mumkin.")
                    ->danger()
                    ->send();

                return;
            }
        }

        foreach ($data['products'] as $item) {
            ProductStock::where('product_id', $item['product_id'])
                ->where('stock_id', $data['from_stock_id'])
                ->decrement('quantity', $item['quantity']);

            ProductStock::updateOrCreate(
                [
                    'product_id' => $item['product_id'],
                    'stock_id' => $data['to_stock_id'],
                ],
                [
                    'quantity' => \DB::raw('quantity + ' . (int)$item['quantity']),
                ]
            );
        }

        Notification::make()
            ->title('Muvaffaqiyatli')
            ->body('Mahsulotlar stocklar orasida ko‘chirildi.')
            ->success()
            ->send();

        $this->form->fill();
    }


}
