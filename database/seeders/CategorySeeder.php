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
        foreach ($this->categories() as $name) {
            Category::create(['name' => $name]);
        }
    }

    /**
     * Build localized default categories for the selected installer locale.
     *
     * @return array<int, string>
     */
    private function categories(): array
    {
        $data_insert = [
            'en' => ['Category 1', 'Category 2', 'Category 3'],
            'ru' => ['Категория 1', 'Категория 2', 'Категория 3'],
            'es' => ['Categoría 1', 'Categoría 2', 'Categoría 3'],
            'fr' => ['Catégorie 1', 'Catégorie 2', 'Catégorie 3'],
            'de' => ['Kategorie 1', 'Kategorie 2', 'Kategorie 3'],
            'zh-cn' => ['分类 1', '分类 2', '分类 3'],
            'pt' => ['Categoria 1', 'Categoria 2', 'Categoria 3'],
            'ar' => ['الفئة 1', 'الفئة 2', 'الفئة 3'],
            'hi' => ['श्रेणी 1', 'श्रेणी 2', 'श्रेणी 3'],
        ];

        return $data_insert[$this->locale()] ?? $data_insert['en'];
    }

    /**
     * Resolve the locale used for installer seed data.
     *
     * @return string
     */
    private function locale(): string
    {
        return in_array(config('app.locale'), config('app.locales', []), true)
            ? config('app.locale')
            : config('app.fallback_locale', 'en');
    }
}
