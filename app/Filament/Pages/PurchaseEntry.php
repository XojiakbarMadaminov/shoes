<?php

namespace App\Filament\Pages;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ProductSize;
use App\Models\ProductStock;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
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

class PurchaseEntry extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel                = 'Ta’minotchidan xarid';
    protected static ?string $title                          = 'Ta’minotchidan xarid qilish';
    protected static ?string $slug                           = 'purchases/create';
    protected static ?int $navigationSort                    = 2;

    protected string $view = 'filament.pages.purchase-entry';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'purchase_date' => now(),
            'payment_type'  => 'cash',
        ]);
    }

    // ✅ Filament 4 uchun to‘g‘ri form() metodi
    public function form(Schema $schema)
    {
        return $schema
            ->components([
                Section::make('Xarid maʼlumotlari')
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
                            ->default(today())
                            ->required(),

                        Select::make('payment_type')
                            ->label('To‘lov turi')
                            ->options([
                                'cash'    => 'Naqd',
                                'card'    => 'Karta',
                                'debt'    => 'Qarz',
                                'partial' => 'Qisman',
                            ])
                            ->default('cash')
                            ->live()
                            ->required(),

                        TextInput::make('partial_paid_amount')
                            ->label('Qisman to‘langan summa')
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
                                    ->options(fn () => Product::withoutGlobalScope('current_store')->orderBy('name')->get()
                                        ->mapWithKeys(fn ($product) => [$product->id => $product->display_label ?? $product->name]))
                                    ->getSearchResultsUsing(function (string $search) {
                                        if (blank($search)) {
                                            return Product::withoutGlobalScope('current_store')->orderBy('name')
                                                ->limit(50)
                                                ->get()
                                                ->mapWithKeys(fn ($product) => [$product->id => $product->display_label ?? $product->name]);
                                        }

                                        return Product::withoutGlobalScope('current_store')
                                            ->where(fn ($q) => $q
                                                ->where('barcode', 'ILIKE', "%{$search}%")
                                                ->orWhere('name', 'ILIKE', "%{$search}%"))
                                            ->orderBy('name')
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn ($product) => [$product->id => $product->display_label ?? $product->name]);
                                    })
                                    ->getOptionLabelUsing(fn ($value) => optional(Product::withoutGlobalScope('current_store')->find($value))->display_label ?? '')
                                    ->required()
                                    ->createOptionForm($this->productCreateForm())
                                    ->createOptionUsing(function (array $data) {
                                        $stockId = data_get($this->form->getState(), 'stock_id');

                                        if (! $stockId) {
                                            throw ValidationException::withMessages([
                                                'stock_id' => 'Mahsulot yaratishdan oldin skladni tanlang.',
                                            ]);
                                        }

                                        $sizes = collect($data['sizes'] ?? [])
                                            ->pluck('size')
                                            ->filter()
                                            ->unique()
                                            ->values();

                                        unset($data['sizes']);

                                        $product = Product::create([
                                            'name'          => $data['name'],
                                            'barcode'       => $data['barcode'] ?? null,
                                            'type'          => $data['type'],
                                            'initial_price' => $data['initial_price'] ?? 0,
                                            'price'         => $data['price'] ?? 0,
                                            'category_id'   => $data['category_id'] ?? null,
                                        ]);

                                        $createdSizes = collect();

                                        if ($product->isSizeBased() && $sizes->isNotEmpty()) {
                                            foreach ($sizes as $size) {
                                                $createdSizes->push(
                                                    $product->sizes()->create(['size' => $size])
                                                );
                                            }
                                        }

                                        if ($stockId) {
                                            if ($product->isPackageBased()) {
                                                ProductStock::firstOrCreate(
                                                    [
                                                        'product_id'      => $product->id,
                                                        'product_size_id' => null,
                                                        'stock_id'        => $stockId,
                                                    ],
                                                    ['quantity' => 0]
                                                );
                                            } else {
                                                $createdSizes->each(function (ProductSize $size) use ($stockId) {
                                                    ProductStock::firstOrCreate(
                                                        [
                                                            'product_id'      => null,
                                                            'product_size_id' => $size->id,
                                                            'stock_id'        => $stockId,
                                                        ],
                                                        ['quantity' => 0]
                                                    );
                                                });
                                            }
                                        }

                                        return $product->getKey();
                                    })
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
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
                                    ->label('Razmerlar')
                                    ->createItemButtonLabel('Razmer qo‘shish')
                                    ->reorderable(false)
                                    ->visible(fn (Get $get) => ($get('product_type') ?? Product::TYPE_PACKAGE) === Product::TYPE_SIZE)
                                    ->columnSpan(12)
                                    ->schema([
                                        Hidden::make('product_size_id'),
                                        TextInput::make('size_label')
                                            ->label('Razmer')
                                            ->required(fn (Get $get) => blank($get('product_size_id')))
                                            ->disabled(fn (Get $get) => filled($get('product_size_id')))
                                            ->placeholder('Razmer nomi'),
                                        TextInput::make('quantity')
                                            ->label('Miqdor')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                    ])
                                    ->columns(3),

                                Textarea::make('item_note')
                                    ->label('Izoh')
                                    ->rows(1)
                                    ->columnSpan(12)
                                    ->maxLength(300)
                                    ->placeholder('Ixtiyoriy izoh'),
                            ])
                            ->addActionLabel('Mahsulot qo‘shish'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function productCreateForm(): array
    {
        return [
            TextInput::make('name')
                ->label('Mahsulot nomi')
                ->required(),

            TextInput::make('barcode')
                ->label('Bar kod')
                ->nullable()
                ->rule(fn () => Rule::unique('products', 'barcode')),

            Select::make('type')
                ->label('Turi')
                ->options([
                    Product::TYPE_PACKAGE => 'Paketli',
                    Product::TYPE_SIZE    => 'Razmerli',
                ])
                ->default(Product::TYPE_PACKAGE)
                ->live()
                ->required(),

            TextInput::make('initial_price')
                ->label('Kelgan narxi')
                ->numeric()
                ->minValue(0)
                ->default(0),

            TextInput::make('price')
                ->label('Sotish narxi')
                ->numeric()
                ->minValue(0)
                ->default(0),

            Repeater::make('sizes')
                ->label('Razmerlar')
                ->visible(fn (Get $get) => ($get('type') ?? Product::TYPE_PACKAGE) === Product::TYPE_SIZE)
                ->schema([
                    TextInput::make('size')
                        ->label('Razmer')
                        ->required(),
                ])
                ->minItems(1)
                ->default(fn () => collect(range(36, 41))->map(fn ($size) => ['size' => $size])->toArray()),
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $stockId = $data['stock_id'] ?? null;

        $data['items'] = collect($data['items'] ?? [])
            ->map(function (array $item) use ($stockId) {
                $productId = $item['product_id'] ?? null;

                if (! $productId) {
                    return $item;
                }

                $product = Product::withoutGlobalScope('current_store')
                    ->with('sizes')
                    ->find($productId);

                if (! $product || ($item['product_type'] ?? Product::TYPE_PACKAGE) !== Product::TYPE_SIZE) {
                    return $item;
                }

                $item['size_quantities'] = collect($item['size_quantities'] ?? [])
                    ->map(function (array $row) use ($product, $stockId) {
                        $sizeId    = $row['product_size_id'] ?? null;
                        $sizeLabel = trim((string) ($row['size_label'] ?? ''));

                        if (! $sizeId && $sizeLabel !== '') {
                            $size = $product->sizes()->firstOrCreate(['size' => $sizeLabel]);
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
            $purchase = app(PurchaseService::class)->create($data);

            Notification::make()
                ->title('Xarid muvaffaqiyatli saqlandi')
                ->success()
                ->send();

            $this->redirect(PurchaseHistoryPage::getUrl());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            report($throwable);

            Notification::make()
                ->title('Xaridni saqlashda xatolik yuz berdi')
                ->body($throwable->getMessage())
                ->danger()
                ->send();
        }
    }
}
