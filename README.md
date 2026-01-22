<div align="center">

# Bizzio.ru

### B2B-платформа для строительной индустрии

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-14+-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Tests](https://img.shields.io/badge/Tests-185%20passed-28A745?style=flat-square&logo=github-actions&logoColor=white)](tests/)
[![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)](LICENSE)

**Тендеры • Аукционы • Бизнес-нетворкинг • Новости**

[Демо](https://bizzio.ru) · [Документация](docs/) · [Отчёты](docs/sprints/)

</div>

---

## О проекте

**Bizzio.ru** — B2B-платформа, объединяющая компании строительной отрасли. Проведение тендеров, онлайн-аукционы, поиск партнёров и агрегация отраслевых новостей в единой экосистеме.

### Ключевые возможности

| Модуль | Описание |
|--------|----------|
| **Компании** | Профили с верификацией, документы, модераторы, галерея фото |
| **Проекты** | Совместная работа, приглашения, комментарии |
| **Тендеры (RFQ)** | Запросы котировок с весовыми критериями, автоматический расчёт баллов |
| **Аукционы** | Real-time торги, анонимизация участников, автоопределение победителя |
| **Новости** | RSS-агрегатор с фильтрацией по ключевым словам |
| **Поиск** | Глобальный поиск по всем сущностям (Laravel Scout) |
| **Уведомления** | Email + in-app уведомления о событиях |

---

## Технологии

### Backend
```
Laravel 12        PHP-фреймворк
PostgreSQL 14+    База данных
Redis             Кэш, очереди, сессии (опционально)
```

### Frontend
```
Tailwind CSS      Стилизация
Alpine.js         Интерактивность
Vite              Сборка ассетов
```

### Ключевые пакеты
```
orchid/platform              Админ-панель
spatie/laravel-medialibrary  Файлы и изображения
spatie/laravel-activitylog   Лента активности
laravel/socialite            OAuth (Google, VK)
laravel/scout                Полнотекстовый поиск
barryvdh/laravel-dompdf      PDF-протоколы
```

---

## Быстрый старт

### Требования

- PHP 8.2+
- Composer 2.x
- PostgreSQL 14+
- Node.js 18+

### Установка

```bash
# Клонировать репозиторий
git clone https://github.com/ShaerWare/BIZZIO.git
cd BIZZIO

# Установить зависимости
composer install
npm install

# Настроить окружение
cp .env.example .env
php artisan key:generate

# Создать базу данных и выполнить миграции
php artisan migrate --seed

# Собрать фронтенд
npm run build

# Запустить сервер
php artisan serve
```

### Docker

```bash
# Запустить контейнеры
docker compose up -d

# Выполнить миграции
docker exec my_project_app php artisan migrate --seed

# Приложение доступно на http://localhost:8080
```

---

## Разработка

### Команды

```bash
# Режим разработки (сервер + очереди + логи + Vite)
composer run dev

# Только сервер
php artisan serve

# Обработка очередей
php artisan queue:work

# Тесты
php artisan test                        # Все тесты
php artisan test --filter=CompanyTest   # Конкретный тест

# Код-стайл
./vendor/bin/pint --test    # Проверка
./vendor/bin/pint           # Исправление

# Очистка кэша
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
```

### Структура проекта

```
app/
├── Events/          # Domain events
├── Listeners/       # Event handlers
├── Services/        # Business logic (Scoring, Protocols, etc.)
├── Policies/        # Authorization
├── Socialite/       # Custom OAuth providers
├── Jobs/            # Queue jobs
├── Traits/          # Reusable traits
└── Orchid/          # Admin panel

resources/views/
├── components/      # Blade components
├── layouts/         # Layouts
├── pdfs/            # PDF templates
└── [modules]/       # Module views

docs/
├── sprints/         # Sprint reports
├── claude/          # Claude Code context
└── *.md             # Documentation
```

---

## Тестирование

**Покрытие:** 185 тестов, 377 assertions

| Модуль | Тестов |
|--------|--------|
| Companies | 28 |
| Projects | 28 |
| RFQ (Тендеры) | 34 |
| Auctions | 46 |
| Search | 9 |
| Other | 40 |

```bash
# Запуск всех тестов
php artisan test

# С покрытием
php artisan test --coverage
```

---

## Конфигурация

### Основные переменные (.env)

```env
# Приложение
APP_NAME=Bizzio.ru
APP_URL=https://bizzio.ru
APP_TIMEZONE=Europe/Moscow

# База данных
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=bizzio
DB_USERNAME=your_user
DB_PASSWORD=your_password

# OAuth
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
VK_CLIENT_ID=...
VK_CLIENT_SECRET=...

# Почта
MAIL_MAILER=smtp
MAIL_HOST=go1.unisender.ru
```

---

## Прогресс разработки

**Статус:** 90% MVP (9/10 спринтов)

| Спринт | Название | Статус |
|--------|----------|--------|
| 1 | Инфраструктура + Авторизация | ✅ |
| 2 | Модуль "Компании" | ✅ |
| 3 | Модуль "Проекты" | ✅ |
| 4 | Модуль "Тендеры" (RFQ) | ✅ |
| 5 | Модуль "Аукционы" | ✅ |
| 6 | Модуль "Новости" (RSS) | ✅ |
| 7 | Лента активности + Уведомления | ✅ |
| 8 | Поиск + Фото | ✅ |
| 9 | Тестирование + Багфиксы | ✅ |
| 10 | Финальная полировка | ⏳ |

**Бэклог:** 21/38 задач выполнено (55%)

---

## Документация

- [CLAUDE.md](CLAUDE.md) — Инструкции для Claude Code
- [docs/](docs/) — Полная документация проекта
- [docs/sprints/](docs/sprints/) — Отчёты по спринтам
- [docs/04_БЭКЛОГ_ФИКСОВ.md](docs/04_БЭКЛОГ_ФИКСОВ.md) — Текущий бэклог

---

## Лицензия

Проект распространяется под лицензией [MIT](LICENSE).

---

<div align="center">

**Bizzio.ru** — Соединяя бизнес

[bizzio.ru](https://bizzio.ru) · [GitHub](https://github.com/ShaerWare/BIZZIO)

</div>
