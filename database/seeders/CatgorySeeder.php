<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatgorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    $categories = [
        [
            'name' => 'sport',
            'slug' => Str::slug('s025'),
        ],
        [
            'name' => 'action',
            'slug' => Str::slug('a025'),
        ],
        [
            'name' => 'war',
            'slug' => Str::slug('w025'),
        ],
    ];

    DB::table('categories')->insert($categories);
}
}
