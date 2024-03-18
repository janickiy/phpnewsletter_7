<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $data_insert['en'][] = ['name' => 'Category 1'];
        $data_insert['en'][] = ['name' => 'Category 2'];
        $data_insert['en'][] = ['name' => 'Category 3'];
        $data_insert['ru'][] = ['name' => 'Категория 1'];
        $data_insert['ru'][] = ['name' => 'Категория 2'];
        $data_insert['ru'][] = ['name' => 'Категория 3'];

        foreach ($data_insert[config('app.locale')] as $row) {
            Category::create(['name' => $row['name']]);
        }
    }
}
