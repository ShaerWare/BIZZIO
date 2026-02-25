# Новости и RSS

Агрегатор новостей из RSS-источников с персонализированной фильтрацией по ключевым словам пользователя.

## Источники (RSSSource)

5 предустановленных источников:
1. **CNews** — cnews.ru/inc/rss/news.xml
2. **TAdviser** — tadviser.ru/xml/tadviser.xml
3. **РБК** — rssexport.rbc.ru/rbcnews/news/30/full.rss
4. **Коммерсантъ** — kommersant.ru/RSS/news.xml
5. **РИА Новости** — ria.ru/export/rss2/index.xml

Каждый источник имеет:
- `enabled` — включён/выключен
- `parse_interval` — интервал парсинга (минуты, по умолчанию 15)
- `last_parsed_at` — время последнего парсинга

## Парсинг (rss:parse)

Artisan-команда: `php artisan rss:parse`

Расписание: каждые 5 минут (withoutOverlapping, background)

Логика:
1. Получает все enabled-источники
2. Для каждого: проверяет, прошёл ли parse_interval с last_parsed_at
3. Парсит RSS-ленту через `willvincent/feeds`
4. Для каждого элемента: создаёт запись News (если link ещё не существует)
5. При дубликате link — ловит Unique violation и логирует ошибку

## Очистка (news:clean-old)

Artisan-команда: `php artisan news:clean-old`

Расписание: ежедневно в 02:00 UTC

Удаляет старые статьи (soft delete).

## Персонализация (UserKeyword)

Пользователь может настроить до 20 ключевых слов через `/profile/keywords`.

Ключевые слова используются для фильтрации новостной ленты:
- `scopeSearchByKeywords($query, $keywords, $mode)` — поиск в title и description
- Режимы: 'all' (AND — все слова), 'any' (OR — любое слово)

## NewsFilterService

Сервис для фильтрации новостей:

```php
getFilteredNews(array $filters, int $perPage = 20): LengthAwarePaginator
```

Фильтры:
- `source_id` — ID RSS-источника
- `date` — дата публикации
- `apply_keywords` — применить ключевые слова пользователя

## Маршруты

| Маршрут | Описание |
|---------|----------|
| GET /news | Лента новостей с фильтрами |
| GET /profile/keywords | Управление ключевыми словами |
| POST /profile/keywords | Добавить ключевое слово |
| DELETE /profile/keywords/{id} | Удалить ключевое слово |

## Дашборд

Виджет новостей на дашборде показывает 3 последние статьи, отфильтрованные по ключевым словам пользователя (если настроены).

## Ошибки RSS

При ошибке парсинга создаётся `NotifyAdminOnRSSErrorJob`, который логирует ошибку и уведомляет администраторов.
