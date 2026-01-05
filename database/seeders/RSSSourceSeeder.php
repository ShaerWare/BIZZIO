<?php

namespace Database\Seeders;

use App\Models\RSSSource;
use Illuminate\Database\Seeder;

class RSSSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'name' => 'CNews',
                'url' => 'https://www.cnews.ru/inc/rss/news.xml',
                'enabled' => true,
            ],
            [
                'name' => 'TAdviser',
                'url' => 'https://www.tadviser.ru/xml/tadviser.xml',
                'enabled' => true,
            ],
            [
                'name' => 'РБК',
                'url' => 'https://rssexport.rbc.ru/rbcnews/news/30/full.rss',
                'enabled' => true,
            ],
            [
                'name' => 'Коммерсантъ',
                'url' => 'https://www.kommersant.ru/RSS/news.xml',
                'enabled' => true,
            ],
            [
                'name' => 'РИА Новости',
                'url' => 'https://ria.ru/export/rss2/index.xml',
                'enabled' => true,
            ],
        ];

        foreach ($sources as $source) {
            RSSSource::updateOrCreate(
                ['url' => $source['url']],
                $source
            );
        }

        $this->command->info('✅ Создано 5 RSS-источников');
    }
}