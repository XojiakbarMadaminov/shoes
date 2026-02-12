<?php

namespace App\Filament\Pages;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Filament\Pages\Page;
use App\Models\ProductStock;
use Filament\Schemas\Schema;
use App\Services\PurchaseService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Concerns\InteractsWithForms;

class PurchaseEdit extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title                 = 'Xaridni tahrirlash';
    protected static ?string $slug                  = 'purchases/{record}/edit';
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.purchase-edit';

    public ?array $data = [];

    public Purchase $purchase;

    public function mount(Purchase $record): void
    {
        $this->purchase = $record->loadMissing(['items.product', 'items.productSize']);

        $state = [
            'supplier_id'   => $this->purchase->supplier_id,
            'stock_id'      => $this->purchase->stock_id,
            'purchase_date' => $this->purchase->purchase_date,
            'payment_type'  => $this->purchase->payment_type,
            'note'          => $this->purchase->note,
        ];

        if ($this->purchase->payment_type === 'partial') {
            $state['partial_paid_amount'] = (float) ($this->purchase->paid_amount ?? 0);
        }

        $state['items'] = $this->buildItemsState();

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Xarid ma’lumotlari')
                    ->columns(2)
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Ta’minotchi')
                            ->options(fn () => Supplier::orderBy('full_name')->pluck('full_name', 'id'))
                            ->searchable()
                            ->required(),

                        Select::make('stock_id')
                            ->label('Sklad')
                            ->options(fn () => Stock::query()->where('is_active', true)->pluck('name', 'id'))
                            ->live()
                            ->searchable()
                            ->required(),

                        DatePicker::make('purchase_date')
                            ->label('Xarid sanasi')
                            ->required(),

                        Select::make('payment_type')
                            ->label('To’lov turi')
                            ->options([
                                'cash'    => 'Naqd',
                                'card'    => 'Karta',
                                'debt'    => 'Qarz',
                                'partial' => 'Qisman',
                            ])
                            ->live()
                            ->required(),

                        TextInput::make('partial_paid_amount')
                            ->label('Qisman to’langan summa')
                            ->numeric()
                            ->minValue(0.01)
                            ->visible(fn (Get $get) => $get('payment_type') === 'partial'),

                        Textarea::make('note')
                            ->label('Izoh')
                            ->columnSpanFull(),
                    ]),

                Section::make('Mahsulotlar')
                    ->hidden(fn (Get $get) => blank($get('stock_id')))
                    ->schema([
                        Repeater::make('items')
                            ->label('Mahsulot pozitsiyalari')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columns(12)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Mahsulot')
                                    ->columnSpan(4)
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->options(
                                        fn () => Product::withoutGlobalScope('current_store')->orderBy('name')->get()
                                            ->mapWithKeys(fn ($product) => [$product->id => $product->display_label])
                                    )
                                    ->afterStateUpdated(function (Set $set, ?string $state, Get $get) {
                                        $product = $state ? Product::withoutGlobalScope('current_store')->with('sizes')->find($state) : null;
                                        $type    = $product?->type ?? Product::TYPE_PACKAGE;
                                        $set('product_type', $type);

                                        if ($type === Product::TYPE_PACKAGE) {
                                            $set('size_quantities', []);
                                            $set('quantity', $get('quantity') ?: 1);
                                        } else {
                                            $set('size_quantities', $product?->sizes?->map(fn ($size) => [
                                                'product_size_id' => $size->id,
                                                'size_label'      => $size->size,
                                                'quantity'        => 0,
                                            ])->toArray() ?? []);
                                        }
                                    }),

                                Hidden::make('product_type')->default(Product::TYPE_PACKAGE),

                                TextInput::make('unit_cost')
                                    ->label('Xarid narxi')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->columnSpan(2)
                                    ->required(),

                                TextInput::make('quantity')
                                    ->label('Miqdor')
                                    ->visible(fn (Get $get) => ($get('product_type') ?? Product::TYPE_PACKAGE) === Product::TYPE_PACKAGE)
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->columnSpan(2),

                                Repeater::make('size_quantities')
                                    ->label(fn (Get $get) => ($get('product_type') ?? Product::TYPE_PACKAGE) === Product::TYPE_COLOR ? 'Ranglar' : 'Razmerlar')
                                    ->createItemButtonLabel(fn (Get $get) => ($get('product_type') ?? Product::TYPE_PACKAGE) === Product::TYPE_COLOR ? 'Rang qo’shish' : 'Razmer qo’shish')
                                    ->reorderable(false)
                                    ->visible(fn (Get $get) => in_array(($get('product_type') ?? Product::TYPE_PACKAGE), [Product::TYPE_SIZE, Product::TYPE_COLOR], true))
                                    ->columnSpan(12)
                                    ->schema([
                                        Hidden::make('product_size_id'),
                                        TextInput::make('size_label')
                                            ->label(fn (Get $get) => ($get('product_type') ?? Product::TYPE_PACKAGE) === Product::TYPE_COLOR ? 'Rang' : 'Razmer')
                                            ->required(fn (Get $get) => blank($get('product_size_id')))
                                            ->disabled(fn (Get $get) => filled($get('product_size_id')))
                                            ->placeholder(fn (Get $get) => ($get('product_type') ?? Product::TYPE_PACKAGE) === Product::TYPE_COLOR ? 'Rang nomi' : 'Razmer nomi'),
                                        TextInput::make('quantity')
                                            ->label('Miqdor')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                    ])
                                    ->columns(3),
                            ])
                            ->addActionLabel('Mahsulot qo’shish'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function buildItemsState(): array
    {
        $grouped = $this->purchase->items->groupBy('product_id');

        return $grouped->map(function ($rows, $productId) {
            /** @var \Illuminate\Support\Collection $rows */
            $product = $rows->first()->product;
            $unit    = (float) ($rows->first()->unit_cost ?? 0);

            if ($product?->isPackageBased()) {
                $row = $rows->first();

                return [
                    'product_id'      => (int) $productId,
                    'product_type'    => Product::TYPE_PACKAGE,
                    'unit_cost'       => $unit,
                    'quantity'        => (int) ($row->quantity ?? 1),
                    'size_quantities' => [],
                ];
            }

            return [
                'product_id'      => (int) $productId,
                'product_type'    => $product->type ?? Product::TYPE_SIZE,
                'unit_cost'       => $unit,
                'quantity'        => 0,
                'size_quantities' => $rows->map(function ($row) {
                    return [
                        'product_size_id' => $row->product_size_id,
                        'size_label'      => $row->productSize?->size,
                        'quantity'        => (int) $row->quantity,
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Normalize dynamic size creation like in create page
        $stockId       = $data['stock_id'] ?? null;
        $data['items'] = collect($data['items'] ?? [])
            ->map(function (array $item) use ($stockId) {
                $productId = $item['product_id'] ?? null;

                if (!$productId) {
                    return $item;
                }

                $product = Product::withoutGlobalScope('current_store')
                    ->with('sizes')
                    ->find($productId);

                if (!$product || !in_array(($item['product_type'] ?? Product::TYPE_PACKAGE), [Product::TYPE_SIZE, Product::TYPE_COLOR], true)) {
                    return $item;
                }

                $item['size_quantities'] = collect($item['size_quantities'] ?? [])
                    ->map(function (array $row) use ($product, $stockId) {
                        $sizeId    = $row['product_size_id'] ?? null;
                        $sizeLabel = trim((string) ($row['size_label'] ?? ''));

                        if (!$sizeId && $sizeLabel !== '') {
                            $size                   = $product->sizes()->firstOrCreate(['size' => $sizeLabel]);
                            $row['product_size_id'] = $size->id;

                            if ($stockId) {
                                ProductStock::firstOrCreate(
                                    [
                                        'product_id'      => null,
                                        'product_size_id' => $size->id,
                                        'stock_id'        => $stockId,
                                    ],
                                    ['quantity' => 0]
                                );
                            }
                        }

                        return $row;
                    })
                    ->toArray();

                return $item;
            })
            ->toArray();

        try {
            $purchase = app(PurchaseService::class)->update($this->purchase, $data);

            Notification::make()
                ->title("Xarid #{$purchase->id} yangilandi")
                ->success()
                ->send();

            $this->redirect(PurchaseHistoryPage::getUrl());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            report($throwable);

            Notification::make()
                ->title('Xaridni yangilashda xatolik yuz berdi')
                ->body($throwable->getMessage())
                ->danger()
                ->send();
        }
    }
}
