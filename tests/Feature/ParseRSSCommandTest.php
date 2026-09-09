<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\News;
use App\Models\RSSSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use willvincent\Feeds\Facades\FeedsFacade;

/**
 * #317 Парсер RSS не должен спамить ERROR-ами на уже виденные ссылки.
 *
 * Первопричина: `news:clean-old` удаляет новости МЯГКО (SoftDeletes), строки остаются
 * в таблице, а уникальный индекс `news_link_unique` их видит. Проверка на дубль без
 * `withTrashed()` таких строк не находила — insert падал на 23505, и каждый элемент
 * ленты писал ERROR в лог каждые 5 минут.
 */
class ParseRSSCommandTest extends TestCase
{
    use RefreshDatabase;

    private function source(array $attributes = []): RSSSource
    {
        return RSSSource::create(array_merge([
            'name' => 'Тестовый источник',
            'url' => 'https://example.com/rss',
            'enabled' => true,
            'parse_interval' => 15,
        ], $attributes));
    }

    /**
     * Подменяет ленту набором элементов вида ['link' => ..., 'title' => ...].
     */
    private function fakeFeed(array $items): void
    {
        $feedItems = array_map(fn (array $item) => new FakeFeedItem(
            $item['link'] ?? null,
            $item['title'] ?? 'Заголовок',
        ), $items);

        FeedsFacade::swap(new FakeFeedsFactory(new FakeFeed($feedItems)));
    }

    public function test_soft_deleted_link_is_skipped_without_error(): void
    {
        $source = $this->source();

        $news = News::create([
            'rss_source_id' => $source->id,
            'title' => 'Старая новость',
            'link' => 'https://example.com/a/1',
            'published_at' => now()->subMonths(2),
        ]);
        $news->delete(); // мягкое удаление — ровно то, что делает news:clean-old

        $this->assertTrue($news->fresh()->trashed());

        $this->fakeFeed([['link' => 'https://example.com/a/1']]);

        Log::shouldReceive('error')->never();

        $this->artisan('rss:parse')->assertSuccessful();

        // Дубль не создан, «воскрешения» тоже не произошло.
        $this->assertSame(1, News::withTrashed()->where('link', 'https://example.com/a/1')->count());
        $this->assertSame(0, News::count());
    }

    public function test_item_without_link_is_skipped(): void
    {
        $this->source();
        $this->fakeFeed([['link' => null], ['link' => '']]);

        Log::shouldReceive('error')->never();

        $this->artisan('rss:parse')->assertSuccessful();

        $this->assertSame(0, News::withTrashed()->count());
    }

    public function test_duplicate_link_inside_one_feed_is_stored_once(): void
    {
        $this->source();
        $this->fakeFeed([
            ['link' => 'https://example.com/a/2', 'title' => 'Первая'],
            ['link' => 'https://example.com/a/2', 'title' => 'Она же'],
        ]);

        Log::shouldReceive('error')->never();

        $this->artisan('rss:parse')->assertSuccessful();

        $this->assertSame(1, News::where('link', 'https://example.com/a/2')->count());
    }

    public function test_new_items_are_still_created(): void
    {
        $this->source();
        $this->fakeFeed([
            ['link' => 'https://example.com/a/3', 'title' => 'Свежая'],
            ['link' => 'https://example.com/a/4', 'title' => 'Ещё одна'],
        ]);

        $this->artisan('rss:parse')->assertSuccessful();

        $this->assertSame(2, News::count());
        $this->assertDatabaseHas('news', ['link' => 'https://example.com/a/3', 'title' => 'Свежая']);
    }
}

/** Заглушки SimplePie — команда обращается только к этим методам. */
class FakeFeedsFactory
{
    public function __construct(private FakeFeed $feed) {}

    public function make($feedUrl = [], $limit = 0, $forceFeed = false, $options = null): FakeFeed
    {
        return $this->feed;
    }
}

class FakeFeed
{
    public function __construct(private array $items) {}

    public function get_items(): array
    {
        return $this->items;
    }
}

class FakeFeedItem
{
    public function __construct(private ?string $link, private string $title) {}

    public function get_permalink(): ?string
    {
        return $this->link;
    }

    public function get_title(): string
    {
        return $this->title;
    }

    public function get_description(): string
    {
        return 'Описание';
    }

    public function get_enclosure()
    {
        return null;
    }

    public function get_date($format = null): string
    {
        return now()->format('Y-m-d H:i:s');
    }
}
