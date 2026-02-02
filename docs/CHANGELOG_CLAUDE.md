# Changelog Claude Code

Лог изменений, выполненных Claude Code.

---

## 22.01.2026

### Спринт 9: P2 багфиксы (продолжение)

**Что сделано:**

#### F3 — Сохранение файлов при ошибке валидации
- Создан `TempUploadController` для временной загрузки файлов
- Создан trait `HandlesTempUploads` для контроллеров
- Создан Blade-компонент `<x-file-upload>` с AJAX-загрузкой (Alpine.js)
- Обновлены контроллеры `RfqController` и `AuctionController` для использования trait
- Обновлены формы создания RFQ и аукционов для использования нового компонента
- Файлы сохраняются во временную папку и восстанавливаются при ошибке валидации

#### S1 — Таймзона сервера UTC+3
- Изменена таймзона в `config/app.php`: `UTC` → `Europe/Moscow`
- Добавлена поддержка env-переменной `APP_TIMEZONE`
- Обновлены локали: `locale`, `fallback_locale` → `ru`, `faker_locale` → `ru_RU`
- Обновлён `.env.example` с APP_TIMEZONE

**Созданные файлы:**
- `app/Http/Controllers/TempUploadController.php`
- `app/Traits/HandlesTempUploads.php`
- `resources/views/components/file-upload.blade.php`

**Изменённые файлы:**
- `app/Http/Controllers/RfqController.php` — добавлен HandlesTempUploads trait
- `app/Http/Controllers/AuctionController.php` — добавлен HandlesTempUploads trait
- `resources/views/rfqs/create.blade.php` — использует x-file-upload компонент
- `resources/views/auctions/create.blade.php` — использует x-file-upload компонент
- `routes/web.php` — добавлены маршруты temp-upload
- `config/app.php` — timezone, locale
- `.env.example` — добавлен APP_TIMEZONE

---

### Спринт 9: UX аукционов и тендеров (P2)

**Что сделано:**

#### Аукционы (A4, A5, A7, A8, A10)
1. **A4 — Удалено поле "Шаг аукциона"** из формы создания/редактирования. Теперь диапазон снижения фиксирован: 0.5% — 5% от текущей цены.
2. **A5 — Кнопки быстрого выбора ставки** — добавлены клик-кнопки для снижения цены на 0.5%, 1%, 2%, 3%, 4%, 5% в форме ставки.
3. **A7 — Идентификация участника** — в таблице ставок свои заявки подсвечиваются синим, рядом показывается "(вы)".
4. **A8 — Панель торгов на главном экране** — во время торгов на главном экране отображается форма ставки и таблица последних ставок (не скрыты во вкладке).
5. **A10 — Анонимность для организатора** — названия компаний скрыты от всех (включая организатора) во время торгов, показываются только после закрытия аукциона.

#### Тендеры (T1, T2, T5)
6. **T1 — Копирование ссылки на RFQ** — организатор может скопировать ссылку для приглашения участников (кнопка в боковой панели). Выбор компаний для закрытых процедур уже был реализован.
7. **T2 — Обезличивание заявок в RFQ** — на активном этапе заявки отображаются анонимно ("Участник 1", "Участник 2"), названия компаний показываются только после закрытия. Свои заявки подсвечиваются.
8. **T5 — Формула расчёта балла** — добавлено объяснение формулы:
   - На странице RFQ (раскрывающийся блок в критериях оценки)
   - В форме подачи заявки (подсказка "Как оценивается ваша заявка")
   - В PDF-протоколе (раздел "Формула расчёта итогового балла")

#### Изменённые файлы
- `resources/views/auctions/create.blade.php` — удалено поле step_percent
- `resources/views/auctions/edit.blade.php` — удалено поле step_percent
- `resources/views/auctions/show.blade.php` — панель торгов, кнопки ставок, анонимизация, идентификация участника
- `app/Http/Requests/StoreAuctionRequest.php` — удалена валидация step_percent
- `app/Http/Requests/UpdateAuctionRequest.php` — удалена валидация step_percent
- `app/Http/Controllers/AuctionController.php` — фиксированное значение step_percent=2.5
- `resources/views/rfqs/show.blade.php` — копирование ссылки, анонимизация заявок, формула расчёта
- `resources/views/pdfs/rfq-protocol.blade.php` — добавлена формула расчёта
- `CLAUDE.md` — исправлено название проекта (Bizzio.ru), добавлены npm команды, view:clear

---

## 21.01.2026

### Спринт 9: Feature-тесты + багфиксы (продолжение)

**Что сделано:**

#### Тесты (расширение)
- Создан `tests/Feature/RfqTest.php` — 34 теста для модуля тендеров (CRUD, заявки, scoring, активация, типы, веса критериев)
- Создан `tests/Feature/AuctionTest.php` — 46 тестов для модуля аукционов (CRUD, ставки, статусы, цены, протоколы, scopes)
- **Итого: 185 тестов, 377 assertions** (было 105)

#### Исправленные баги (найдены при написании тестов)
1. **Порядок маршрутов RFQ** — `routes/web.php`: `/rfqs/{rfq}` перехватывал `/rfqs/create` → перенесён после auth-группы (аналогично projects)
2. **Незакрытая транзакция в AuctionController** — `app/Http/Controllers/AuctionController.php:241`: при раннем возврате с ошибкой "уже подали заявку" транзакция не закрывалась → добавлен `DB::rollBack()`
3. **Неверный policy для протокола** — `app/Http/Controllers/AuctionController.php`: `authorize('update', $auction)` требовал status='draft', но протокол генерируется для status='closed' → создан новый метод `generateProtocol` в AuctionPolicy

#### P2 багфиксы
4. **C3 — Скрытие черновиков от посторонних** — `app/Http/Controllers/RfqController.php` и `app/Http/Controllers/AuctionController.php`: добавлен фильтр, черновики видны только модераторам компании-организатора
5. **G2 — Валидация ИНН** — уже была реализована (regex `/^\d{10}(\d{2})?$/`)

#### Изменённые файлы (сессия 2)
- `tests/Feature/RfqTest.php` (создан)
- `tests/Feature/AuctionTest.php` (создан)
- `routes/web.php` — исправлен порядок маршрутов RFQ
- `app/Http/Controllers/AuctionController.php` — исправлена транзакция, изменён authorize, добавлен фильтр черновиков
- `app/Http/Controllers/RfqController.php` — добавлен фильтр черновиков
- `app/Policies/AuctionPolicy.php` — добавлен метод generateProtocol()
- `docs/04_БЭКЛОГ_ФИКСОВ.md` — обновлён статус C3, G2

---

### Спринт 9: Feature-тесты + багфиксы (начало)

**Что сделано:**

#### Тесты
- Создан `tests/Feature/CompanyTest.php` — 28 тестов для модуля компаний (CRUD, верификация, модераторы, запросы на присоединение, фотогалерея)
- Создан `tests/Feature/ProjectTest.php` — 28 тестов для модуля проектов (CRUD, участники, комментарии, права доступа)
- Общее покрытие: **105 тестов, 232 assertions**

#### Исправленные баги (найдены при написании тестов)
1. **Конфликт параметров маршрута** — `routes/web.php`: параметр `{request}` конфликтовал с `Request $request` в контроллере → переименован в `{joinRequest}`
2. **Не загружалась связь company** — `app/Models/CompanyJoinRequest.php`: метод `canReview()` падал с null error → добавлена lazy-загрузка связи
3. **Порядок маршрутов projects** — `routes/web.php`: `/projects/{project:slug}` перехватывал `/projects/create` → перенесён после auth-группы
4. **Неверный метод hasRole()** — `app/Models/Comment.php`: `hasRole('Admin')` → `inRole('admin')` (Orchid использует `inRole`)
5. **Написание бренда в PDF** — `resources/views/pdfs/rfq-protocol.blade.php`, `resources/views/pdf/auction-protocol.blade.php`: "Bizzo.ru" → "Bizzio.ru"

#### Документация
- Обновлён `CLAUDE.md` — добавлены инструкции тестирования PDF на сервере, улучшена секция архитектуры

**Изменённые файлы:**
- `tests/Feature/CompanyTest.php` (создан)
- `tests/Feature/ProjectTest.php` (создан)
- `routes/web.php` — исправлен порядок маршрутов и имена параметров
- `app/Models/CompanyJoinRequest.php` — исправлен метод canReview()
- `app/Models/Comment.php` — исправлен метод canManage()
- `app/Http/Controllers/CompanyJoinRequestController.php` — переименован параметр $request → $joinRequest
- `resources/views/pdfs/rfq-protocol.blade.php` — исправлен footer
- `resources/views/pdf/auction-protocol.blade.php` — исправлен footer
- `CLAUDE.md` — добавлена документация

---

## 13.01.2026

### Создание бэклога фиксов

**Что сделано:**
- Создан структурированный бэклог `docs/04_БЭКЛОГ_ФИКСОВ.md` на основе `03_НЕПРЕДУСМОТРЕННЫЕ_ФИЧИ.md`
- Все 38 задач категоризированы по модулям (Тендеры, Аукционы, Компании, Сервер, Общее, Новости)
- Присвоены приоритеты P1-P4
- Определён рекомендуемый порядок работы (4 фазы)

**Изменённые файлы:**
- `docs/04_БЭКЛОГ_ФИКСОВ.md` (создан)
- `docs/README.md` (обновлён: добавлен спринт 7, ссылка на бэклог, актуализирован статус)
- `docs/CHANGELOG_CLAUDE.md` (создан)

---

### Фаза 1: Критичные баги P1 (4/4 выполнено)

**A1 — Кнопка "Подать заявку" в аукционах:**
- Удалён DEBUG-блок из шаблона
- Добавлены информативные сообщения о причинах недоступности кнопки (черновик, не начался приём, закончился приём, закрытый аукцион, нет компании)

**A9 — Протокол аукциона:**
- Добавлен маршрут `POST /auctions/{auction}/protocol`
- Добавлен метод `generateProtocol()` в AuctionController
- Обновлён шаблон: кнопка генерации/скачивания протокола

**C1 — Кнопки заявок на присоединение:**
- Исправлено имя поля `rejection_reason` → `review_comment` (согласование с контроллером)
- Добавлено безопасное экранирование JavaScript через `Js::from()`

**S2 — Ошибка 413 nginx:**
- Исправлен `fastcgi_pass app:9000` → `127.0.0.1:9000` (nginx и PHP-FPM в одном контейнере)
- Добавлены fastcgi таймауты (300s)
- Увеличены PHP лимиты до 100M (согласовано с nginx)

**Изменённые файлы:**
- `resources/views/auctions/show.blade.php`
- `resources/views/companies/show.blade.php`
- `app/Http/Controllers/AuctionController.php`
- `routes/web.php`
- `docker/nginx.conf`
- `docker/uploads.ini`
- `docs/04_БЭКЛОГ_ФИКСОВ.md`

---

## 16.01.2026

### Исправление конфигурации VK ID и унификация env для prod/local

**Проблема:**
Ошибка на сервере `SocialiteProviders\VKID\Provider doesn't exist` — пакет `socialiteproviders/vkid` версии 5.0.0 требует PHP 8.4, а на сервере PHP 8.2/8.3.

**Решение:**
1. Создан собственный VK ID провайдер `App\Socialite\VKIDProvider` без зависимости от внешнего пакета
2. Удалён пакет `socialiteproviders/vkid` из composer.json
3. Переписан `AppServiceProvider`:
   - Разделение на методы: `configureSocialite()`, `configureHttps()`, `registerPolicies()`, `registerEventListeners()`
   - VK ID провайдер регистрируется через наш собственный класс
4. Обновлён `.env.example` с документированными настройками для local/production

**Изменённые файлы:**
- `app/Socialite/VKIDProvider.php` (создан)
- `app/Providers/AppServiceProvider.php` (переписан)
- `composer.json` (удалён socialiteproviders/vkid)
- `.env.example` (обновлён)

**Для сервера выполнить:**
```bash
cd /path/to/project
git pull
composer update --no-dev
php artisan config:clear && php artisan cache:clear
```

**Настройки .env для production:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bizzio.ru
SESSION_SECURE_COOKIE=true
```

---

## 20.01.2026

### Спринт 8: Поиск + Фото (завершён 100%)

**Что сделано:**

#### 1. Глобальный поиск (Laravel Scout)
- Установлен `laravel/scout` с `database` драйвером
- Добавлен трейт `Searchable` к моделям: User, Company, Project, Rfq, Auction
- Реализованы методы `toSearchableArray()` и `shouldBeSearchable()`
- Создан `SearchController` с методами `index()` и `quick()`
- Создана страница результатов поиска с фильтрами по типам
- Добавлен AJAX-поиск в хедере с dropdown (Alpine.js)

#### 2. Загрузка аватаров
- Добавлен аксессор `getAvatarUrlAttribute()` в модель User
- Добавлены методы `updateAvatar()` и `destroyAvatar()` в ProfileController
- Создан partial `update-avatar-form.blade.php`
- Поддержка OAuth аватаров (VK, Google)

#### 3. Галерея фотографий компаний
- Добавлена MediaCollection 'photos' в модель Company
- Добавлены методы `uploadPhotos()` и `deletePhoto()` в CompanyController
- Добавлена вкладка "Фото" на странице компании
- Сетка фотографий с возможностью удаления (для модераторов)

#### 4. Оптимизация изображений
- Добавлены конверсии: thumb (300x300), medium (800x600), webp
- Настроены оптимизаторы в `config/media-library.php`

#### 5. Feature-тесты
- Создан `SearchTest.php` — 9 тестов, все пройдены

**Созданные файлы:**
- `app/Http/Controllers/SearchController.php`
- `resources/views/search/index.blade.php`
- `resources/views/profile/partials/update-avatar-form.blade.php`
- `config/scout.php`
- `tests/Feature/SearchTest.php`
- `docs/sprints/08.md`

**Изменённые файлы:**
- `app/Models/User.php` — +Searchable, +avatar accessor
- `app/Models/Company.php` — +Searchable, +media conversions
- `app/Models/Project.php` — +Searchable
- `app/Models/Rfq.php` — +Searchable
- `app/Models/Auction.php` — +Searchable
- `app/Http/Controllers/ProfileController.php` — +avatar methods
- `app/Http/Controllers/CompanyController.php` — +photo methods
- `resources/views/layouts/navigation.blade.php` — +search form
- `resources/views/profile/edit.blade.php` — +avatar section
- `resources/views/companies/show.blade.php` — +photos tab
- `routes/web.php` — +search, avatar, photo routes
- `.env` — +SCOUT_DRIVER=database

**Исправленные ошибки:**
- Scout `Builder::count()` не работает с database driver → заменено на `->get()->count()`
- vendor/socialiteproviders/vkid permission denied → полная переустановка vendor через Docker

### Подготовка документации для следующей сессии

**Обновлены файлы:**
- `CLAUDE.md` — обновлён статус (8/10 спринтов, 80% MVP), добавлен `laravel/scout` в пакеты
- `docs/README.md` — добавлен спринт 8, обновлён статус, следующий спринт 9
- `docs/claude/start_message.md` — полностью переписан для спринта 9 (Тестирование + Багфиксы)

---

## 02.02.2026

### Production Deployment — Настройка сервера

**Что сделано:**

1. **Добавлен Laravel Scheduler в Supervisor**
   - Добавлена программа `laravel-scheduler` в `docker/supervisord.conf`
   - Scheduler запускает `php artisan schedule:run` каждые 60 секунд
   - Логи пишутся в `/var/log/scheduler.log`

2. **Документация Production Deployment**
   - Добавлена секция "Production Deployment" в `CLAUDE.md`
   - Команды для деплоя через git pull
   - Все server-команды в формате `docker compose exec app`
   - Описание auto-start сервисов через Supervisor
   - First-time setup инструкции

**Изменённые файлы:**
- `docker/supervisord.conf` — добавлен laravel-scheduler
- `CLAUDE.md` — добавлена секция Production Deployment
