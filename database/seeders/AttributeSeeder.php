<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 10 Attributes and their corresponding Values
        $attributesWithValues = [
            'Color' => ['Red', 'Blue', 'Black', 'White', 'Space Gray', 'Green'],
            'Size' => ['S', 'M', 'L', 'XL', 'XXL'],
            'Storage' => ['128GB', '256GB', '512GB', '1TB'],
            'RAM' => ['8GB', '16GB', '32GB', '64GB'],
            'Material' => ['Cotton', 'Leather', 'Aluminum', 'Polyester', 'Plastic'],
            'Switch Type' => ['Red Switch', 'Blue Switch', 'Brown Switch', 'Linear'],
            'Shoe Size' => ['UK 7', 'UK 8', 'UK 9', 'UK 10', 'UK 11'],
            'Warranty' => ['6 Months', '1 Year', '2 Years', 'Lifetime'],
            'Edition' => ['Standard', 'Pro', 'Limited Edition'],
            'Style' => ['Casual', 'Formal', 'Sports', 'Ergonomic'],
        ];

        foreach ($attributesWithValues as $attributeName => $values) {
            // Create Attribute
            $attribute = Attribute::firstOrCreate(
                ['slug' => Str::slug($attributeName)],
                ['name' => $attributeName]
            );

            // Create Attribute Values for this Attribute
            foreach ($values as $value) {
                AttributeValue::firstOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'slug'         => Str::slug($value),
                    ],
                    [
                        'value'        => $value,
                    ]
                );
            }
        }
    }
}