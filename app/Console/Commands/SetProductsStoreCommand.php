<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class SetProductsStoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:set-store {store_id=1 : Store ID that should be assigned to every product}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Overwrites the store_id of every product record with the provided store ID.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storeId = (int) $this->argument('store_id');

        if ($storeId <= 0) {
            $this->error('Store ID must be a positive integer.');

            return self::FAILURE;
        }

        $updatedCount = Product::withoutGlobalScopes()->update([
            'store_id' => $storeId,
        ]);

        $this->info("Updated {$updatedCount} product(s) to store ID {$storeId}.");

        return self::SUCCESS;
    }
}
