<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            'Информационные технологии',
            'Строительство',
            'Производство',
            'Торговля',
            'Финансы и банковское дело',
            'Транспорт и логистика',
            'Образование',
            'Здравоохранение',
            'Энергетика',
            'Телекоммуникации',
            'Консалтинг',
            'Недвижимость',
            'Сельское хозяйство',
            'Туризм и гостиничный бизнес',
            'Медиа и реклама',
        ];

        foreach ($industries as $industry) {
            Industry::firstOrCreate(
                ['slug' => Str::slug($industry)],
                ['name' => $industry]
            );
        }
    }
}