<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Dummy Categories Exist
        $electronics = Category::firstOrCreate(['slug' => 'electronics'], ['name' => 'Electronics']);
        $fashion     = Category::firstOrCreate(['slug' => 'fashion'], ['name' => 'Fashion']);
        $accessories = Category::firstOrCreate(['slug' => 'accessories'], ['name' => 'Accessories']);

        // 2. Fetch Attribute Values (Created by AttributeSeeder)
        $red        = AttributeValue::where('slug', 'red')->first()?->id;
        $blue       = AttributeValue::where('slug', 'blue')->first()?->id;
        $black      = AttributeValue::where('slug', 'black')->first()?->id;
        $white      = AttributeValue::where('slug', 'white')->first()?->id;
        $sizeS      = AttributeValue::where('slug', 's')->first()?->id;
        $sizeM      = AttributeValue::where('slug', 'm')->first()?->id;
        $sizeL      = AttributeValue::where('slug', 'l')->first()?->id;
        $sizeXL     = AttributeValue::where('slug', 'xl')->first()?->id;
        $storage128 = AttributeValue::where('slug', '128gb')->first()?->id;
        $storage256 = AttributeValue::where('slug', '256gb')->first()?->id;
        $ram8       = AttributeValue::where('slug', '8gb')->first()?->id;
        $ram16      = AttributeValue::where('slug', '16gb')->first()?->id;

        // 10 Products List (Mixed with & without variants)
        $productsData = [
            // --- WITH VARIANTS (Electronics & Fashion) ---
            [
                'name'         => 'iPhone 15 Pro',
                'category_id'  => $electronics->id,
                'description'  => 'Latest Titanium design Apple iPhone with A17 Pro Chip.',
                'is_active'    => true,
                'variants'     => [
                    [
                        'sku' => 'IPH15P-BLK-128', 'price' => 119900.00, 'compare_at_price' => 125000.00, 'cost_price' => 105000.00, 'quantity' => 15, 'attributes' => array_filter([$black, $storage128, $ram8])
                    ],
                    [
                        'sku' => 'IPH15P-BLK-256', 'price' => 134900.00, 'compare_at_price' => 140000.00, 'cost_price' => 120000.00, 'quantity' => 10, 'attributes' => array_filter([$black, $storage256, $ram8])
                    ],
                    [
                        'sku' => 'IPH15P-WHT-256', 'price' => 134900.00, 'compare_at_price' => 140000.00, 'cost_price' => 120000.00, 'quantity' => 8, 'attributes' => array_filter([$white, $storage256, $ram8])
                    ],
                ]
            ],
            [
                'name'         => 'Premium Cotton Polo T-Shirt',
                'category_id'  => $fashion->id,
                'description'  => '100% Combed Cotton breathable polo t-shirt for daily wear.',
                'is_active'    => true,
                'variants'     => [
                    ['sku' => 'POLO-RED-M', 'price' => 850.00, 'compare_at_price' => 1000.00, 'cost_price' => 450.00, 'quantity' => 50, 'attributes' => array_filter([$red, $sizeM])],
                    ['sku' => 'POLO-RED-L', 'price' => 850.00, 'compare_at_price' => 1000.00, 'cost_price' => 450.00, 'quantity' => 40, 'attributes' => array_filter([$red, $sizeL])],
                    ['sku' => 'POLO-BLU-L', 'price' => 850.00, 'compare_at_price' => 1000.00, 'cost_price' => 450.00, 'quantity' => 30, 'attributes' => array_filter([$blue, $sizeL])],
                    ['sku' => 'POLO-BLU-XL', 'price' => 900.00, 'compare_at_price' => 1050.00, 'cost_price' => 500.00, 'quantity' => 20, 'attributes' => array_filter([$blue, $sizeXL])],
                ]
            ],
            [
                'name'         => 'MacBook Air M2',
                'category_id'  => $electronics->id,
                'description'  => 'Thin and lightweight Apple MacBook Air with M2 Processor.',
                'is_active'    => true,
                'variants'     => [
                    ['sku' => 'MBA-M2-8-256', 'price' => 125000.00, 'compare_at_price' => 130000.00, 'cost_price' => 110000.00, 'quantity' => 12, 'attributes' => array_filter([$ram8, $storage256])],
                    ['sku' => 'MBA-M2-16-256', 'price' => 145000.00, 'compare_at_price' => 150000.00, 'cost_price' => 128000.00, 'quantity' => 6, 'attributes' => array_filter([$ram16, $storage256])],
                ]
            ],
            [
                'name'         => 'Slim Fit Denim Jeans',
                'category_id'  => $fashion->id,
                'description'  => 'Comfortable stretchable denim jeans pant for men.',
                'is_active'    => true,
                'variants'     => [
                    ['sku' => 'JEANS-BLK-M', 'price' => 1850.00, 'compare_at_price' => 2200.00, 'cost_price' => 1000.00, 'quantity' => 25, 'attributes' => array_filter([$black, $sizeM])],
                    ['sku' => 'JEANS-BLK-L', 'price' => 1850.00, 'compare_at_price' => 2200.00, 'cost_price' => 1000.00, 'quantity' => 35, 'attributes' => array_filter([$black, $sizeL])],
                ]
            ],

            // --- WITHOUT VARIANTS / SIMPLE PRODUCTS ---
            [
                'name'         => 'Logitech Wireless Mouse M185',
                'category_id'  => $accessories->id,
                'description'  => 'Simple, reliable wireless mouse with plug-and-play connection.',
                'is_active'    => true,
                'price'        => 1250.00,
                'sku'          => 'LOGI-M185-BLK',
                'quantity'     => 100,
            ],
            [
                'name'         => 'Realme Power Bank 10000mAh',
                'category_id'  => $accessories->id,
                'description'  => '12W fast charging dual output power bank.',
                'is_active'    => true,
                'price'        => 1650.00,
                'sku'          => 'RLM-PB-10K',
                'quantity'     => 45,
            ],
            [
                'name'         => 'Keychron K2 Mechanical Keyboard Wrist Rest',
                'category_id'  => $accessories->id,
                'description'  => 'Wooden wrist rest for Keychron K2 mechanical keyboard.',
                'is_active'    => true,
                'price'        => 1500.00,
                'sku'          => 'KEY-K2-WR',
                'quantity'     => 18,
            ],
            [
                'name'         => 'Anker Soundcore Select 2 Bluetooth Speaker',
                'category_id'  => $electronics->id,
                'description'  => '16W IPX7 waterproof portable Bluetooth speaker.',
                'is_active'    => true,
                'price'        => 4200.00,
                'sku'          => 'ANK-SC-SEL2',
                'quantity'     => 30,
            ],
            [
                'name'         => 'Baseus USB-C to USB-C 100W Cable',
                'category_id'  => $accessories->id,
                'description'  => 'High-speed fast charging braided 2-meter cable.',
                'is_active'    => true,
                'price'        => 650.00,
                'sku'          => 'BAS-USBC-100W',
                'quantity'     => 150,
            ],
            [
                'name'         => 'Leather Formal Men Belt',
                'category_id'  => $fashion->id,
                'description'  => 'Genuine leather formal brown belt with classic metal buckle.',
                'is_active'    => true,
                'price'        => 1100.00,
                'sku'          => 'BELT-LEA-BRN',
                'quantity'     => 60,
            ],
        ];

        // 3. Loop through & Insert Data
        foreach ($productsData as $data) {
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name'        => $data['name'],
                'slug'        => Str::slug($data['name']) . '-' . Str::random(4),
                'description' => $data['description'],
                'is_active'   => $data['is_active'],
            ]);

            if (isset($data['variants'])) {
                // Product with Variants
                foreach ($data['variants'] as $vData) {
                    $variant = $product->variants()->create([
                        'sku'              => $vData['sku'],
                        'price'            => $vData['price'],
                        'compare_at_price' => $vData['compare_at_price'] ?? null,
                        'cost_price'       => $vData['cost_price'] ?? null,
                        'is_active'        => true,
                    ]);

                    // Inventory
                    $variant->inventory()->create([
                        'quantity'            => $vData['quantity'],
                        'low_stock_threshold' => 5,
                    ]);

                    // Sync Attribute Values to Variant
                    if (!empty($vData['attributes'])) {
                        $variant->attributeValues()->sync($vData['attributes']);
                    }
                }
            } else {
                // Simple Product (Default Variant + Inventory)
                $defaultVariant = $product->variants()->create([
                    'sku'       => $data['sku'],
                    'price'     => $data['price'],
                    'is_active' => true,
                ]);

                $defaultVariant->inventory()->create([
                    'quantity'            => $data['quantity'],
                    'low_stock_threshold' => 5,
                ]);
            }
        }
    }
}