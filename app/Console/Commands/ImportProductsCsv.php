<?php

namespace App\Console\Commands;

use SplFileObject;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Models\ProductStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProductsCsv extends Command
{
    protected $signature = 'products:import
        {file : Path to the CSV file}
        {--delimiter=, : CSV delimiter}
        {--no-header : Treat file as having no header row}
        {--update : Update existing products if found}
    ';

    protected $description = 'Import products from a CSV file, assign to all stocks with quantity 0, and set type to package.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path         = (string) $this->argument('file');
        $delimiter    = (string) $this->option('delimiter');
        $hasHeader    = !(bool) $this->option('no-header');
        $shouldUpdate = (bool) $this->option('update');

        if (!is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $stocks = Stock::query()->get();
        if ($stocks->isEmpty()) {
            $this->warn('No stocks found. Products will be created without product_stocks entries.');
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV|SplFileObject::SKIP_EMPTY|SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl($delimiter);

        $header    = [];
        $rowNumber = 0;
        $created   = 0;
        $updated   = 0;
        $skipped   = 0;

        if ($hasHeader) {
            $header = $this->readRow($file);
            if (empty($header)) {
                $this->error('CSV header row is empty or unreadable.');

                return self::FAILURE;
            }
            $header = array_map(function ($h) {
                return strtolower(trim((string) $h));
            }, $header);
        }

        while (!$file->eof()) {
            $rowNumber++;
            $row = $this->readRow($file);
            if ($row === null) {
                break; // EOF
            }
            if ($row === []) {
                continue; // skip blank
            }

            $data = $this->normalizeRow($row, $header, $hasHeader);

            $name         = trim((string) Arr::get($data, 'name', ''));
            $barcode      = trim((string) Arr::get($data, 'barcode', ''));
            $initialPrice = $this->toIntNullable(Arr::get($data, 'initial_price'));
            $price        = $this->toIntNullable(Arr::get($data, 'price'));
            $categoryId   = $this->resolveCategoryId($data);

            if ($name === '' && $barcode === '') {
                $skipped++;
                $this->line("[skip] Row {$rowNumber}: missing both name and barcode");
                continue;
            }

            DB::beginTransaction();

            try {
                $product = null;
                if ($barcode !== '') {
                    $product = Product::query()->where('barcode', $barcode)->first();
                }
                if (!$product && $name !== '') {
                    $product = Product::query()->where('name', $name)->first();
                }

                $exists = $product !== null;
                if (!$exists) {
                    $product = new Product;
                } elseif (!$shouldUpdate) {
                    DB::rollBack();
                    $skipped++;
                    $this->line("[skip] Row {$rowNumber}: product exists (name='{$name}', barcode='{$barcode}')");
                    continue;
                }

                if ($name !== '') {
                    $product->name = $name;
                }
                if ($barcode !== '') {
                    $product->barcode = $barcode;
                }
                if ($initialPrice !== null) {
                    $product->initial_price = $initialPrice;
                }
                if ($price !== null) {
                    $product->price = $price;
                }
                if ($categoryId !== null) {
                    $product->category_id = $categoryId;
                }

                $product->type = Product::TYPE_PACKAGE;
                $product->save();

                foreach ($stocks as $stock) {
                    ProductStock::firstOrCreate(
                        [
                            'product_id'      => $product->id,
                            'product_size_id' => null,
                            'stock_id'        => $stock->id,
                        ],
                        [
                            'quantity' => 0,
                        ]
                    );
                }

                DB::commit();
                if ($exists) {
                    $updated++;
                    $this->line("[update] Row {$rowNumber}: '{$product->name}' (#{$product->id})");
                } else {
                    $created++;
                    $this->line("[create] Row {$rowNumber}: '{$product->name}' (#{$product->id})");
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                $skipped++;
                $this->error("[error] Row {$rowNumber}: " . $e->getMessage());
            }
        }

        $this->info("Done. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * Read next CSV row as array of strings. Returns [] for blank lines, null at EOF.
     */
    protected function readRow(SplFileObject $file): ?array
    {
        if ($file->eof()) {
            return null;
        }

        $row = $file->fgetcsv();
        if ($row === false) {
            return null;
        }

        // Handle BOM on first cell
        if (isset($row[0])) {
            $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
        }

        // Normalize: cast all to string and trim
        $row = array_map(function ($v) {
            return is_null($v) ? '' : trim((string) $v);
        }, $row);

        // Detect completely empty row
        if (count(array_filter($row, fn ($v) => $v !== '')) === 0) {
            return [];
        }

        return $row;
    }

    /**
     * Map CSV row to associative array using header if present, otherwise positional mapping.
     * Supported keys: name, barcode, price, initial_price, category, category_id
     */
    protected function normalizeRow(array $row, array $header, bool $hasHeader): array
    {
        $data = [];
        if ($hasHeader) {
            foreach ($row as $i => $value) {
                $key        = $header[$i] ?? (string) $i;
                $data[$key] = $value;
            }
        } else {
            // Positional: 0=name, 1=barcode, 2=price, 3=initial_price, 4=category
            $data['name']          = $row[0] ?? '';
            $data['barcode']       = $row[1] ?? '';
            $data['price']         = $row[2] ?? '';
            $data['initial_price'] = $row[3] ?? '';
            $data['category']      = $row[4] ?? '';
        }

        return $data;
    }

    protected function toIntNullable(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        // Remove common number formatting
        $normalized = preg_replace('/[^0-9\-]/', '', $value);
        if ($normalized === '' || $normalized === '-') {
            return null;
        }

        return (int) $normalized;
    }

    protected function resolveCategoryId(array $data): ?int
    {
        $categoryId = Arr::get($data, 'category_id');
        if ($categoryId !== null && $categoryId !== '') {
            return (int) $categoryId;
        }

        $categoryName = trim((string) Arr::get($data, 'category', ''));
        if ($categoryName === '') {
            return null;
        }

        $category = Category::query()->firstOrCreate([
            'name' => $categoryName,
        ]);

        return (int) $category->id;
    }
}
