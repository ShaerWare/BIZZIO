# Bizzio.ru — B2B Business Network

**Bizzio.ru** — B2B-платформа для строительной отрасли. Объединяет компании, проекты, запросы цен (RFQ) и аукционы в единую экосистему.

## Технологический стек

| Компонент | Технология |
|-----------|------------|
| Backend | Laravel 12 (PHP 8.2) |
| Admin Panel | Orchid Platform |
| Database | PostgreSQL 14 |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| PDF | DomPDF (barryvdh/laravel-dompdf) |
| File Storage | Spatie Media Library |
| Activity Log | Spatie Activity Log |
| OAuth | Google + Yandex (кастомный провайдер) |
| AI Chat | Google Gemini Pro (прокси API) |
| RSS | willvincent/feeds |
| Queue | Database driver + Supervisor |
| Deploy | Docker + Caddy (auto-HTTPS) |

## Навигация по Wiki

### Архитектура
- [[Архитектура-проекта]] — Структура каталогов, паттерны, принципы
- [[Схема-базы-данных]] — Все таблицы, связи, индексы
- [[Модели-и-связи]] — Eloquent-модели, fillable, scopes, relationships
- [[Событийная-архитектура]] — Events, Listeners, Notifications

### Бизнес-логика
- [[Компании]] — Регистрация, верификация, модераторы, запросы на вступление
- [[Проекты]] — Создание, участники, роли, комментарии
- [[Запросы-цен-RFQ]] — Создание, подача заявок, скоринг, протоколы
- [[Аукционы]] — Жизненный цикл, торги в реальном времени, анонимизация
- [[Тендеры]] — Единый интерфейс RFQ + Аукционов
- [[Новости-и-RSS]] — Парсинг, фильтрация по ключевым словам

### Техническое
- [[API]] — REST API, AI Chat proxy
- [[Авторизация-и-политики]] — Policies, роли, права доступа
- [[Сервисы]] — Бизнес-логика: скоринг, протоколы, определение победителя
- [[Очереди-и-задачи]] — Jobs, Scheduler, Supervisor
- [[Поиск]] — Quick search, full search, Scout collection driver
- [[Фронтенд]] — Blade-шаблоны, компоненты, Alpine.js
- [[Админ-панель-Orchid]] — Экраны, макеты, управление данными
- [[Деплой-и-инфраструктура]] — Docker, Caddy, Supervisor, мониторинг

## Ключевые метрики проекта

- **41** миграция базы данных
- **17** Eloquent-моделей
- **18** контроллеров
- **5** сервисов бизнес-логики
- **9** доменных событий
- **10** уведомлений (database + mail)
- **4** фоновых задачи (Jobs)
- **2** политики авторизации
- **12** Form Request валидаторов
