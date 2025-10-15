<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Color;
use App\Models\Stock;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreWithStocksAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            ['title' => 'Qora', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Oq', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Qizil', 'created_at' => now(), 'updated_at' => now()],
        ];

        Color::query()->upsert($colors, ['title'], ['updated_at']);

        $store = Store::query()->updateOrCreate(
            ['name' => 'Main Store'],
            [
                'address' => 'Toshkent, Chilonzor',
                'phone'   => '+998901234567',
            ]
        );

        // 2️⃣ 2 ta stock yaratish
        $stocks = [
            ['name' => 'Asosiy Ombor'],
            ['name' => 'Filial Ombori'],
        ];

        $stockIds = [];
        foreach ($stocks as $stock) {
            $created = Stock::query()->updateOrCreate(
                ['name' => $stock['name']],
                ['is_active' => true]
            );
            $stockIds[] = $created->id;
        }

        // 3️⃣ Store va stocklarni pivot orqali bog‘lash
        $store->stocks()->syncWithoutDetaching($stockIds);

        // 4️⃣ Admin user yaratish
        $user = User::query()->where('users.email', 'super@gmail.com')->first();

        // 5️⃣ Userga store biriktirish

        $user->stores()->syncWithoutDetaching([$store->id]);
        $user->update(['current_store_id' => $store->id]);

        $this->command->info('✅ Store, Stocks va User muvaffaqiyatli yaratildi va bog‘landi (pivot orqali).');
    }
}
