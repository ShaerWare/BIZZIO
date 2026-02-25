# API

## REST API

### AI Chat Proxy

`POST /api/v1/chat`

Проксирует запросы к Google Gemini Pro.

**Контроллер:** `App\Http\Controllers\Api\V1\ChatController`

#### Запрос

```json
{
  "message": "Привет, расскажи о строительных тендерах",
  "history": [
    {"role": "user", "text": "Что такое RFQ?"},
    {"role": "bot", "text": "RFQ — это запрос цен..."}
  ]
}
```

| Поле | Тип | Описание |
|------|-----|----------|
| message | string, required, max 2000 | Текст сообщения |
| history | array, optional | История диалога |
| history.*.role | string | 'user' или 'bot' |
| history.*.text | string | Текст сообщения |

#### Ответ (успех)

```json
{
  "reply": "Тендеры в строительной отрасли — это..."
}
```

#### Ответ (ошибка)

```json
{
  "error": "Произошла ошибка при обработке запроса",
  "details": "API key not configured"
}
```

#### Конфигурация
- Требует `.env`: `GEMINI_API_KEY`
- Используется пакет `google-gemini-php/client`
- Метод: `geminiPro()` (модель Gemini Pro)
- Маппинг ролей: user → Role::USER, bot → Role::MODEL

---

## Quick Search API

`GET /search/quick?q={query}`

AJAX-поиск для выпадающего списка в навигации.

**Контроллер:** `App\Http\Controllers\SearchController@quick`

#### Параметры

| Параметр | Тип | Описание |
|----------|-----|----------|
| q | string, min 2 chars | Поисковый запрос |

#### Ответ

Плоский JSON-массив (НЕ обёрнут в `{results: [...]}`):

```json
[
  {
    "type": "company",
    "type_label": "Компания",
    "id": 5,
    "title": "СтройГрупп",
    "subtitle": "ИНН: 1234567890",
    "url": "/companies/stroygrupp"
  },
  {
    "type": "rfq",
    "type_label": "Запрос цен",
    "id": 12,
    "title": "Закупка бетона М400",
    "subtitle": "К-260223-0001",
    "url": "/rfqs/12"
  }
]
```

#### Лимиты результатов по типам
- Company: до 3
- Project: до 3
- RFQ: до 2
- Auction: до 2
- User: до 3

#### Типы (type)
| type | type_label |
|------|------------|
| company | Компания |
| project | Проект |
| rfq | Запрос цен |
| auction | Аукцион |
| user | Пользователь |

---

## Long-Polling: Auction State

`GET /auctions/{id}/state`

Состояние торгов аукциона для real-time обновления.

**Контроллер:** `App\Http\Controllers\AuctionController@getState`

**Middleware:** auth

#### Ответ

```json
{
  "current_price": 950000.00,
  "status": "trading",
  "last_bid_at": "2026-02-23T15:30:00.000000Z",
  "time_remaining": 1200,
  "bids": [
    {
      "anonymous_code": "AB42",
      "company_name": null,
      "price": 950000.00,
      "created_at": "2026-02-23T15:30:00.000000Z"
    }
  ]
}
```

**Примечание:** `company_name` = null для участников (анонимность), имя компании видно только организатору.

---

## Notifications API

### Непрочитанные уведомления

`GET /notifications/unread-count`

```json
{
  "count": 5
}
```

### Список уведомлений

`GET /notifications`

Поддерживает JSON и HTML-ответ (по заголовку Accept).

### Отметить прочитанным

`POST /notifications/{id}/read`

### Отметить все прочитанными

`POST /notifications/read-all`

---

## OAuth Endpoints

| Маршрут | Описание |
|---------|----------|
| GET /auth/google/redirect | Редирект на Google OAuth |
| GET /auth/google/callback | Callback от Google |
| GET /auth/yandex/redirect | Редирект на Yandex OAuth |
| GET /auth/yandex/callback | Callback от Yandex |

### Yandex OAuth

Кастомный провайдер: `app/Socialite/YandexProvider.php`

Регистрация: `AppServiceProvider@configureSocialite()` через `Socialite::extend('yandex', ...)`

Scopes: `login:email`, `login:info`, `login:avatar`

API Endpoints Yandex:
- Auth: `https://oauth.yandex.ru/authorize`
- Token: `https://oauth.yandex.ru/token`
- User Info: `https://login.yandex.ru/info`
