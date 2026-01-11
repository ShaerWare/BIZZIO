# 🚀 АРТЕФАКТ МИГРАЦИИ: Bizzo.ru → Claude Code (Локальная Разработка)

**Дата создания:** 11.01.2026  
**Версия:** 1.0  
**Проект:** Bizzo.ru — B2B бизнес-сеть  
**Цель:** Полный переход с веб-версии Claude на Claude Code (VSCode extension) для локальной разработки

---

## 📋 СОДЕРЖАНИЕ

1. [Общая информация о проекте](#1-общая-информация-о-проекте)
2. [Технический стек и окружение](#2-технический-стек-и-окружение)
3. [Структура проекта](#3-структура-проекта)
4. [Завершённые спринты (1-6)](#4-завершённые-спринты-1-6)
5. [Текущее состояние кодовой базы](#5-текущее-состояние-кодовой-базы)
6. [Критические файлы и конфигурации](#6-критические-файлы-и-конфигурации)
7. [Настройка локального окружения](#7-настройка-локального-окружения)
8. [Инструкции для Claude Code](#8-инструкции-для-claude-code)
9. [Неочевидные особенности и артефакты](#9-неочевидные-особенности-и-артефакты)
10. [Контекст для продолжения разработки](#10-контекст-для-продолжения-разработки)
11. [Чеклист миграции](#11-чеклист-миграции)

---

## 1. ОБЩАЯ ИНФОРМАЦИЯ О ПРОЕКТЕ

### 1.1. Описание

**Bizzo.ru** — B2B платформа для:
- **Networking:** Поиск партнёров, компаний, проектов
- **Тендеры:** Проведение Запросов котировок (RFQ) и Обратных аукционов
- **Новости:** RSS-агрегация отраслевых новостей с персонализацией
- **Проекты:** Управление проектами с участниками и комментариями
- **Компании:** Корпоративные профили с верификацией

### 1.2. Бизнес-цели

- **Автоматизация:** Замена 3-5 сотрудников (экономия 300к₽/мес)
- **Монетизация:** SaaS-подписки, комиссии с тендеров
- **Финансовая цель:** Выход на 500к₽+/мес стабильного дохода
- **ROI:** Окупаемость до 6 месяцев

### 1.3. Бюджет и сроки

- **Бюджет MVP:** 500 000₽ (10 недель × 50 000₽/неделя)
- **Фактически потрачено (Спринты 1-6):** ~300 000₽
- **Дата начала:** 09.11.2025
- **Текущая дата:** 11.01.2026
- **Статус:** Спринт 6 завершён (60% MVP готово)

---

## 2. ТЕХНИЧЕСКИЙ СТЕК И ОКРУЖЕНИЕ

### 2.1. Основной стек

```yaml
Backend:
  Framework: Laravel 11
  PHP: 8.3+
  Database: PostgreSQL 15+
  Cache/Queue: Redis 7+ (опционально для MVP - используется database)
  Auth: Laravel Passport + Socialite
  Admin Panel: Orchid
  API Docs: Swagger (L5-Swagger)

Frontend:
  Templates: Blade (монолит)
  UI Framework: AdminLTE 3.x
  JS: Vanilla JS + Alpine.js
  CSS: Tailwind CSS (CDN для MVP)

DevOps:
  Hosting: VPS (Ubuntu 24.04)
  Web Server: Apache
  Containerization: Docker Compose (для разработки)
  CI/CD: Git + Webhook
  SSL: Let's Encrypt
  Backup: Ежедневное резервное копирование БД
  Process Manager: Supervisor (queue worker, scheduler)

Services:
  Email: Mailgun / SMTP (настроено в .env)
  Storage: Local / S3 (для файлов)
  RSS Parser: willvincent/feeds
  PDF Generator: barryvdh/laravel-dompdf
```

### 2.2. Локальное окружение разработки

**Docker Compose конфигурация:**

```yaml
# docker-compose.yml (корень проекта)
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: bizzo-app
    container_name: bizzo-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    networks:
      - bizzo-network
    depends_on:
      - db
      - redis
    environment:
      - APP_ENV=local
      - APP_DEBUG=true

  db:
    image: postgres:15-alpine
    container_name: bizzo-db
    restart: unless-stopped
    environment:
      POSTGRES_DB: bizzo
      POSTGRES_USER: bizzo_user
      POSTGRES_PASSWORD: secret
    ports:
      - "5432:5432"
    volumes:
      - db-data:/var/lib/postgresql/data
    networks:
      - bizzo-network

  redis:
    image: redis:7-alpine
    container_name: bizzo-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - bizzo-network

  nginx:
    image: nginx:alpine
    container_name: bizzo-nginx
    restart: unless-stopped
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - bizzo-network
    depends_on:
      - app

networks:
  bizzo-network:
    driver: bridge

volumes:
  db-data:
```

**Dockerfile:**

```dockerfile
FROM php:8.3-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip

# Установка расширений PHP
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www/html

# Копирование проекта
COPY . .

# Установка зависимостей
RUN composer install --no-interaction --optimize-autoloader
RUN npm install

# Права доступа
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
```

### 2.3. Переменные окружения (.env)

```env
APP_NAME="Bizzo"
APP_ENV=local
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=true
APP_TIMEZONE=Europe/Moscow
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=bizzo
DB_USERNAME=bizzo_user
DB_PASSWORD=secret

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@bizzo.ru"
MAIL_FROM_NAME="${APP_NAME}"

# OAuth (опционально, для Спринта 11)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

VK_CLIENT_ID=
VK_CLIENT_SECRET=
VK_REDIRECT_URI=

# Passport
PASSPORT_PRIVATE_KEY=
PASSPORT_PUBLIC_KEY=
```

---

## 3. СТРУКТУРА ПРОЕКТА

### 3.1. Дерево директорий

```
bizzo/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── ParseRSSCommand.php
│   │       ├── CleanOldNewsCommand.php
│   │       └── UpdateAuctionStatuses.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── CompanyController.php
│   │   │   ├── ProjectController.php
│   │   │   ├── RfqController.php
│   │   │   ├── AuctionController.php
│   │   │   ├── NewsController.php
│   │   │   └── UserKeywordController.php
│   │   ├── Requests/
│   │   │   ├── StoreCompanyRequest.php
│   │   │   ├── UpdateCompanyRequest.php
│   │   │   ├── StoreProjectRequest.php
│   │   │   ├── UpdateProjectRequest.php
│   │   │   ├── StoreRfqRequest.php
│   │   │   ├── UpdateRfqRequest.php
│   │   │   ├── StoreBidRequest.php (используется и для RFQ, и для Аукционов)
│   │   │   ├── StoreAuctionRequest.php
│   │   │   ├── UpdateAuctionRequest.php
│   │   │   └── StoreAuctionBidRequest.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Company.php
│   │   ├── Industry.php
│   │   ├── Project.php
│   │   ├── Comment.php
│   │   ├── Rfq.php
│   │   ├── RfqBid.php
│   │   ├── RfqInvitation.php
│   │   ├── Auction.php
│   │   ├── AuctionBid.php
│   │   ├── AuctionInvitation.php
│   │   ├── News.php
│   │   ├── RSSSource.php
│   │   └── UserKeyword.php
│   ├── Orchid/
│   │   ├── Screens/
│   │   │   ├── Company/
│   │   │   │   ├── CompanyListScreen.php
│   │   │   │   └── CompanyEditScreen.php
│   │   │   ├── Project/
│   │   │   │   ├── ProjectListScreen.php
│   │   │   │   └── ProjectEditScreen.php
│   │   │   ├── Rfq/
│   │   │   │   ├── RfqListScreen.php
│   │   │   │   └── RfqEditScreen.php
│   │   │   ├── Auction/
│   │   │   │   ├── AuctionListScreen.php
│   │   │   │   └── AuctionEditScreen.php
│   │   │   ├── RSSSourceListScreen.php
│   │   │   ├── RSSSourceEditScreen.php
│   │   │   └── NewsListScreen.php
│   │   └── PlatformProvider.php
│   ├── Policies/
│   │   ├── CompanyPolicy.php
│   │   ├── ProjectPolicy.php
│   │   ├── RfqPolicy.php
│   │   └── AuctionPolicy.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   └── AuthServiceProvider.php
│   ├── Services/
│   │   ├── RfqScoringService.php
│   │   ├── RfqProtocolService.php
│   │   ├── AuctionProtocolService.php
│   │   └── NewsFilterService.php
│   └── Jobs/
│       ├── CloseRfqJob.php
│       ├── StartAuctionTradingJob.php
│       ├── CloseAuctionJob.php
│       └── NotifyAdminOnRSSErrorJob.php
├── bootstrap/
│   └── app.php (Scheduler настроен здесь)
├── config/
│   ├── app.php
│   ├── database.php
│   ├── passport.php
│   └── l5-swagger.php
├── database/
│   ├── migrations/
│   │   ├── 2024_11_09_000001_create_users_table.php
│   │   ├── 2024_11_09_000002_create_password_reset_tokens_table.php
│   │   ├── 2024_11_10_000003_create_industries_table.php
│   │   ├── 2024_11_10_000004_create_companies_table.php
│   │   ├── 2024_11_10_000005_create_company_user_table.php
│   │   ├── 2024_11_11_000006_create_projects_table.php
│   │   ├── 2024_11_11_000007_create_company_project_table.php
│   │   ├── 2024_11_11_000008_create_project_comments_table.php
│   │   ├── 2024_11_12_000009_create_rfqs_table.php
│   │   ├── 2024_11_12_000010_create_rfq_bids_table.php
│   │   ├── 2024_11_12_000011_create_rfq_invitations_table.php
│   │   ├── 2024_11_13_000012_create_auctions_table.php
│   │   ├── 2024_11_13_000013_create_auction_bids_table.php
│   │   ├── 2024_11_13_000014_create_auction_invitations_table.php
│   │   ├── 2024_11_14_000015_create_news_table.php
│   │   ├── 2024_11_14_000016_create_rss_sources_table.php
│   │   └── 2024_11_14_000017_create_user_keywords_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── OrchidRoleSeeder.php
│       ├── IndustrySeeder.php
│       └── RSSSourceSeeder.php
├── public/
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php
│   │   │   ├── navigation.blade.php
│   │   │   └── guest.blade.php
│   │   ├── companies/
│   │   │   ├── index.blade.php
│   │   │   ├── show.blade.php
│   │   │   ├── create.blade.php
│   │   │   └── edit.blade.php
│   │   ├── projects/
│   │   │   ├── index.blade.php
│   │   │   ├── show.blade.php
│   │   │   ├── create.blade.php
│   │   │   └── edit.blade.php
│   │   ├── rfqs/
│   │   │   ├── index.blade.php
│   │   │   ├── show.blade.php
│   │   │   ├── create.blade.php
│   │   │   ├── edit.blade.php
│   │   │   ├── my-rfqs.blade.php
│   │   │   ├── my-bids.blade.php
│   │   │   └── my-invitations.blade.php
│   │   ├── auctions/
│   │   │   ├── index.blade.php
│   │   │   ├── show.blade.php
│   │   │   ├── create.blade.php
│   │   │   ├── edit.blade.php
│   │   │   ├── my-auctions.blade.php
│   │   │   ├── my-bids.blade.php
│   │   │   └── my-invitations.blade.php
│   │   ├── news/
│   │   │   └── index.blade.php
│   │   ├── profile/
│   │   │   └── keywords.blade.php
│   │   ├── components/
│   │   │   ├── company-card.blade.php
│   │   │   ├── project-card.blade.php
│   │   │   ├── rfq-card.blade.php
│   │   │   ├── auction-card.blade.php
│   │   │   └── comment.blade.php
│   │   └── pdf/
│   │       ├── rfq-protocol.blade.php
│   │       └── auction-protocol.blade.php
│   └── css/
│       └── app.css
├── routes/
│   ├── web.php
│   ├── api.php
│   └── platform.php (Orchid routes)
├── storage/
│   ├── app/
│   │   ├── public/
│   │   │   ├── logos/
│   │   │   ├── avatars/
│   │   │   └── documents/
│   │   └── private/
│   └── logs/
│       ├── laravel.log
│       └── scheduler.log
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env
├── .env.example
├── composer.json
├── package.json
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## 4. ЗАВЕРШЁННЫЕ СПРИНТЫ (1-6)

### Спринт 1: Инфраструктура + Авторизация ✅
**Период:** 09.11.2025 - 15.11.2025  
**Статус:** Завершён на 90%

**Выполнено:**
- ✅ Laravel 11 + PostgreSQL 15 + Docker Compose
- ✅ Orchid Admin Panel
- ✅ Регистрация/авторизация (email + пароль)
- ✅ Email-верификация
- ✅ Система ролей (Orchid Roles): Admin, Moderator, Subscriber
- ✅ OAuth-контроллеры (Google, VK) - ожидают API-ключи

**Отложено на Спринт 11:**
- ⏳ OAuth: Google (API-ключи)
- ⏳ OAuth: VK (API-ключи)
- ⏳ Feature-тесты (будут в Спринте 9)

---

### Спринт 2: Модуль "Компании" ✅
**Период:** 16.11.2025 - 22.11.2025  
**Статус:** Завершён на 95%

**Выполнено:**
- ✅ CRUD компаний (создание, редактирование, удаление)
- ✅ Верификация компаний (ручная, админом)
- ✅ Назначение модераторов компаний
- ✅ Загрузка логотипов и документов (PDF: Устав, ИНН, ОГРН)
- ✅ Каталог компаний с фильтрами (отрасль, верификация, поиск)
- ✅ Профиль компании (вкладки: Описание, Люди, Документы)
- ✅ Spatie Media Library для файлов
- ✅ Orchid Screens (список, редактирование)

**Модели:**
- `Company` (связи: industry, creator, moderators)
- `Industry` (справочник 15 отраслей)

**Особенности:**
- Уникальность ИНН (индекс)
- Мягкое удаление (SoftDeletes)

---

### Спринт 3: Модуль "Проекты" ✅
**Период:** 23.11.2025 - 29.11.2025  
**Статус:** Завершён на 100%

**Выполнено:**
- ✅ CRUD проектов (только для модераторов компаний)
- ✅ Приглашение компаний-участников с указанием роли
- ✅ Система комментариев (древовидная структура)
- ✅ Каталог проектов с фильтрами (статус, заказчик, поиск)
- ✅ Профиль проекта (вкладки: Описание, Участники, Комментарии)
- ✅ Orchid Screens (список, редактирование)

**Модели:**
- `Project` (связи: company, creator, participants, comments)
- `Comment` (вложенные ответы)

**Роли участников:**
- Заказчик, Генподрядчик, Подрядчик, Поставщик, Консультант

**Особенности:**
- Автоматическая генерация slug для SEO
- AJAX для комментариев

---

### Спринт 4: Модуль "Запрос котировок" (RFQ) ✅
**Период:** 30.11.2025 - 06.12.2025  
**Статус:** Завершён на 120% (с дополнительными функциями)

**Выполнено:**
- ✅ Размещение RFQ (открытая/закрытая процедура)
- ✅ Приглашение участников (для закрытых)
- ✅ Подача заявок с критериями (цена, срок, аванс)
- ✅ Автоматический расчёт баллов по формулам (RfqScoringService)
- ✅ Автоматическое определение победителя (CloseRfqJob)
- ✅ Генерация PDF-протокола (RfqProtocolService + barryvdh/laravel-dompdf)
- ✅ Генерация уникального номера (К-ГГММДД-0001)
- ✅ Загрузка технического задания (PDF)
- ✅ Личный кабинет (мои тендеры, заявки, приглашения)
- ✅ Orchid Screens (список, редактирование)

**Дополнительные функции (сверх ТЗ):**
- ✅ Система черновиков с активацией
- ✅ Выбор статуса при создании
- ✅ Умная форма подачи заявки
- ✅ JavaScript-валидация весов критериев
- ✅ Блок "Служба поддержки" с автоссылкой

**Модели:**
- `Rfq` (связи: company, bids, invitations)
- `RfqBid` (связи: rfq, company)
- `RfqInvitation`

**Формулы расчёта:**
```php
// Цена: 100 × (минимальная цена / цена заявки)
// Срок: 100 × (минимальный срок / срок заявки)
// Аванс: 100 - (аванс заявки / макс. аванс) × 100
// Итог: взвешенная сумма по настроенным весам
```

---

### Спринт 5: Модуль "Аукцион" ✅
**Период:** 07.12.2025 - 13.12.2025  
**Статус:** Завершён на 100%

**Выполнено:**
- ✅ Размещение аукциона (открытая/закрытая процедура)
- ✅ Подача первоначальных заявок
- ✅ Проведение торгов (снижение цены по шагам 0.5-5%)
- ✅ Обезличивание участников (4-символьные коды: AB12)
- ✅ Автоматическое определение победителя (через 20 мин после последней ставки)
- ✅ Генерация PDF-протокола (AuctionProtocolService)
- ✅ Генерация уникального номера (А-ГГММДД-0001)
- ✅ Long polling для обновления торгов (каждые 10 сек)
- ✅ Личный кабинет (мои аукционы, заявки, приглашения)
- ✅ Orchid Screens (список, редактирование)

**Дополнительные функции:**
- ✅ Система черновиков с активацией
- ✅ Умная форма подачи заявки/ставки
- ✅ Валидация шага аукциона с подсказкой диапазона

**Модели:**
- `Auction` (связи: company, bids, invitations)
- `AuctionBid` (тип: initial/bid, статус: pending/active/winner)
- `AuctionInvitation`

**Автоматизация:**
- `StartAuctionTradingJob` - запуск торгов
- `CloseAuctionJob` - закрытие через 20 мин
- `UpdateAuctionStatuses` - команда для обновления статусов

**Особенности:**
- Сквозная нумерация с RFQ (общий счётчик тендеров)
- Throttle для long polling (1 запрос/10 сек)

---

### Спринт 6: Модуль "Новости" (RSS-агрегатор) ✅
**Период:** 14.12.2025 - 20.12.2025  
**Статус:** Завершён на 100%

**Выполнено:**
- ✅ Парсинг RSS из 5 источников (каждые 15 минут)
- ✅ Автоматическое удаление новостей старше 1 месяца
- ✅ Фильтрация по ключевым словам (1 слово = OR, несколько = AND)
- ✅ Лента новостей с фильтрами (источник, дата, ключевые слова)
- ✅ Управление ключевыми словами (отдельная страница в профиле)
- ✅ Orchid Screens (список новостей, список RSS-источников)
- ✅ Обработка ошибок парсинга + уведомление админу

**RSS-источники:**
1. CNews
2. TAdviser
3. РБК
4. Коммерсантъ
5. РИА Новости

**Модели:**
- `News` (связи: rssSource)
- `RSSSource` (статус: включён/выключен)
- `UserKeyword` (лимит: 20 слов на пользователя)

**Команды:**
- `rss:parse` - парсинг RSS-лент
- `news:clean-old` - очистка старых новостей

**Scheduler:**
```php
$schedule->command('rss:parse')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

$schedule->command('news:clean-old')
    ->dailyAt('02:00')
    ->withoutOverlapping();
```

**Особенности:**
- FULLTEXT индекс для поиска
- Регистронезависимый поиск (ILIKE для PostgreSQL)
- Валидация URL изображений

---

## 5. ТЕКУЩЕЕ СОСТОЯНИЕ КОДОВОЙ БАЗЫ

### 5.1. Статистика

```
Спринты завершены: 6 из 10 (60% MVP)
Часов разработки: ~300 из 400
Созданных файлов: ~180
Строк кода: ~25000+
Миграций: 17
Моделей: 14
Контроллеров: 6
Services: 4
Jobs: 4
Commands: 3
Orchid Screens: 14
Blade-шаблонов: 40+
```

### 5.2. Текущие модули (по приоритету)

1. ✅ **Компании** - 100%
2. ✅ **Проекты** - 100%
3. ✅ **RFQ** - 100%
4. ✅ **Аукционы** - 100%
5. ✅ **Новости** - 100%
6. ⏳ **Лента активности** - 0% (Спринт 7)
7. ⏳ **Уведомления** - 0% (Спринт 7)
8. ⏳ **Поиск + Фото** - 0% (Спринт 8)

### 5.3. Критические недоработки из ТЗ

Из документа `КРИТИЧНЫЕ ПРОПУСКИ.md`:

**🔴 КРИТИЧНЫЕ (обязательны для MVP):**
1. ⏳ Сообщения (Private Messaging) - Спринт 11
2. ⏳ Блоги / Публикации - Спринт 8
3. ⏳ Раздел "Образование" в профиле - Спринт 2 (доработка)
4. ⏳ Раздел "Опыт работы" в профиле - Спринт 2 (доработка)

**🟡 ВАЖНЫЕ (желательны для MVP):**
5. ⏳ Отзывы и рейтинги - Спринт 11
6. ⏳ LinkedIn OAuth - Спринт 1 (доработка)
7. ⏳ Автозаполнение профиля через OAuth - Спринт 1 (доработка)

---

## 6. КРИТИЧЕСКИЕ ФАЙЛЫ И КОНФИГУРАЦИИ

### 6.1. Конфигурационные файлы

**bootstrap/app.php** (Scheduler):
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Парсинг RSS каждые 15 минут
        $schedule->command('rss:parse')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Очистка старых новостей ежедневно в 02:00
        $schedule->command('news:clean-old')
            ->dailyAt('02:00')
            ->withoutOverlapping();

        // Обновление статусов аукционов каждую минуту
        $schedule->command('auctions:update-statuses')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

**app/Providers/AppServiceProvider.php** (Регистрация Policies):
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Company;
use App\Models\Project;
use App\Models\Rfq;
use App\Models\Auction;
use App\Policies\CompanyPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RfqPolicy;
use App\Policies\AuctionPolicy;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Регистрация Policies
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Rfq::class, RfqPolicy::class);
        Gate::policy(Auction::class, AuctionPolicy::class);
    }
}
```

**app/Orchid/PlatformProvider.php** (Меню и права):
```php
<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    public function menu(): array
    {
        return [
            Menu::make('Пользователи')
                ->icon('user')
                ->route('platform.systems.users')
                ->permission('platform.systems.users'),

            Menu::make('Роли')
                ->icon('lock')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),

            Menu::make('Компании')
                ->icon('briefcase')
                ->route('platform.companies')
                ->permission('platform.systems.companies'),

            Menu::make('Проекты')
                ->icon('rocket')
                ->route('platform.projects')
                ->permission('platform.systems.projects'),

            Menu::make('Запросы котировок')
                ->icon('basket')
                ->route('platform.rfqs')
                ->permission('platform.systems.rfqs'),

            Menu::make('Аукционы')
                ->icon('fire')
                ->route('platform.auctions')
                ->permission('platform.systems.auctions'),

            Menu::make(__('Контент'))
                ->icon('layers')
                ->list([
                    Menu::make('Новости')
                        ->icon('feed')
                        ->route('platform.systems.news')
                        ->permission('platform.systems.news'),

                    Menu::make('RSS-источники')
                        ->icon('globe')
                        ->route('platform.systems.rss-sources')
                        ->permission('platform.systems.rss-sources'),
                ]),
        ];
    }

    public function permissions(): array
    {
        return [
            ItemPermission::group(__('Система'))
                ->addPermission('platform.systems.users', __('Пользователи'))
                ->addPermission('platform.systems.roles', __('Роли'))
                ->addPermission('platform.systems.companies', __('Компании'))
                ->addPermission('platform.systems.projects', __('Проекты'))
                ->addPermission('platform.systems.rfqs', __('Запросы котировок'))
                ->addPermission('platform.systems.auctions', __('Аукционы'))
                ->addPermission('platform.systems.news', __('Новости'))
                ->addPermission('platform.systems.rss-sources', __('RSS-источники')),
        ];
    }
}
```

### 6.2. Маршруты

**routes/web.php** (основные маршруты):
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserKeywordController;

// Главная страница
Route::get('/', function () {
    return view('welcome');
});

// Компании
Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
Route::get('/companies/create', [CompanyController::class, 'create'])->middleware('auth')->name('companies.create');
Route::post('/companies', [CompanyController::class, 'store'])->middleware('auth')->name('companies.store');
Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->middleware('auth')->name('companies.edit');
Route::put('/companies/{company}', [CompanyController::class, 'update'])->middleware('auth')->name('companies.update');
Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->middleware('auth')->name('companies.destroy');

// Проекты
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/create', [ProjectController::class, 'create'])->middleware('auth')->name('projects.create');
Route::post('/projects', [ProjectController::class, 'store'])->middleware('auth')->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->middleware('auth')->name('projects.edit');
Route::put('/projects/{project}', [ProjectController::class, 'update'])->middleware('auth')->name('projects.update');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->middleware('auth')->name('projects.destroy');
Route::post('/projects/{project}/comments', [ProjectController::class, 'storeComment'])->middleware('auth')->name('projects.comments.store');
Route::put('/comments/{comment}', [ProjectController::class, 'updateComment'])->middleware('auth')->name('comments.update');
Route::delete('/comments/{comment}', [ProjectController::class, 'destroyComment'])->middleware('auth')->name('comments.destroy');

// RFQ
Route::get('/rfqs', [RfqController::class, 'index'])->name('rfqs.index');
Route::get('/rfqs/create', [RfqController::class, 'create'])->middleware('auth')->name('rfqs.create');
Route::post('/rfqs', [RfqController::class, 'store'])->middleware('auth')->name('rfqs.store');
Route::get('/rfqs/{rfq}', [RfqController::class, 'show'])->name('rfqs.show');
Route::get('/rfqs/{rfq}/edit', [RfqController::class, 'edit'])->middleware('auth')->name('rfqs.edit');
Route::put('/rfqs/{rfq}', [RfqController::class, 'update'])->middleware('auth')->name('rfqs.update');
Route::delete('/rfqs/{rfq}', [RfqController::class, 'destroy'])->middleware('auth')->name('rfqs.destroy');
Route::post('/rfqs/{rfq}/bids', [RfqController::class, 'storeBid'])->middleware('auth')->name('rfqs.bids.store');
Route::post('/rfqs/{rfq}/activate', [RfqController::class, 'activate'])->middleware('auth')->name('rfqs.activate');
Route::get('/my-rfqs', [RfqController::class, 'myRfqs'])->middleware('auth')->name('rfqs.my');
Route::get('/my-rfq-bids', [RfqController::class, 'myBids'])->middleware('auth')->name('rfqs.my-bids');
Route::get('/my-rfq-invitations', [RfqController::class, 'myInvitations'])->middleware('auth')->name('rfqs.my-invitations');

// Аукционы
Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
Route::get('/auctions/create', [AuctionController::class, 'create'])->middleware('auth')->name('auctions.create');
Route::post('/auctions', [AuctionController::class, 'store'])->middleware('auth')->name('auctions.store');
Route::get('/auctions/{auction}', [AuctionController::class, 'show'])->name('auctions.show');
Route::get('/auctions/{auction}/edit', [AuctionController::class, 'edit'])->middleware('auth')->name('auctions.edit');
Route::put('/auctions/{auction}', [AuctionController::class, 'update'])->middleware('auth')->name('auctions.update');
Route::delete('/auctions/{auction}', [AuctionController::class, 'destroy'])->middleware('auth')->name('auctions.destroy');
Route::post('/auctions/{auction}/bids', [AuctionController::class, 'storeBid'])->middleware('auth')->name('auctions.bids.store');
Route::post('/auctions/{auction}/activate', [AuctionController::class, 'activate'])->middleware('auth')->name('auctions.activate');
Route::get('/auctions/{auction}/state', [AuctionController::class, 'getState'])->middleware('auth')->name('auctions.state');
Route::get('/auctions/my/list', [AuctionController::class, 'myAuctions'])->middleware('auth')->name('auctions.my');
Route::get('/auctions/my/bids', [AuctionController::class, 'myBids'])->middleware('auth')->name('auctions.my-bids');
Route::get('/auctions/my/invitations', [AuctionController::class, 'myInvitations'])->middleware('auth')->name('auctions.my-invitations');

// Новости
Route::get('/news', [NewsController::class, 'index'])->name('news.index');

// Ключевые слова (профиль)
Route::middleware('auth')->group(function () {
    Route::get('/profile/keywords', [UserKeywordController::class, 'index'])->name('profile.keywords.index');
    Route::post('/profile/keywords', [UserKeywordController::class, 'store'])->name('profile.keywords.store');
    Route::delete('/profile/keywords/{keyword}', [UserKeywordController::class, 'destroy'])->name('profile.keywords.destroy');
});

// Breeze (авторизация)
require __DIR__.'/auth.php';
```

### 6.3. Supervisor конфигурация (Production)

**Файл:** `/etc/supervisor/conf.d/bizzo-queue-worker.conf`
```ini
[program:bizzo-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/BIZZIO/artisan queue:work --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=root
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/BIZZIO/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Файл:** `/etc/supervisor/conf.d/bizzo-scheduler.conf`
```ini
[program:bizzo-scheduler]
process_name=%(program_name)s
command=php /var/www/BIZZIO/artisan schedule:work
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/var/www/BIZZIO/storage/logs/scheduler.log
stopwaitsecs=3600
```

**Команды для управления:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bizzo-queue-worker:*
sudo supervisorctl start bizzo-scheduler
sudo supervisorctl status
```

---

## 7. НАСТРОЙКА ЛОКАЛЬНОГО ОКРУЖЕНИЯ

### 7.1. Первоначальная настройка

**Шаг 1: Клонирование репозитория**
```bash
git clone <repository-url> bizzo
cd bizzo
```

**Шаг 2: Копирование .env**
```bash
cp .env.example .env
```

**Шаг 3: Запуск Docker Compose**
```bash
docker compose up -d
```

**Шаг 4: Установка зависимостей**
```bash
docker compose exec app composer install
docker compose exec app npm install
```

**Шаг 5: Генерация ключа приложения**
```bash
docker compose exec app php artisan key:generate
```

**Шаг 6: Миграции и Seeders**
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=OrchidRoleSeeder
docker compose exec app php artisan db:seed --class=IndustrySeeder
docker compose exec app php artisan db:seed --class=RSSSourceSeeder
```

**Шаг 7: Создание symlink для storage**
```bash
docker compose exec app php artisan storage:link
```

**Шаг 8: Создание первого админа (Orchid)**
```bash
docker compose exec app php artisan orchid:admin admin admin@bizzo.ru password
```

**Шаг 9: Установка Passport**
```bash
docker compose exec app php artisan passport:install
```

**Шаг 10: Запуск Scheduler (для разработки)**
```bash
docker compose exec app php artisan schedule:work
```

**Шаг 11: Запуск Queue Worker (в отдельном терминале)**
```bash
docker compose exec app php artisan queue:work
```

### 7.2. Проверка работоспособности

```bash
# Проверка статуса контейнеров
docker compose ps

# Проверка логов приложения
docker compose logs -f app

# Проверка БД
docker compose exec db psql -U bizzo_user -d bizzo -c "SELECT * FROM users LIMIT 1;"

# Проверка миграций
docker compose exec app php artisan migrate:status

# Проверка маршрутов
docker compose exec app php artisan route:list
```

**Доступ к приложению:**
- Frontend: http://localhost
- Orchid Admin: http://localhost/admin (admin@bizzo.ru / password)

---

## 8. ИНСТРУКЦИИ ДЛЯ CLAUDE CODE

### 8.1. Контекст для первого запуска

При первом запуске Claude Code в проекте Bizzo.ru, предоставь ему следующий контекст:

```markdown
# КОНТЕКСТ ПРОЕКТА BIZZO.RU

Привет! Ты — мой цифровой партнёр по разработке проекта Bizzo.ru.

## Краткая справка:
- **Проект:** B2B бизнес-сеть (Laravel 11 + PostgreSQL + Docker)
- **Спринты завершены:** 6 из 10 (60% MVP готово)
- **Текущий этап:** Спринт 7 — Лента активности + Уведомления
- **Часов разработки:** ~300 из 400

## Завершённые модули:
1. ✅ Компании (CRUD, верификация, модераторы)
2. ✅ Проекты (CRUD, участники, комментарии)
3. ✅ RFQ (размещение, заявки, автоопределение победителя, PDF-протокол)
4. ✅ Аукционы (торги, long polling, обезличивание, PDF-протокол)
5. ✅ Новости (RSS-парсинг, фильтрация по ключевым словам)

## Важные артефакты:
- Системный промпт: `/mnt/project/СИСТЕМНЫЙ_ПРОМПТ.md`
- Артефакт Спринта 6: `/mnt/project/АРТЕФАКТЫ_СПРИНТА_6`
- Критичные пропуски ТЗ: `/mnt/project/КРИТИЧНЫЕ_ПРОПУСКИ.md`

## Ключевые принципы:
- **Все команды через Docker:** `docker compose exec app php artisan ...`
- **PostgreSQL:** Используй `ILIKE` вместо `LIKE`
- **Blade-шаблоны:** `@extends('layouts.app')` (не `<x-app-layout>`)
- **Типизация:** Всегда `declare(strict_types=1);` + полная типизация
- **Безопасность:** FormRequest для всех форм, Policy для всех моделей

## Текущие задачи:
- Спринт 7: Лента активности (spatie/laravel-activitylog)
- Спринт 7: Email + DB-уведомления (bell-icon в хедере)
- Доработка: Образование и Опыт работы в профиле пользователя

## Команды для быстрого старта:
- `/help` — список всех команд
- `/tz` — создать ТЗ для новой задачи
- `/sprints` — разбить задачу на спринты
- `/nocode` — стратегический анализ без кода
- `/audit` — критический аудит решения
- `/pr` — сгенерировать Pull Request workflow

Используй файлы в `/mnt/project/` как справочник. Всегда перечитывай контекст перед началом работы.

Готов к работе? 🚀
```

### 8.2. Рекомендуемая структура запросов

**Для новых задач:**
```
/tz Реализовать систему Email-уведомлений для RFQ и Аукционов
```

**Для модификации кода:**
```
Нужно добавить поле "Образование" в профиль пользователя.

Пожалуйста, сначала запроси текущее содержимое:
- app/Models/User.php
- database/migrations/..._create_users_table.php
- resources/views/profile/edit.blade.php
```

**Для критического аудита:**
```
/audit

Оцени текущую реализацию Long Polling в аукционах:
- app/Http/Controllers/AuctionController.php (метод getState)
- resources/views/auctions/show.blade.php (секция JavaScript)

Есть ли более эффективные решения?
```

### 8.3. Частые команды для Claude Code

```bash
# Проверка синтаксиса PHP
docker compose exec app php -l app/Models/User.php

# Tinker (интерактивная консоль Laravel)
docker compose exec app php artisan tinker

# Очистка всех кешей
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# Создание новой миграции
docker compose exec app php artisan make:migration create_table_name

# Создание модели с миграцией и контроллером
docker compose exec app php artisan make:model ModelName -mc

# Создание FormRequest
docker compose exec app php artisan make:request StoreModelNameRequest

# Создание Policy
docker compose exec app php artisan make:policy ModelNamePolicy --model=ModelName

# Создание Orchid Screen
docker compose exec app php artisan orchid:screen ModelNameListScreen
docker compose exec app php artisan orchid:screen ModelNameEditScreen

# Создание Service
mkdir -p app/Services && touch app/Services/MyService.php

# Создание Job
docker compose exec app php artisan make:job MyJob

# Создание Command
docker compose exec app php artisan make:command MyCommand

# Проверка маршрутов
docker compose exec app php artisan route:list

# Проверка статуса миграций
docker compose exec app php artisan migrate:status

# Запуск тестов
docker compose exec app php artisan test

# Генерация Swagger документации
docker compose exec app php artisan l5-swagger:generate
```

---

## 9. НЕОЧЕВИДНЫЕ ОСОБЕННОСТИ И АРТЕФАКТЫ

### 9.1. Артефакт Спринта 6 (Автоматизация статусов аукционов)

**Файл:** `/mnt/project/СПРИНТ_6_аукционы`

**Ключевые моменты:**
- Автоматический переход `active → trading` при истечении срока приёма заявок
- Автоматический переход `trading → closed` через 20 минут после последней ставки
- Генерация анонимных кодов для участников при начале торгов
- Определение победителя (минимальная цена)
- Команда `auctions:update-statuses` запускается каждую минуту через Scheduler

**Проблемы и решения:**
1. **Duplicate key violation:** Решено добавлением `withTrashed()` при генерации номеров
2. **PostgreSQL Ambiguous Column:** Решено явным указанием `companies.id` в `pluck()`
3. **Права доступа к storage:** Решено установкой `chown -R root:root` (Docker использует root)
4. **Scheduler не может запускать Jobs напрямую:** Решено использованием `command()` вместо `job()`

### 9.2. Особенности работы с PostgreSQL

**Регистронезависимый поиск:**
```php
// ❌ MySQL (не работает в PostgreSQL)
$query->where('title', 'LIKE', "%{$keyword}%");

// ✅ PostgreSQL
$query->where('title', 'ILIKE', "%{$keyword}%");
```

**Явное указание имени таблицы для нестандартных моделей:**
```php
class RSSSource extends Model
{
    protected $table = 'rss_sources'; // Без этого Laravel угадывает как r_s_s_sources
}
```

**FULLTEXT индексы:**
```php
// В миграции
$table->fullText(['title', 'description']);

// В модели
public function scopeSearchByKeywords($query, array $keywords)
{
    foreach ($keywords as $keyword) {
        $query->where(function ($q) use ($keyword) {
            $q->where('title', 'ILIKE', "%{$keyword}%")
              ->orWhere('description', 'ILIKE', "%{$keyword}%");
        });
    }
    return $query;
}
```

### 9.3. Blade-шаблоны: Стандарты

**❌ НЕПРАВИЛЬНО:**
```blade
<x-app-layout>
    <x-slot name="header">
        <h2>Заголовок</h2>
    </x-slot>
    <!-- Контент -->
</x-app-layout>
```

**✅ ПРАВИЛЬНО:**
```blade
@extends('layouts.app')

@section('title', 'Заголовок страницы')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Контент -->
        </div>
    </div>
@endsection
```

**Экранирование данных:**
```blade
<!-- Для обычного текста -->
<p>{{ $news->description }}</p>

<!-- Для текста с переносами строк -->
<p>{!! nl2br(e($news->description)) !!}</p>

<!-- Для доверенного HTML -->
<div class="prose">{!! $trustedHtml !!}</div>
```

### 9.4. Scheduler: Регистрация задач

**В Laravel 11+ нет `app/Console/Kernel.php`, используй `bootstrap/app.php`:**

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function (Schedule $schedule) {
        // Парсинг RSS каждые 15 минут
        $schedule->command('rss:parse')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Обязательные методы:
        // ->withoutOverlapping() — не запускать, если предыдущая задача ещё выполняется
        // ->runInBackground() — не блокировать другие задачи
    })
    ->create();
```

**Запуск Scheduler в разработке:**
```bash
docker compose exec app php artisan schedule:work
```

**Настройка Cron на production:**
```bash
* * * * * cd /var/www/BIZZIO && php artisan schedule:run >> /dev/null 2>&1
```

### 9.5. Формулы расчёта баллов RFQ

**Файл:** `app/Services/RfqScoringService.php`

```php
// Цена (чем меньше цена, тем выше балл)
$priceScore = 100 * ($minPrice / $bid->price);

// Срок (чем меньше срок, тем выше балл)
$deadlineScore = 100 * ($minDeadline / $bid->deadline);

// Аванс (чем меньше аванс, тем выше балл - инвертированная шкала)
$advanceScore = 100 - (($bid->advance_percent / $maxAdvance) * 100);

// Итоговый балл (взвешенная сумма)
$totalScore = 
    ($priceScore * $rfq->price_weight) +
    ($deadlineScore * $rfq->deadline_weight) +
    ($advanceScore * $rfq->advance_weight);
```

**Пример:**
- Вес цены: 50%
- Вес срока: 30%
- Вес аванса: 20%

Заявка с минимальной ценой, минимальным сроком и минимальным авансом получит 100 баллов.

---

## 10. КОНТЕКСТ ДЛЯ ПРОДОЛЖЕНИЯ РАЗРАБОТКИ

### 10.1. Следующие спринты

**Спринт 7: Лента активности + Уведомления (21.12 — 27.12)**

**Цель:** Создать общую ленту активности и систему уведомлений.

**Задачи:**
1. Установить `spatie/laravel-activitylog`
2. Логировать события:
   - Создание компаний
   - Создание проектов
   - Создание тендеров (RFQ + Аукцион)
   - Комментарии в проектах
3. Blade-шаблон: Лента активности (Dashboard)
4. Пагинация ленты активности
5. DB-уведомления (bell-icon в хедере)
6. Email-уведомления (Mailgun/SMTP)
7. Триггеры уведомлений:
   - Приглашение в проект
   - Приглашение в тендер
   - Новый комментарий
   - Окончание приёма заявок
   - Определение победителя

**Оценка:** 40 часов

---

**Спринт 8: Поиск + Фото (28.12 — 03.01)**

**Цель:** Глобальный поиск и галерея фото.

**Задачи:**
1. Установить Laravel Scout (или использовать LIKE-поиск для MVP)
2. Индексация: User, Company, Project, RFQ, Auction
3. Глобальный поиск (в хедере)
4. Страница результатов поиска (с фильтрами)
5. Загрузка фото пользователей (аватары)
6. Загрузка фото компаний
7. Галерея фото
8. Оптимизация изображений (WebP, lazy loading)

**Оценка:** 40 часов

---

**Спринт 9: Тестирование + Багфиксы (04.01 — 10.01)**

**Цель:** Комплексное тестирование и исправление багов.

**Задачи:**
1. Feature-тесты: Регистрация (email, Google, VK)
2. Feature-тесты: Компании (создание, верификация)
3. Feature-тесты: Проекты (создание, приглашения, комментарии)
4. Feature-тесты: RFQ (размещение, заявки, победитель)
5. Feature-тесты: Аукцион (размещение, торги, победитель)
6. Feature-тесты: RSS-парсинг + фильтрация
7. Feature-тесты: Уведомления (email + DB)
8. Feature-тесты: Поиск
9. Ручное тестирование (чек-лист из ТЗ)
10. Проверка адаптивности (320px — 1920px)
11. Оптимизация запросов (N+1, eager loading)
12. Проверка производительности (GTMetrix, ≤2 сек)
13. Исправление критичных багов

**Оценка:** 40 часов

---

**Спринт 10: Деплой + Документация (11.01 — 17.01)**

**Цель:** Деплой на production и полная документация.

**Задачи:**
1. Деплой на production (VPS)
2. Настройка SSL (Let's Encrypt)
3. Настройка cron-задач (RSS, уведомления, закрытие тендеров)
4. Настройка резервного копирования БД
5. Настройка логирования (Laravel Log + Sentry)
6. README.md (установка, конфигурация, запуск)
7. Инструкция для админа (верификация, модераторы, модерация)
8. API-документация (Swagger)
9. FAQ для пользователей
10. Презентация для заказчика
11. Обучение заказчика (демо + Q&A)
12. Передача доступов (Orchid, хостинг, Git, email)
13. Финальная приёмка (чек-лист с заказчиком)
14. Создание бэкапа production-БД
15. Подписание акта выполненных работ

**Оценка:** 40 часов

---

### 10.2. Критичные доработки из "Непредусмотренные в ТЗ фичи"

**Документ:** `/mnt/project/НЕПРЕДУСМОТРЕННЫЕ_В_ТЗ_ФИЧИ`

**🔴 КРИТИЧНЫЕ (для RFQ и Аукционов):**

1. **Приглашения в открытых процедурах:**
   - В открытой процедуре (RFQ) также должна быть возможность приглашать компании
   - Варианты отправки: 1) выбор из списка пользователей Bizzo, 2) копирование ссылки

2. **Обезличивание заявок в RFQ:**
   - Заявки в RFQ должны быть обезличены в процессе проведения процедуры
   - Раскрытие участников только после определения победителя

3. **Кнопка "Подать заявку" на видном месте:**
   - Продублировать кнопку "Подать заявку" в блоке "Написать в поддержку"
   - После нажатия — провалиться в заполнение заявки
   - Блок "Написать в поддержку" перенести вниз страницы

4. **Формула расчёта баллов:**
   - Добавить информацию о формуле расчёта баллов:
     - При создании RFQ
     - При подаче заявки
     - В протоколе подведения итогов
     - В разделе "Правила проведения тендеров" (создать раздел)

5. **Объединение меню "Тендеры":**
   - Объединить RFQ и Аукционы в общее меню "Тендеры"
   - В каждой карточке указывать вид процедуры (RFQ/Аукцион)

6. **Скрытие черновиков:**
   - Черновики должны видеть только владельцы и админ

---

**🟡 ВАЖНЫЕ (для Аукционов):**

7. **Статусы аукционов:**
   - Статус "Приём заявок" должен устанавливаться только в период подачи заявок
   - Исправить автоматическое определение статусов

8. **Упрощение установки времени:**
   - "Начало приёма заявок" → по умолчанию равно времени публикации
   - "Дата и время начала торгов" → по умолчанию равно окончанию приёма заявок + 1 минута

9. **Примечание UTC+3:**
   - Ко всем полям времени добавить примечание "UTC+3"

10. **Удобство выбора времени:**
    - Исправить шаг прокрутки времени в браузерах

11. **Зона нажатия кнопки "Выберите файл":**
    - Ограничить зону нажатия только самой кнопкой

12. **Сохранение файла при ошибке:**
    - При ошибке валидации файл должен оставаться прикреплённым

---

**🟢 ЖЕЛАТЕЛЬНЫЕ (общие):**

13. **Регистрация VK OAuth**
14. **Корректировка времени сервера на UTC+3**
15. **Поле ИНН: 10-12 символов**
16. **Убрать подтверждения браузера при отправке запросов**
17. **Ошибка 413 при загрузке PDF к компании** (увеличить лимит в nginx/php.ini)
18. **Настройка VPN на сервере**
19. **Меню: в мобильной версии часть меню не видно**
20. **Настройка таймаута обновления новостей в админке**

---

### 10.3. Технический долг

**На данный момент:**
- ⏳ OAuth (Google, VK) — ожидают API-ключи
- ⏳ Feature-тесты (будут в Спринте 9)
- ⏳ Redis для кэша/очередей (сейчас используется `database`)
- ⏳ Meilisearch для поиска (сейчас используется LIKE-поиск)
- ⏳ S3 для хранения файлов (сейчас используется local)
- ⏳ WebSocket для чатов (отложено на 2-й этап)
- ⏳ Интеграция с API ФНС (автоверификация компаний) - 2-й этап
- ⏳ Платёжный шлюз (подписки) - 2-й этап
- ⏳ Мобильное приложение (React Native) - 2-й этап

---

## 11. ЧЕКЛИСТ МИГРАЦИИ

### 11.1. Подготовка

- [ ] Прочитать этот артефакт полностью
- [ ] Изучить системный промпт (`/mnt/project/СИСТЕМНЫЙ_ПРОМПТ.md`)
- [ ] Изучить артефакты Спринта 6 (`/mnt/project/АРТЕФАКТЫ_СПРИНТА_6`, `/mnt/project/СПРИНТ_6_аукционы`)
- [ ] Изучить критичные пропуски (`/mnt/project/КРИТИЧНЫЕ_ПРОПУСКИ.md`)
- [ ] Изучить непредусмотренные фичи (`/mnt/project/НЕПРЕДУСМОТРЕННЫЕ_В_ТЗ_ФИЧИ`)

### 11.2. Настройка окружения

- [ ] Установить Docker и Docker Compose
- [ ] Клонировать репозиторий
- [ ] Скопировать `.env.example` в `.env`
- [ ] Запустить `docker compose up -d`
- [ ] Установить зависимости (`composer install`, `npm install`)
- [ ] Сгенерировать ключ приложения (`php artisan key:generate`)
- [ ] Запустить миграции (`php artisan migrate`)
- [ ] Запустить seeders (Orchid, Industries, RSSSource)
- [ ] Создать symlink для storage (`php artisan storage:link`)
- [ ] Создать первого админа (`php artisan orchid:admin`)
- [ ] Установить Passport (`php artisan passport:install`)

### 11.3. Проверка работоспособности

- [ ] Открыть http://localhost (главная страница)
- [ ] Открыть http://localhost/admin (Orchid админка)
- [ ] Зарегистрировать тестового пользователя
- [ ] Создать тестовую компанию
- [ ] Проверить загрузку файлов (логотип, документы)
- [ ] Создать тестовый проект
- [ ] Создать тестовый RFQ
- [ ] Создать тестовый аукцион
- [ ] Проверить парсинг RSS (`php artisan rss:parse`)
- [ ] Проверить Scheduler (`php artisan schedule:work`)
- [ ] Проверить Queue Worker (`php artisan queue:work`)

### 11.4. Интеграция с Claude Code

- [ ] Установить Claude Code (VSCode extension)
- [ ] Открыть проект в VSCode
- [ ] Предоставить контекст (раздел 8.1)
- [ ] Проверить доступ к файлам в `/mnt/project/`
- [ ] Протестировать команду `/help`
- [ ] Протестировать команду `/tz` на простой задаче
- [ ] Протестировать команду `/pr` на готовом коммите
- [ ] Протестировать команду `/nocode` на концептуальной задаче

### 11.5. Первая задача в Claude Code

**Рекомендуемая первая задача:** Добавить поля "Образование" и "Опыт работы" в профиль пользователя.

**Команда для Claude Code:**
```
/tz Добавить в профиль пользователя два раздела:
1. Образование (учебное заведение, период, специализация)
2. Опыт работы (компания, должность, период, описание)

Пожалуйста, сначала запроси:
- app/Models/User.php
- database/migrations/..._create_users_table.php
- resources/views/profile/edit.blade.php

Затем предложи миграции для новых таблиц (education, experience).
```

---

## 📊 ИТОГОВАЯ СТАТИСТИКА МИГРАЦИИ

```
Общий объём проделанной работы:
├── Спринтов завершено: 6 из 10 (60%)
├── Часов разработки: ~300 из 400
├── Стоимость работ: ~300 000₽ из 500 000₽
├── Созданных файлов: ~180
├── Строк кода: ~25 000+
├── Миграций: 17
├── Моделей: 14
├── Контроллеров: 6
├── Services: 4
├── Jobs: 4
├── Commands: 3
├── Orchid Screens: 14
├── Blade-шаблонов: 40+
├── Policies: 4
├── FormRequests: 10+
└── Тестов: 0 (будут в Спринте 9)

Технический долг:
├── OAuth (Google, VK): API-ключи
├── Feature-тесты: Спринт 9
├── Redis для производительности: опционально
├── Meilisearch для поиска: опционально
└── S3 для файлов: опционально

Критичные доработки из ТЗ:
├── Образование в профиле: простая задача
├── Опыт работы в профиле: простая задача
├── Блоги/Публикации: Спринт 8
├── Сообщения: Спринт 11
├── Отзывы и рейтинги: Спринт 11
└── LinkedIn OAuth: Спринт 11

Непредусмотренные фичи:
├── Обезличивание заявок в RFQ: средняя задача
├── Приглашения в открытых RFQ: простая задача
├── Формула расчёта баллов (документация): простая задача
├── Объединение меню "Тендеры": простая задача
└── Скрытие черновиков: простая задача
```

---

## 🎓 ЗАКЛЮЧЕНИЕ

Этот артефакт содержит **весь необходимый контекст** для успешной миграции проекта Bizzo.ru из веб-версии Claude в Claude Code (VSCode extension).

**Ключевые преимущества локальной разработки:**
1. ✅ Прямой доступ к файловой системе проекта
2. ✅ Возможность запуска команд напрямую через терминал
3. ✅ Интеграция с Git для Pull Requests
4. ✅ Автодополнение кода в VSCode
5. ✅ Встроенная отладка и профилирование
6. ✅ Быстрое переключение между файлами

**Следующие шаги:**
1. Настроить локальное окружение (раздел 7)
2. Проверить работоспособность (раздел 11.3)
3. Интегрировать с Claude Code (раздел 11.4)
4. Начать с первой задачи (раздел 11.5)
5. Продолжить разработку Спринта 7 (раздел 10.1)

**Удачи в разработке! 🚀**

---

**Дата создания:** 11.01.2026  
**Версия:** 1.0  
**Автор:** AI Assistant (Claude)  
**Проект:** Bizzo.ru — B2B бизнес-сеть

---

## 📞 ПОДДЕРЖКА

Если возникнут вопросы или проблемы при миграции:

1. **Перечитай контекст:** Используй команду `/stop` для перечитки всего диалога
2. **Проверь артефакты:** Файлы в `/mnt/project/` содержат детальную информацию
3. **Запроси помощь:** Используй команду `/help` для списка доступных команд
4. **Критический аудит:** Используй команду `/audit` для оценки сложных решений

**Помни:** Ты не один. Вся команда (системный промпт, артефакты, история спринтов) поддерживает тебя на каждом шагу разработки.

**Создавай что-то великое! 💪**
