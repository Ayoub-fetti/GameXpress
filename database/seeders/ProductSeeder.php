<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{

    public function run(): void
    {
        $products = [
            [
                'name' => 'FIFA 2025',
                'slug' => Str::slug('fifa2025'),
                'price' => 19.99,
                'stock' => 100,
                'status' => 'available',
                'category_id' => 1,
            ],
            [
                'name' => 'PES 2025',
                'slug' => Str::slug('pes2025'),
                'price' => 29.99,
                'stock' => 50,
                'status' => 'available',
                'category_id' => 1,
            ],
            [
                'name' => 'GTA V ',
                'slug' => Str::slug('gta5'),
                'price' => 9.99,
                'stock' => 200,
                'status' => 'out_of_stock',
                'category_id' => 1,
            ],
        ];

        DB::table('products')->insert($products);
    }
}



