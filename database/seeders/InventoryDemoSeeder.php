<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $stock = app(StockService::class);
            foreach ([
                ['T-shirt Essential', 'Abbigliamento', 'Cheflow', [['Nero / M', 'DEMO-TSH-NER-M', 2990, 24], ['Nero / L', 'DEMO-TSH-NER-L', 2990, 3], ['Bianco / M', 'DEMO-TSH-BIA-M', 2990, 0]]],
                ['Felpa Studio', 'Abbigliamento', 'Cheflow', [['Verde / M', 'DEMO-FLP-VER-M', 6900, 16], ['Verde / L', 'DEMO-FLP-VER-L', 6900, 8]]],
                ['Tote bag Everyday', 'Accessori', 'Cheflow', [['Naturale', 'DEMO-TOTE-NAT', 1800, 35]]],
                ['Cappellino Classic', 'Accessori', 'Cheflow', [['Nero', 'DEMO-CAP-NER', 2400, 4]]],
            ] as [$name, $category, $vendor, $variants]) {
                $product = Product::query()->firstOrCreate(['name' => $name], ['category' => $category, 'vendor' => $vendor, 'status' => 'active']);
                foreach ($variants as [$variantName, $sku, $price, $quantity]) {
                    if ($product->variants()->where('sku', $sku)->exists()) {
                        continue;
                    }
                    $variant = $product->variants()->create(['name' => $variantName, 'sku' => $sku, 'price_cents' => $price, 'low_stock_threshold' => 5]);
                    if ($quantity > 0) {
                        $stock->move($variant, 'in', $quantity, 'Giacenza iniziale', 'DEMO');
                    }
                }
            }
        });
    }
}
