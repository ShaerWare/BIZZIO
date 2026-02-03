# Changelog Claude Code

Лог изменений, выполненных Claude Code.

---

## 03.02.2026

### Замена авторизации VK → Яндекс OAuth

- Установлен пакет `socialiteproviders/yandex` (стандартный OAuth redirect flow)
- `config/services.php`: блоки `vk` и `vkid` заменены на `yandex`
- `.env.example` и `.env`: VK-переменные заменены на `YANDEX_CLIENT_ID`, `YANDEX_CLIENT_SECRET`, `YANDEX_REDIRECT_URI`
- `AppServiceProvider`: регистрация Yandex через `SocialiteWasCalled` event вместо кастомного `VKIDProvider`
- `SocialiteController`: удалён метод `vkIdCallback()`, убрана нормализация `vkid → vk`
- `routes/web.php`: удалён маршрут `POST /auth/vk/callback`
- Blade-шаблоны (login, register, welcome): кнопка VK заменена на «Войти через Яндекс» в emerald-стиле
- Welcome page: удалён VK ID SDK скрипт (~50 строк JS), заменён на простую ссылку
- `public/css/custom.css`: стили `.oauth-btn.vk` заменены на `.oauth-btn.yandex` (цвет #28a745)
- Удалён `app/Socialite/VKIDProvider.php` и директория `app/Socialite/`

**Файлы:** config/services.php, .env.example, AppServiceProvider.php, SocialiteController.php, routes/web.php, login.blade.php, register.blade.php, welcome.blade.php, custom.css. Удалён: VKIDProvider.php

### #31 T8 — Приглашение компаний к участию в RFQ через поиск

- Новый AJAX-маршрут `POST /rfqs/{rfq}/invitations` → `RfqController@storeInvitation` (JSON API)
- Метод `storeInvitation()`: валидация прав, проверка дубликатов, создание `RfqInvitation`, dispatch `TenderInvitationSent` event
- Блок приглашений на show-странице RFQ: Alpine.js поиск + invite (сайдбар, для организатора)
- Вкладка «Приглашения» теперь видна для ВСЕХ типов RFQ (не только закрытых)
- Форма создания RFQ: статический multi-select заменён на поиск с автодополнением (Alpine.js + `/search/quick`)
- Блок приглашений на создании показывается всегда, с разным пояснением для open/closed
- `store()` отправляет приглашения для ЛЮБОГО типа процедуры + dispatch event

**Файлы:** `routes/web.php`, `RfqController.php`, `rfqs/show.blade.php`, `rfqs/create.blade.php`

### Пакет багфиксов и улучшений (issues #32, #33, #35, #38, #40, #41, #42, #43)

- **#35 A2** — Изменён текст «Начальная цена» → «НМЦ» в карточке аукциона (`auction-card.blade.php`)
- **#42 F2** — Исправлена зона нажатия кнопки загрузки файла — скрытый input + styled button (`file-upload.blade.php`)
- **#32 T3+T4** — Перенос кнопки «Подать заявку» в сайдбар и блока поддержки вниз страницы (`rfqs/show.blade.php`)
- **#40 A11+A12+A14** — Авто-заполнение времени начала торгов (end_date + 1 мин), дефолт start_date = now (`auctions/create.blade.php`, `auctions/edit.blade.php`)
- **#41 F1** — Новый компонент `<x-datetime-input>` с раздельными полями дата/время вместо `datetime-local` (4 формы)
- **#33 T9** — Страница «Правила проведения тендеров» (`tenders/rules.blade.php`, маршрут, навигация)
- **#38 A15** — Анонимизация участников для ВСЕХ (включая организатора) на этапе приёма заявок (`auctions/show.blade.php`, `rfqs/show.blade.php`)
- **#38 A16** — Ограничение доступа к протоколу: только организатор и участники (`auctions/show.blade.php`, `rfqs/show.blade.php`)
- **#43 G5+A17** — Выбор валюты RUB/USD/CNY при создании RFQ и аукциона. Миграция, модели, формы, отображение цен, PDF протоколы

**Файлы:** ~20 файлов изменено, 3 создано (datetime-input component, rules page, migration)

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

---

### Ребрендинг цветов — Bizzio Green (#28a745)

**Что сделано:**

1. **Обновлена цветовая палитра Tailwind**
   - Добавлена кастомная палитра `bizzio` в `tailwind.config.js`
   - Основной цвет бренда: `#28a745` (bizzio-500)
   - Полный спектр оттенков 50-950

2. **Обновлена стартовая страница (welcome.blade.php)**
   - Кнопки: градиент `#28a745 → #81b407`
   - Фон блока features: тот же градиент
   - Ховер-эффекты ссылок: emerald цвета
   - Иконки: заливка `#28a745`
   - **Исправлена загрузка CSS** — изменено с внешнего URL (bizzio.ru) на локальный `{{ asset('css/custom.css') }}`

3. **Массовая замена цветов в Blade-шаблонах**
   - Заменено `indigo-*` → `emerald-*` в 41 файле
   - Заменено `blue-*` → `emerald-*` где применимо
   - **Сохранены исключения:** кнопка VK OAuth осталась синей (фирменный цвет VK)

4. **Обновлены компоненты**
   - `primary-button.blade.php` — bg-emerald-600/700/800
   - `nav-link.blade.php` — border-emerald-400/700
   - `responsive-nav-link.blade.php` — emerald-* для активного состояния

**Изменённые файлы:**
- `tailwind.config.js` — добавлена палитра bizzio
- `public/css/custom.css` — зелёные цвета для welcome page
- `resources/views/welcome.blade.php` — локальные assets
- `resources/views/components/primary-button.blade.php`
- `resources/views/components/nav-link.blade.php`
- `resources/views/components/responsive-nav-link.blade.php`
- 41 blade-шаблон в `resources/views/` — замена indigo/blue → emerald

---

### Настройка таймзоны Docker — Europe/Moscow

**Проблема:**
Контейнер показывал UTC вместо московского времени, несмотря на `APP_TIMEZONE=Europe/Moscow` в Laravel.

**Решение:**

1. **docker-compose.yml** — добавлена переменная окружения:
   ```yaml
   app:
     environment:
       - TZ=Europe/Moscow
   db:
     environment:
       TZ: Europe/Moscow
   ```

2. **Dockerfile** — установка tzdata для Alpine Linux:
   ```dockerfile
   ENV TZ=Europe/Moscow
   RUN apk add --no-cache tzdata \
       && cp /usr/share/zoneinfo/$TZ /etc/localtime \
       && echo $TZ > /etc/timezone
   ```

**Причина:** Alpine Linux не имеет tzdata по умолчанию, поэтому переменная окружения `TZ` не работает без установки пакета и копирования файла зоны.

**Изменённые файлы:**
- `docker-compose.yml` — TZ environment для app и db
- `Dockerfile` — установка tzdata, настройка /etc/localtime

---

### Интеграция бэклога заказчика из GitHub Projects

**Что сделано:**

Загружены 16 задач из [GitHub Projects](https://github.com/users/ShaerWare/projects/4/views/1) (issues #24-#44), проверены на соответствие ТЗ и добавлены в бэклог проекта.

#### Новые задачи (добавлены в бэклог):
- **T8** — Приглашение участников к RFQ по поиску пользователей (issue #31)
- **T9** — Раздел "Правила проведения тендеров" (issue #33)
- **A15** — Обезличивание участников аукциона на ВСЕХ этапах (issue #38)
- **A16** — Скрытие протокола аукциона от посторонних (issue #38)
- **A17** — Выбор валюты RUB/USD/CNY для аукционов (issue #43)
- **S5** — Настройка почты SMTP/Mailgun (issue #24)
- **G5** — Выбор валюты RUB/USD/CNY для RFQ (issue #43)
- **G6** — Ребрендинг цветов (#28a745)

#### Повышены приоритеты (P3 → P2):
- **T6, T7** — Объединение меню тендеров (заказчик: !!)
- **A11, A12** — Время аукциона по умолчанию (заказчик: !!)

#### Привязаны GitHub Issues к существующим задачам:
- #31 → T1, T8 | #32 → T3, T4 | #33 → T5, T9 | #34 → T6, T7
- #35 → A2 | #36 → A4, A5 | #37 → A6 | #38 → A7, A10, A15, A16
- #39 → A8 | #40 → A11, A12, A14 | #41 → F1 | #42 → F2, F3
- #43 → A17, G5 | #44 → S3 | #24 → S5 | #25 → G1

#### Соответствие ТЗ:
- 14 из 16 задач соответствуют или не противоречат ТЗ
- 1 задача (#43 — выбор валюты) — расширение ТЗ (ТЗ предусматривает только рубли)
- 1 задача (#44 — VPN) — вне ТЗ, инфраструктурная

**Изменённые файлы:**
- `docs/04_БЭКЛОГ_ФИКСОВ.md` — полное обновление: 44 задачи (было 38), привязка GitHub Issues, таблица соответствия ТЗ, обновлённый план работ

---

## 03.02.2026

### T6+T7 — Объединение меню тендеров (issue #34)

**Что сделано:**

Объединены раздельные меню RFQ и Аукционов в единое меню «Тендеры» с общими страницами каталога, мои тендеры, мои заявки, мои приглашения.

#### 1. TenderController — единый контроллер
- 4 метода: `index()`, `myTenders()`, `myBids()`, `myInvitations()`
- Объединение данных из Rfq + Auction через коллекции + ручная `LengthAwarePaginator`
- Фильтрация: поиск, статус, тип процедуры, вид (RFQ/Аукцион)
- Скрытие черновиков от посторонних (C3)

#### 2. Маршруты
- `GET /tenders` → единый каталог (публичный)
- `GET /my-tenders` → мои тендеры (auth)
- `GET /my-bids-all` → мои заявки (auth)
- `GET /my-invitations-all` → мои приглашения (auth)
- Старые маршруты (`/rfqs`, `/auctions`, `/my-rfqs` и т.д.) сохранены для обратной совместимости

#### 3. Бейджи на карточках
- `rfq-card.blade.php` — бейдж «Запрос котировок» (emerald)
- `auction-card.blade.php` — бейдж «Аукцион» (amber)

#### 4. Навигация
- Десктоп и мобильное меню: «Тендеры и Аукционы» → «Тендеры»
- 11 пунктов → 7 пунктов (объединены найти, мои тендеры, мои заявки, мои приглашения)
- Добавлен `tenders.*` в routeIs() для подсветки активного состояния

#### 5. Новые view-шаблоны
- `tenders/index.blade.php` — единый каталог с фильтрами (поиск, вид, статус, тип)
- `tenders/my-tenders.blade.php` — объединённые мои тендеры (row-layout)
- `tenders/my-bids.blade.php` — объединённые мои заявки (разные карточки для RFQ и Аукционов)
- `tenders/my-invitations.blade.php` — объединённые мои приглашения

**Созданные файлы:**
- `app/Http/Controllers/TenderController.php`
- `resources/views/tenders/index.blade.php`
- `resources/views/tenders/my-tenders.blade.php`
- `resources/views/tenders/my-bids.blade.php`
- `resources/views/tenders/my-invitations.blade.php`

**Изменённые файлы:**
- `routes/web.php` — 4 новых маршрута
- `resources/views/layouts/navigation.blade.php` — объединённое меню (desktop + mobile)
- `resources/views/components/rfq-card.blade.php` — бейдж «Запрос котировок»
- `resources/views/components/auction-card.blade.php` — бейдж «Аукцион»
