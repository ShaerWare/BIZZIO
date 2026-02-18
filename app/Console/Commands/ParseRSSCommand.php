<?php

namespace App\Console\Commands;

use App\Jobs\NotifyAdminOnRSSErrorJob;
use App\Models\News;
use App\Models\RSSSource;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Feeds;

class ParseRSSCommand extends Command
{
    protected $signature = 'rss:parse';
    protected $description = 'Парсинг RSS-лент из всех включённых источников';

    public function handle()
    {
        $this->info('🚀 Начинаем парсинг RSS-лент...');

        $sources = RSSSource::enabled()->get();

        if ($sources->isEmpty()) {
            $this->warn('⚠️ Нет включённых RSS-источников');
            return 0;
        }

        $totalParsed = 0;
        $totalErrors = 0;

        foreach ($sources as $source) {
            try {
                // N1: Пропускаем источник, если не прошёл интервал обновления
                $interval = $source->parse_interval ?? 15;
                if ($source->last_parsed_at && $source->last_parsed_at->diffInMinutes(now()) < $interval) {
                    $this->line("⏭️  Пропускаем {$source->name} (интервал {$interval} мин, последний парсинг: {$source->last_parsed_at->format('H:i')})");
                    continue;
                }

                $this->info("📡 Парсим: {$source->name} ({$source->url})");

                $feed = Feeds::make($source->url);
                
                if (!$feed) {
                    throw new \Exception("Не удалось загрузить RSS-ленту");
                }

                $items = $feed->get_items();
                $parsedCount = 0;

                foreach ($items as $item) {
                    try {
                        // Извлечение данных из RSS
                        $link = $item->get_permalink();
                        
                        // Проверка на дубль
                        if (News::where('link', $link)->exists()) {
                            continue;
                        }

                        // Извлечение изображения
                        $image = null;
                        $enclosure = $item->get_enclosure();
                        if ($enclosure && $enclosure->get_thumbnail()) {
                            $image = $enclosure->get_thumbnail();
                        } elseif ($enclosure && $enclosure->get_link()) {
                            $image = $enclosure->get_link();
                        }

                        // Валидация URL изображения
                        if ($image && !filter_var($image, FILTER_VALIDATE_URL)) {
                            $image = null;
                        }

                        // Дата публикации
                        $publishedAt = $item->get_date('Y-m-d H:i:s');
                        if (!$publishedAt) {
                            $publishedAt = now();
                        }

                        // Создание новости
                        News::create([
                            'rss_source_id' => $source->id,
                            'title' => strip_tags($item->get_title()),
                            'description' => strip_tags($item->get_description()),
                            'link' => $link,
                            'image' => $image,
                            'published_at' => $publishedAt,
                        ]);

                        $parsedCount++;
                    } catch (\Exception $e) {
                        Log::error("Ошибка парсинга элемента RSS: {$e->getMessage()}", [
                            'source' => $source->name,
                            'item_link' => $item->get_permalink() ?? 'unknown',
                        ]);
                        continue;
                    }
                }

                // Обновление времени последнего парсинга
                $source->update(['last_parsed_at' => now()]);

                $this->info("✅ {$source->name}: добавлено {$parsedCount} новых новостей");
                $totalParsed += $parsedCount;

            } catch (\Exception $e) {
                $this->error("❌ Ошибка при парсинге {$source->name}: {$e->getMessage()}");
                
                Log::error("RSS Parse Error: {$source->name}", [
                    'url' => $source->url,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Отправка уведомления админу
                NotifyAdminOnRSSErrorJob::dispatch($source, $e->getMessage());
                
                $totalErrors++;
                continue;
            }
        }

        $this->info("🎉 Парсинг завершён. Всего добавлено: {$totalParsed} новостей. Ошибок: {$totalErrors}");
        
        return 0;
    }
}