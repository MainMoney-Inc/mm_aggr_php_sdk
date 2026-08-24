<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class SeedShop extends Command
{
    protected $signature = 'shop:seed';

    protected $description = 'Insert demo products if the catalog is empty';

    public function handle(): int
    {
        $items = [
            ['sku' => 'DEMO-SHIRT', 'name' => 'Demo T-shirt', 'description' => 'Cotton demo shirt', 'price' => '25.00', 'currency' => 'USD'],
            ['sku' => 'DEMO-COFFEE', 'name' => 'Demo coffee', 'description' => 'A cup of demo coffee', 'price' => '5.00', 'currency' => 'USD'],
            ['sku' => 'DEMO-BUNDLE', 'name' => 'Demo bundle', 'description' => 'Shirt plus coffee', 'price' => '10.00', 'currency' => 'USD'],
        ];
        $created = 0;
        foreach ($items as $item) {
            $existing = Product::query()->where('sku', $item['sku'])->first();
            if ($existing === null) {
                Product::query()->create($item);
                $created++;
            }
        }
        $this->info("Seeded {$created} products (".Product::query()->count().' total).');

        return self::SUCCESS;
    }
}
