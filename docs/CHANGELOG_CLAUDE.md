# Changelog Claude Code

Лог изменений, выполненных Claude Code.

---

## 2026-04-03 — Доработки #126 (Друзья) + #130 (Поиск в шапке)

### #126 — Модуль Друзья: доработки
- **Таб «Отправленные заявки»** — добавлен 4-й таб с исходящими заявками и кнопкой «Отменить»
- **Поиск друзей** — поле поиска на табе «Друзья» (по имени, email, должности)
- **Уведомления** — `FriendRequestNotification` (новая заявка) и `FriendRequestAcceptedNotification` (принятие заявки), события `FriendRequestSent` / `FriendRequestAccepted`, слушатели зарегистрированы в `AppServiceProvider`

**Файлы:**
- `app/Http/Controllers/FriendshipController.php` — outgoing запрос, поиск, dispatch событий
- `resources/views/friends/index.blade.php` — таб «Отправленные», поле поиска
- `app/Events/FriendRequestSent.php`, `app/Events/FriendRequestAccepted.php` — новые события
- `app/Notifications/FriendRequestNotification.php`, `app/Notifications/FriendRequestAcceptedNotification.php` — уведомления
- `app/Listeners/SendFriendRequestNotification.php`, `app/Listeners/SendFriendRequestAcceptedNotification.php` — слушатели
- `app/Providers/AppServiceProvider.php` — регистрация событий

### #130 — Поле поиска в шапке
- **Десктоп:** расширено поле (`w-64 lg:w-80 xl:w-96`), увеличен padding (`pl-10 pr-8 py-2`), иконка `h-5 w-5` — убрано наслоение placeholder и лупы
- **Мобилка:** добавлено полноценное поле поиска с выпадающими результатами в мобильном меню

**Файлы:**
- `resources/views/layouts/navigation.blade.php` — расширение desktop поиска + добавление mobile поиска

---

## 2026-03-31 — Правки #73, #65 + Модуль Друзья (#126)

### #73 — Проекты: 500 ошибка при открытии + ограничение комментариев
- **Причина бага:** `$user->companies()` не существует в модели User, вызывает `BadMethodCallException`
- Заменено на `$user->moderatedCompanies()` в `ProjectController::show()` и `storeComment()`
- Комментарии отображаются только участникам проекта (логика уже была, но падала из-за бага)
- **Файлы:** `app/Http/Controllers/ProjectController.php`

### #65 — Email-уведомление админу о новых компаниях
- Добавлен `ADMIN_NOTIFICATION_EMAIL=admin@bizzio.ru` в .env и config/app.php
- `SendCompanyCreatedNotification` теперь гарантированно отправляет email на admin@bizzio.ru (даже если нет admin-пользователей в системе)
- Убран хардкод `admin@bizzio.ru` в ProfileController (обратная связь) — используется `config('app.admin_email')`
- **Файлы:** `app/Listeners/SendCompanyCreatedNotification.php`, `config/app.php`, `.env`, `.env.example`, `app/Http/Controllers/ProfileController.php`

### #126 — Модуль Друзья
- **Миграция:** `friendships` таблица (sender_id, receiver_id, status: pending/accepted)
- **Модель:** `Friendship` с отношениями sender/receiver
- **User model:** добавлены методы `friends()`, `friendsCount()`, `friendshipStatusWith()`, `isFriendOf()`, `friendsOfFriends()`, `mutualFriendsCount()`, `pendingFriendRequests()`, `sentFriendRequests()`, `receivedFriendRequests()`
- **Контроллер:** `FriendshipController` — sendRequest, accept, remove, index (с вкладками)
- **Маршруты:** GET /friends, POST /friends/{user}/request, POST /friends/{user}/accept, DELETE /friends/{user}
- **Views:**
  - `friends/index.blade.php` — страница с 3 вкладками (Друзья, Входящие заявки, Рекомендации)
  - `components/friendship-button.blade.php` — универсальная кнопка дружбы (4 состояния)
- **Навигация:** ссылка «Друзья» в desktop и mobile меню с бейджем входящих заявок
- **Профиль пользователя:** кнопка дружбы + счётчик друзей
- **Логика:** авто-принятие при встречных заявках, друзья друзей как рекомендации, счётчик общих друзей
- **Файлы:** `database/migrations/2026_03_31_100000_create_friendships_table.php`, `app/Models/Friendship.php`, `app/Models/User.php`, `app/Http/Controllers/FriendshipController.php`, `app/Http/Controllers/UserProfileController.php`, `routes/web.php`, `resources/views/friends/index.blade.php`, `resources/views/components/friendship-button.blade.php`, `resources/views/users/show.blade.php`, `resources/views/layouts/navigation.blade.php`

---

## 2026-03-27 — Правки после тестов заказчика (batch 3)

**Контекст:** 6 issues из столбца "Правки после тестов" канбана — повторные замечания от @MSverlov (25-26 марта).

### #60 — Dashboard: вёрстка десктоп + welcome page
- **Причина бага:** CSS-классы `order-*` и `lg:order-*` отсутствовали в скомпилированном Tailwind CSS (Vite build), из-за чего на десктопе правая колонка не перемещалась на своё место
- Пересобран Vite build — все grid/order классы теперь в CSS
- Welcome page: добавлена адаптация для 14" ноутбуков (`@media (max-height: 800px)`) — уменьшены паддинги, размеры блоков, `min-height` ограничен `min(600px, 75vh)`
- **Файлы:** `public/css/custom.css`, `public/build/assets/` (rebuild)

### #65 — Индикатор новых компаний в админке
- Неверифицированные компании сортируются первыми
- Бейдж изменён с жёлтого на красный `bg-danger` с иконкой ⚠
- Добавлен счётчик в описании экрана: «Ожидают верификации: N»
- Добавлена кнопка-фильтр «Ожидают верификации» в command bar
- **Файлы:** `app/Orchid/Screens/CompanyListScreen.php`

### #72 — Удалена вкладка «Управление» в компании
- Вкладка дублировала «Люди» — удалена кнопка, контент, модальное окно, JS-функции
- Вкладка «Люди» уже содержит поиск участника, inline смену ролей, удаление
- **Файлы:** `resources/views/companies/show.blade.php`

### #73 — 500-ошибка на страницах проектов (критическая)
- **Причина:** `Project::getRouteKeyName()` возвращал `'id'` вместо `'slug'`, а все маршруты используют `{project:slug}` → Laravel не мог найти проект
- Исправлено: `return 'slug'`
- Ограничение комментариев для не-участников было реализовано ранее, но не работало из-за той же ошибки
- **Файлы:** `app/Models/Project.php`

### #125 — Удаление аукционов и RFQ из админки
- Добавлены кнопки «Удалить» с подтверждением в таблицы `/admin/rfqs` и `/admin/auctions`
- Используется soft delete
- **Файлы:** `app/Orchid/Screens/RfqListScreen.php`, `app/Orchid/Screens/AuctionListScreen.php`

### #69 — Обратная связь
- Ссылка в dropdown-меню и отправка на `admin@bizzio.ru` были реализованы ранее
- Заказчик создал ящик `admin@bizzio.ru` на Beget — нужно проверить SMTP при деплое

---

## 2026-03-25 — Пакет доработок по замечаниям заказчика (10 issues)

**Контекст:** Заказчик (@MSverlov) 23 марта прокомментировал 10+ issues с замечаниями к реализованному функционалу. Все задачи из столбца "Правки после тестов" канбана.

### Исправления:

1. **#110 — Статус приглашения в аукцион** — при подаче initial bid статус приглашения обновляется на `accepted`.
   - `app/Http/Controllers/AuctionController.php` — добавлено обновление `AuctionInvitation.status` в `storeBid()`

2. **#107 — Лишние ссылки Login/Register на welcome** — убраны ссылки "Войти" и "Регистрация" из правого верхнего угла.
   - `resources/views/welcome.blade.php` — удалена секция `@guest` в navbar-user

3. **#111 — Dashboard виджеты: лимит 3 записи** — в каждом блоке оставлено 3 записи (было 5).
   - `app/Http/Controllers/DashboardController.php` — `take(5)` → `take(3)` для myTenders, myInvitations, myBids

4. **#60 — Dashboard мобильная: порядок блоков** — на мобильном блоки закупок теперь перед Лентой.
   - `resources/views/dashboard.blade.php` — добавлены Tailwind `order-*` классы для мобильной перестановки

5. **#73 — Комментарии проектов только для участников** — неавторизованные пользователи больше не могут комментировать.
   - `app/Http/Controllers/ProjectController.php` — добавлена проверка `isMember` / `isCompanyParticipant` в `storeComment()` и `show()`
   - `resources/views/projects/show.blade.php` — форма скрыта для не-участников

6. **#59 — Колокольчик уведомлений на мобильном** — добавлена иконка колокольчика рядом с гамбургером + пункт "Уведомления" в мобильном меню.
   - `resources/views/layouts/navigation.blade.php` — мобильный bell icon + responsive-nav-link

7. **#71 + #72 — Управление участниками компании** — во вкладке "Люди" добавлен inline-поиск пользователей, dropdown смены роли и кнопка удаления (по аналогии с проектами).
   - `resources/views/companies/show.blade.php` — переписана вкладка "Люди" с формой поиска и inline-управлением

8. **#69 — "Обратная связь" в меню аккаунта** — добавлен пункт в desktop dropdown и mobile menu.
   - `resources/views/layouts/navigation.blade.php` — новый `<x-dropdown-link>` и `<x-responsive-nav-link>`
   - `resources/views/profile/partials/feedback-form.blade.php` — добавлен якорь `#feedback`

9. **#38 — Закрытые аукционы только для приглашённых** — закрытые аукционы скрыты из общего каталога для неприглашённых. Имена обезличены.
   - `app/Http/Controllers/AuctionController.php` — фильтр `type=closed` в `index()`
   - `app/Http/Controllers/TenderController.php` — новый метод `applyClosedAuctionFilter()`
   - `resources/views/auctions/show.blade.php` — обезличивание имён участников и приглашённых для не-организаторов

---

## 2026-03-23 — Оптимизация производительности: Tailwind CDN → Vite build

**Проблема:** Страницы «Закупки» и «Новости» грузились очень медленно.

**Корневые причины:**
1. `cdn.tailwindcss.com` — render-blocking JIT-компилятор (~100KB JS), компилировал CSS в браузере на каждой загрузке
2. CDN Alpine.js с нечёткой версией `3.x.x`
3. Виджет ai-sekretar24 загружался синхронно (блокировал рендер)
4. Отсутствовал индекс на `news.deleted_at` (COUNT на 26K записей — 75ms)

**Исправления:**
- `resources/views/layouts/app.blade.php`, `guest.blade.php` — заменены CDN на `@vite(['resources/css/app.css', 'resources/js/app.js'])`
- `public/build/` — предкомпилированные CSS (62KB) + JS (82KB) коммитятся в репо
- Виджет ai-sekretar24 загружается с `async`
- Миграция: индекс `news_deleted_at_index`

---

## 2026-03-23 — Устранение 404 ошибок ресурсов и оптимизация nginx

**Проблема:** Медленная загрузка страниц из-за массы 404 ошибок в консоли браузера. Каждый 404 проходил через PHP/Laravel.

**Корневые причины:**
1. Orchid CompanyEditScreen записывал массив upload-данных в колонку `logo` → URL вида `/storage/["undefined","undefined","26"]`
2. Некоторые проекты имели `/storage/...` в поле `avatar` → двойной `/storage//storage/...`
3. Blade-шаблоны использовали `Storage::url()` вместо accessor `logo_url`
4. Nginx отдавал все 404 через PHP (медленно)
5. Отсутствовал `default-company-logo.svg`

**Исправления:**
- `app/Models/Company.php` — `getLogoUrlAttribute()`: валидация данных, обработка `/storage/` префикса
- `app/Models/Project.php` — `getAvatarUrlAttribute()`: аналогичная защита
- `app/Models/User.php` — `getAvatarUrlAttribute()`: обработка `/storage/` префикса
- `app/Orchid/Screens/CompanyEditScreen.php` — исключены `logo`/`documents`/`moderators` из `update()`
- 6 blade-шаблонов — заменены `Storage::url($company->logo)` на `$company->logo_url`
- `docker/nginx.conf` — статика отдаётся nginx напрямую (кэш 30 дней), gzip сжатие
- `public/images/default-company-logo.svg` — SVG-плейсхолдер
- Почищены мусорные данные в production БД (компании 4, 13; проект 3)

---

## 2026-03-20 — Доработки по канбану (первоочередные задачи)

### Issue #40 — Дефолтные интервалы времени аукциона
- Начало приёма заявок: +10 мин от текущего времени (было: текущее время)
- Окончание приёма заявок: +1 день +10 мин (= начало + 1 день)
- Начало торгов: +1 день +15 мин (= окончание + 5 мин)
- **Файл:** `resources/views/auctions/create.blade.php`

### Issue #57 — PDF протокол: текст наезжает на футер
- Добавлен `padding-bottom: 60px` к body в PDF-шаблонах для резервирования места под фиксированный футер
- **Файлы:** `resources/views/pdf/auction-protocol.blade.php`, `resources/views/pdfs/rfq-protocol.blade.php`

### Issue #111 — Dashboard: блоки не обновляются
- Увеличен лимит выборки с 3 до 10 для всех виджетов (закупки, приглашения, заявки)
- Сортировка изменена с `sortByDesc('number')` на `sortByDesc('created_at')` для корректного порядка
- Отображение увеличено с 3 до 5 элементов
- Для заявок на аукцион фильтр по `type=initial` (без дублей от торговых ставок)
- **Файл:** `app/Http/Controllers/DashboardController.php`

### Issue #119 — Ставку может делать только подавший заявку
- В `show()`: проверка `user_id` на initial bid при торгах (не только company_id)
- В `storeBid()`: серверная проверка — ставку может делать только user, подавший initial bid
- В шаблоне: при торгах выбор компании ограничен компанией из заявки (`$bidCompanies`)
- **Файлы:** `app/Http/Controllers/AuctionController.php`, `resources/views/auctions/show.blade.php`

---

## 2026-03-16 — Доработки по issue #109 (комментарии заказчика)

### Два окна для ставки
При trading отображались два окна подачи ставки (компактная панель + полная форма). Полная форма теперь скрыта при trading (`@if(!$auction->isTrading())`). Файл: `resources/views/auctions/show.blade.php`.

### Форма ставки видна не-участникам
`canBid` при trading разрешал ставки всем модераторам, включая тех, кто не подавал заявку. Теперь при trading `canBid=true` только для компаний с существующей заявкой. Файл: `app/Http/Controllers/AuctionController.php`.

### Ошибка валидации цены при нажатии кнопок процентов
Long-polling обновлял отображение цены, но не обновлял min/max формы и значения кнопок. Добавлено динамическое обновление min/max инпута и onclick-обработчиков кнопок при получении новой цены. Файл: `resources/views/auctions/show.blade.php`.

---

## 2026-03-16 — Критический багфикс: форма подачи заявки в аукционе

### Баг: невозможно подать заявку на аукцион
**Причина:** Форма подачи заявки находилась внутри `content-bids` (вкладка «Ставки»), которая имела `display:none!important` для всех статусов кроме `closed`. При `active` статусе форма была полностью недоступна → заявки не подавались → аукцион отменялся.
**Решение:** Форма вынесена из табов в отдельный блок `#bid-section`, который отображается независимо от вкладок. Кнопка «Подать заявку» теперь скроллит к форме напрямую.
**Файл:** `resources/views/auctions/show.blade.php`

---

## 2026-03-16 — Ревью канбана: закрытие issues + доработки (#107, #109, #111)

### Закрыты по результатам ревью заказчика
Issues #105, #106, #108, #110, #112, #115 — уже были CLOSED на GitHub. Issue #40 — был в Done, но оставался OPEN, закрыт.

### Issue #109 — Скрытие вкладки «Ставки» при отмене аукциона
Вкладка «Ставки» теперь показывается **только** при статусе `closed`, а не при `closed || cancelled`. Файл: `resources/views/auctions/show.blade.php`.

### Issue #107 — Удалён переход на Laravel Breeze /login
GET `/login` теперь редиректит на `/#login-form` (форма входа на главной). Русифицированы плейсхолдеры формы (Email, Пароль, Запомнить, Забыли пароль?). Файлы: `routes/auth.php`, `resources/views/welcome.blade.php`.

### Issue #111 — Статус аукциона на дашборде
Подтверждено: исправление уже было применено ранее (коммит f671892). Закрыто.

---

## 2026-03-14 — Пакет исправлений по обратной связи заказчика (Issues #105–#115)

### Issue #112 — Валюта в виджете ставок на дашборде
Заменён хардкод `₽` на `currency_symbol` из модели. Файлы: `DashboardController.php`, `bids-widget.blade.php`.

### Issue #111 — Статусы тендеров в виджете «Мои тендеры»
Добавлены человекочитаемые метки статусов с цветовыми индикаторами (Приём заявок, Торги, Завершён и т.д.). Файлы: `DashboardController.php`, `tenders-widget.blade.php`.

### Issue #110 — Статусы приглашений в виджете
Добавлены цветные бейджи: тип тендера, статус приглашения, статус тендера. Файлы: `DashboardController.php`, `invitations-widget.blade.php`.

### Issue #109 — Скрытие вкладки ставок в аукционе до завершения
Вкладка «Ставки» показывается только при `closed`/`cancelled`. Файл: `auctions/show.blade.php`.

### Issue #108 — Адаптивность страницы компании
Шапка компании сделана респонсивной (flex-col/row, адаптивные размеры лого и кнопок). Файл: `companies/show.blade.php`.

### Issue #107 — Перевод welcome-страницы + якорь на форму логина
Кнопка «Войти» в шапке скроллит к форме. Английский текст переведён на русский. Файл: `welcome.blade.php`.

### Issue #106 — Редактирование валюты аукциона
Добавлен select валюты + динамическое обновление символа в label цены. Файлы: `auctions/edit.blade.php`, `UpdateAuctionRequest.php`.

### Issue #105 — Адаптивность карточек заявок на вступление
Сделана респонсивная раскладка для карточек join request. Файл: `companies/show.blade.php`.

### Issue #115 — Скрытие результатов завершённых тендеров
- Новая миграция: `is_results_hidden` (boolean) для таблиц `auctions` и `rfqs`
- Поле добавлено в модели, формы создания/редактирования (чекбокс), валидацию запросов
- Логика скрытия в контроллерах: если флаг включён и тендер завершён — результаты (заявки, протокол) видны только организатору и участникам
- Бейдж «Результаты скрыты» на странице тендера
- Файлы: миграция, `Auction.php`, `Rfq.php`, `AuctionController.php`, `RfqController.php`, `Store/UpdateAuction/RfqRequest.php`, все create/edit blade-файлы для аукционов и RFQ, оба show blade-файла

---

## 2026-03-05 — Фикс регистрации: reCAPTCHA блокировала форму

**Проблема:** Регистрация новых пользователей невозможна. Валидация требовала поле `g-recaptcha-response` как `required`, но виджет reCAPTCHA не отображался (ключи `RECAPTCHA_SITE_KEY` / `RECAPTCHA_SECRET_KEY` пустые на сервере). Форма всегда падала с ошибкой валидации.

**Что сделано:** Поле `g-recaptcha-response` теперь обязательно только при настроенной reCAPTCHA (`config('services.recaptcha.site_key')` не пустой). Без ключей регистрация проходит без капчи.

**Изменённые файлы:** `app/Http/Controllers/Auth/RegisteredUserController.php`

---

## 2026-03-05 — Фикс /projects 500 (start_date null)

**Проблема:** Страница `/projects` возвращала 500. Причина — `getFormattedDurationAttribute()` вызывал `->format()` на `null` значении `start_date`.

**Что сделано:** Добавлена проверка `if (! $this->start_date)` с фоллбэком "Сроки не указаны".

**Изменённые файлы:** `app/Models/Project.php`

---

## 2026-03-05 — Фикс 500 при создании компании + русификация валидации

**Проблема 1:** При создании компании возникала ошибка 500. Причина — все Notification-классы отправляли email синхронно (не реализовывали `ShouldQueue`). При сбое SMTP компания создавалась в БД, но пользователь получал 500.

**Проблема 2:** Сообщения валидации при регистрации отображались на английском (напр. "The password field must contain at least one number"), хотя `APP_LOCALE=ru`. Отсутствовали файлы русской локализации.

**Что сделано:**
- Добавлен `implements ShouldQueue` во все 11 notification-классов — теперь email отправляется через очередь
- Создана русская локализация: `lang/ru/validation.php`, `lang/ru/auth.php`, `lang/ru/passwords.php`, `lang/ru/pagination.php`
- Добавлены русские имена атрибутов (пароль, email, ИНН и др.) для понятных сообщений об ошибках

**Изменённые файлы:**
- `app/Notifications/CompanyCreatedNotification.php`
- `app/Notifications/AuctionTradingStartedNotification.php`
- `app/Notifications/NewCommentNotification.php`
- `app/Notifications/ProjectInvitationNotification.php`
- `app/Notifications/JoinRequestNotification.php`
- `app/Notifications/TenderClosedNotification.php`
- `app/Notifications/TenderInvitationNotification.php`
- `app/Notifications/ProjectJoinRequestReviewedNotification.php`
- `app/Notifications/ProjectJoinRequestNotification.php`
- `app/Notifications/ProjectUserInvitedNotification.php`
- `app/Notifications/UserSubscribedNotification.php`
- `lang/ru/validation.php` (новый)
- `lang/ru/auth.php` (новый)
- `lang/ru/passwords.php` (новый)
- `lang/ru/pagination.php` (новый)

---

## 2026-03-04 — Исправление поиска в навигации + деплой

**Проблема:** Быстрый поиск в шапке сайта (dropdown) не показывал результаты.

**Причина:** API `GET /search/quick` возвращает плоский JSON-массив `[...]`, а JavaScript в навигации обращался к `d.results`, ожидая объект `{results: [...]}`. Результат: `d.results = undefined`, поиск всегда пустой.

**Что сделано:**
- `navigation.blade.php` — исправлен парсинг ответа: `Array.isArray(d) ? d : (d.results || [])`
- Выполнен полный деплой на продакшен (git pull + composer install + migrate + cache rebuild + queue:restart + permissions fix)

**Изменённые файлы:**
- `resources/views/layouts/navigation.blade.php`

---

## 2026-03-03 — Исправление 500 на странице каталога компаний

**Проблема:** Страница `/companies` (каталог компаний) возвращала 500 ошибку.

**Причина:** Предыдущий фикс (2026-03-02) не затронул `company-card.blade.php` и `CompanyJoinRequest`. В карточке компании `$company->creator->name` падало при отсутствии создателя (deleted user). Также `$joinRequest->company->name` падало при soft-deleted компании в pending-запросах.

**Что сделано:**
- `company-card.blade.php` — заменено `$company->creator->name` на `$company->creator?->name ?? 'Неизвестный'`
- `companies/index.blade.php` — добавлена `@if($joinRequest->company)` проверка в блоке pending join requests
- `CompanyJoinRequest` — добавлено `->withTrashed()` к связи `company()`

**Изменённые файлы:**
- `resources/views/components/company-card.blade.php`
- `resources/views/companies/index.blade.php`
- `app/Models/CompanyJoinRequest.php`

---

## 2026-03-02 — Исправление 500 ошибок на страницах с удалёнными компаниями

**Проблема:** Страницы `/projects`, `/tenders`, `/rfqs`, `/auctions`, `/my-bids-all`, `/my-invitations-all`, `/my-tenders` возвращали 500 ошибку на production.

**Причина:** При soft-delete компании (`Company`) все связанные модели (Project, Rfq, Auction, *Bid, *Invitation) теряли связь `company()` — Eloquent возвращал `null`. Blade-шаблоны обращались к `$model->company->logo` и падали с ошибкой `Attempt to read property "logo" on null`.

**Что сделано:**
- Добавлено `->withTrashed()` к связи `company()` в 7 моделях: Project, Rfq, Auction, RfqBid, AuctionBid, RfqInvitation, AuctionInvitation
- Добавлены защитные `@if($model->company)` проверки в 3 Blade-шаблонах карточек: project-card, rfq-card, auction-card
- Обновлён CLAUDE.md (добавлены разделы: Posts, Registration, HandlesTempUploads, расширена диаграмма моделей)

**Изменённые файлы:**
- `app/Models/Project.php` — `->withTrashed()` в `company()`
- `app/Models/Rfq.php` — `->withTrashed()` в `company()`
- `app/Models/Auction.php` — `->withTrashed()` в `company()`
- `app/Models/RfqBid.php` — `->withTrashed()` в `company()`
- `app/Models/AuctionBid.php` — `->withTrashed()` в `company()`
- `app/Models/RfqInvitation.php` — `->withTrashed()` в `company()`
- `app/Models/AuctionInvitation.php` — `->withTrashed()` в `company()`
- `resources/views/projects/partials/project-card.blade.php` — null-check
- `resources/views/components/rfq-card.blade.php` — null-check
- `resources/views/components/auction-card.blade.php` — null-check
- `CLAUDE.md` — расширена документация

**Тесты:** Все 237 тестов проходят (521 assertion).

---

## 2026-02-27 — Капча и валидация на регистрацию — Issue #99

**Issue:** #99

**Что сделано:**
- Добавлена Google reCAPTCHA v2 (checkbox) на форму регистрации — без внешних пакетов, интеграция через Google API напрямую
- Серверная валидация reCAPTCHA: HTTP-запрос к `siteverify`, пропуск проверки если `RECAPTCHA_SECRET_KEY` не задан (dev-окружение)
- Усилена валидация полей регистрации: `name` min:2, `email:rfc,dns` (проверка MX-записи), `password` min:8 + буквы + цифры
- Добавлена кнопка OAuth «Войти через VK» на формы `/login` и `/register`
- Добавлена конфигурация VK OAuth в `config/services.php` и `.env.example`
- 7 тестов: регистрация с капчей, без капчи (ошибка), невалидная капча (ошибка), пропуск капчи без секрета, короткое имя (ошибка), слабый пароль (ошибка)

**Изменённые файлы:**
- `config/services.php` — добавлены секции `vkontakte` и `recaptcha`
- `.env.example` — добавлены переменные `VK_*` и `RECAPTCHA_*`
- `app/Http/Controllers/Auth/RegisteredUserController.php` — reCAPTCHA валидация + усиленные правила полей
- `resources/views/layouts/guest.blade.php` — подключён скрипт reCAPTCHA API
- `resources/views/auth/register.blade.php` — виджет reCAPTCHA + кнопка VK
- `resources/views/auth/login.blade.php` — кнопка VK
- `tests/Feature/Auth/RegistrationTest.php` — 7 тестов (было 2)

---

## 2026-02-27 — Модуль подписок (Друзья) — Issue #89

**Issue:** #89

**Что сделано:**
- Создана полиморфная таблица `subscriptions` (subscriber_id, subscribable_type, subscribable_id) — подписка на User и Company одной записью
- Создана модель `Subscription` с `subscriber()` и `subscribable()` связями
- Расширена модель `User`: `subscriptions()`, `subscribers()`, `isSubscribedTo()` методы
- Расширена модель `Company`: `subscribers()` связь
- Создан `SubscriptionController` — подписка/отписка на пользователей и компании, страница «Мои подписки»
- Создан `UserProfileController` — публичная страница профиля пользователя (`/users/{id}`)
- Создан Blade-компонент `subscribe-button` — кнопка подписки/отписки с POST/DELETE формами
- Создано событие `UserSubscribed` + `SendUserSubscribedNotification` listener + `UserSubscribedNotification` (database only)
- Фильтрация ленты на dashboard: посты от подписок + друзья друзей (2-й уровень), активности от прямых подписок
- Пустая лента показывает рекомендации верифицированных компаний
- Кнопка «Подписаться» добавлена на страницу компании (для не-модераторов)
- URL пользователей в поиске исправлен: `#` → `route('users.show', $user)`
- Добавлен case `user_subscribed` в уведомления (иконка + текст)
- 15 тестов (подписка/отписка, self-subscribe guard, идемпотентность, фильтрация ленты, FoF, рекомендации, профиль)

**Новые файлы:**
- `database/migrations/2026_02_27_100000_create_subscriptions_table.php`
- `app/Models/Subscription.php`
- `app/Http/Controllers/SubscriptionController.php`
- `app/Http/Controllers/UserProfileController.php`
- `app/Events/UserSubscribed.php`
- `app/Listeners/SendUserSubscribedNotification.php`
- `app/Notifications/UserSubscribedNotification.php`
- `resources/views/users/show.blade.php`
- `resources/views/subscriptions/index.blade.php`
- `resources/views/components/subscribe-button.blade.php`
- `tests/Feature/SubscriptionTest.php`

**Изменённые файлы:**
- `app/Models/User.php` — добавлены subscriptions(), subscribers(), isSubscribedTo()
- `app/Models/Company.php` — добавлена subscribers() связь
- `app/Http/Controllers/DashboardController.php` — фильтрация ленты по подпискам + рекомендации
- `app/Http/Controllers/SearchController.php` — URL пользователей в поиске
- `app/Providers/AppServiceProvider.php` — регистрация UserSubscribed event
- `routes/web.php` — маршруты подписок и профиля пользователя
- `resources/views/companies/show.blade.php` — кнопка подписки
- `resources/views/partials/dashboard/posts-feed.blade.php` — ссылки на профили, рекомендации
- `resources/views/partials/notification-text.blade.php` — case user_subscribed
- `resources/views/notifications/index.blade.php` — иконка user_subscribed

---

## 2026-02-26 — Обновление CLAUDE.md (PR #100)

**Что сделано:**
- Добавлен отсутствовавший OAuth-провайдер VK (`socialiteproviders/vkontakte`) в секцию OAuth Providers
- Исправлено описание Company route model binding: принимает и числовые ID (для Orchid admin) и slug (для публичных маршрутов)

**Изменённые файлы:** `CLAUDE.md`

---

## 2026-02-26 — Пакет багфиксов: #87, #88, #90, #91, #92, #93, #94

**Issues:** #87, #88, #90, #91, #92, #93, #94

**Что сделано:**
- **#92 (P1):** Кнопка «Подать заявку» в RFQ теперь переключает на вкладку «Заявки» и скроллит к форме. Добавлена аналогичная кнопка в сайдбар аукциона (ранее отсутствовала).
- **#88:** Поиск юзера в компании — исправлены имена полей (`user.name`→`user.title`, `user.email`→`user.subtitle`), z-index dropdown повышен до z-50, grid overflow-visible.
- **#91:** Добавлена JS-валидация файла ТЗ при создании RFQ (показывает ошибку если файл не прикреплён). Кнопка переименована: «Разместить RFQ» → «Разместить». Убран `required` с hidden file input (блокировал submit молча).
- **#93:** Обезличивание приглашённых компаний на вкладке «Приглашения» RFQ — не-организаторы видят «Участник N» вместо названий.
- **#90:** Добавлен блок «Приглашённые компании» с AJAX-поиском на страницу редактирования черновика RFQ. Загрузка invitations в контроллере edit(). Исправлен баг `data.results` → `data` в companyInviter() на show-странице.
- **#87:** Добавлены глобальные padding (`px-3 py-2`) для всех полей ввода через `@layer components` в app.css. Vite assets пересобраны.
- **#94:** Фильтр новостей по ключевым словам изменён с AND на OR (`'all'` → `'any'` в NewsFilterService).

**Изменённые файлы:**
- `resources/views/rfqs/show.blade.php` — кнопка «Подать заявку» (#92), обезличивание приглашений (#93), фикс companyInviter (#90)
- `resources/views/auctions/show.blade.php` — добавлена кнопка «Подать заявку» в сайдбар (#92)
- `resources/views/companies/show.blade.php` — фикс полей поиска user.title/subtitle, z-50, overflow-visible (#88)
- `resources/views/rfqs/create.blade.php` — JS-валидация ТЗ, переименование кнопки (#91)
- `resources/views/rfqs/edit.blade.php` — секция приглашений компаний (#90)
- `resources/views/components/file-upload.blade.php` — убран required с hidden input (#91)
- `resources/css/app.css` — глобальные padding для input/textarea/select (#87)
- `app/Http/Controllers/RfqController.php` — загрузка invitations в edit() (#90)
- `app/Services/NewsFilterService.php` — OR вместо AND (#94)
- `public/build/` — пересобранные assets

---

## 2026-02-25 — Фикс Orchid 404: Company route binding + route cache

**Задача:** Исправить 404 при редактировании/удалении компаний в админке Orchid.

**Причина:** `Route::bind('company', ...)` был в `routes/platform.php` — при `route:cache` binding терялся, и `{company}` резолвился через `getRouteKeyName() = 'slug'` вместо id.

**Что сделано:**
1. Перенесён `Route::bind('company', ...)` из `routes/platform.php` в `AppServiceProvider::registerRouteBindings()`.
2. Удалён неиспользуемый import `App\Models\Company` из `routes/platform.php`.

**Изменённые файлы (2):**
- `app/Providers/AppServiceProvider.php` — добавлен `registerRouteBindings()`
- `routes/platform.php` — удалён `Route::bind('company', ...)`

---

## 2026-02-25 — Wiki проекта: 19 страниц для AI-датасета

**Задача:** Создать Wiki проекта для дальнейшего обучения AI-модели.

**Что сделано:**
Создано 19 wiki-страниц (2500+ строк) в папке `wiki/`:
- Home (навигация), Архитектура проекта, Схема базы данных
- Модели и связи, Событийная архитектура
- Компании, Проекты, Запросы цен (RFQ), Аукционы, Тендеры
- Новости и RSS, API, Авторизация и политики
- Сервисы, Очереди и задачи, Поиск, Фронтенд
- Админ-панель Orchid, Деплой и инфраструктура

**Добавленные файлы (19):**
- `wiki/Home.md` + 18 тематических страниц

---

## 2026-02-25 — Фикс 500 на dashboard: getKey() on array

**Задача:** Исправить 500 Server Error при открытии dashboard авторизованным пользователем.

**Причина ошибки:** `DashboardController` вызывал `Eloquent\Collection->merge()` на результатах `->map()`, которые возвращали массивы вместо моделей. Метод `merge()` у Eloquent Collection пытается вызвать `getKey()` на каждом элементе.

**Что сделано:**
1. Обёрнуты 3 вызова `->map()->merge()` в `collect()` — конвертация из `Eloquent\Collection` в `Support\Collection` перед merge.
2. Запущены pending миграции на проде (`create_project_user_table`, `create_project_join_requests_table`).

**Изменённые файлы (1):**
- `app/Http/Controllers/DashboardController.php` — строки 71, 97, 123

---

## 2026-02-23 — Обновление бэклога: финализация MVP

**Задача:** Обновить `docs/04_БЭКЛОГ_ФИКСОВ.md` — привести к актуальному состоянию после завершения MVP.

**Что сделано:**
1. Обновлена шапка: дата → 23.02.2026, статус → 10/10 спринтов (MVP готов).
2. Таблица статусов: 60/75 → 70/75 (93%).
3. Отмечены как ✅ Готово: T6, T7, PR2, S4, S6, G7, G8, G9, G17.
4. Фазы 3-5 → ✅ ЗАВЕРШЕНА, Фаза 6 → Ожидает.
5. Обновлён раздел «Следующие шаги».
6. Добавлена запись в «Историю изменений».

**Изменённые файлы (1):**
- `docs/04_БЭКЛОГ_ФИКСОВ.md`

---

## 2026-02-23 — #74: Пользователи в проектах: роли, приглашения, запросы

**Задача:** Добавить пользовательское участие в проектах — приглашения, запросы на присоединение, роли (admin/moderator/member), вкладка «Люди» на странице проекта.

**Что сделано:**
1. Создана таблица `project_user` — pivot для пользователей-участников проекта (с ролями, компанией, кто добавил).
2. Создана таблица `project_join_requests` — запросы на присоединение к проекту (по аналогии с company_join_requests).
3. Создана модель `ProjectJoinRequest` — с relations, scopes, canCancel/canReview.
4. Обновлена модель `Project` — members(), joinRequests(), isMember(), hasPendingRequestFrom(), addMember(), removeMember(), getUserRoles().
5. Обновлена модель `User` — projectMemberships() relation.
6. Создан `ProjectMemberController` — invite, update role, remove, join request CRUD, approve/reject.
7. Добавлены 7 маршрутов для участников и запросов.
8. Созданы 3 события: ProjectUserInvited, ProjectJoinRequestCreated, ProjectJoinRequestReviewed.
9. Созданы 3 слушателя и 3 уведомления (database + mail).
10. Зарегистрированы события в AppServiceProvider.
11. Добавлена вкладка «Люди» на странице проекта с формой приглашения (Alpine.js), списком участников, кнопкой запроса, управлением запросами.
12. Обновлён notification-text.blade.php — 3 новых @case блока.
13. Обновлён ProjectController: eager-loading members в show(), автодобавление создателя как admin в store().
14. Написано 22 теста (все проходят).

**Новые файлы (15):**
- `database/migrations/2026_02_23_100000_create_project_user_table.php`
- `database/migrations/2026_02_23_100001_create_project_join_requests_table.php`
- `app/Models/ProjectJoinRequest.php`
- `app/Http/Controllers/ProjectMemberController.php`
- `app/Events/ProjectUserInvited.php`
- `app/Events/ProjectJoinRequestCreated.php`
- `app/Events/ProjectJoinRequestReviewed.php`
- `app/Listeners/SendProjectUserInvitedNotification.php`
- `app/Listeners/SendProjectJoinRequestNotification.php`
- `app/Listeners/SendProjectJoinRequestReviewedNotification.php`
- `app/Notifications/ProjectUserInvitedNotification.php`
- `app/Notifications/ProjectJoinRequestNotification.php`
- `app/Notifications/ProjectJoinRequestReviewedNotification.php`
- `resources/views/projects/partials/members-tab.blade.php`
- `tests/Feature/ProjectMemberTest.php`

**Изменённые файлы (6):**
- `app/Models/Project.php`
- `app/Models/User.php`
- `app/Http/Controllers/ProjectController.php`
- `app/Providers/AppServiceProvider.php`
- `routes/web.php`
- `resources/views/projects/show.blade.php`
- `resources/views/partials/notification-text.blade.php`

---

## 2026-02-21 — #68: Удаление тестовых данных

**Задача:** Безопасное удаление тестовых компаний перед запуском + защита удаления аукционов в админке.

**Что сделано:**
1. Создана artisan-команда `cleanup:test-data` — интерактивное удаление компаний с каскадным soft-delete (RFQ, аукционы, ставки, проекты) и hard-delete pivot-записей (invitations, company_user, company_project, join_requests). Поддержка `--force` для автоматического режима.
2. Добавлена проверка статуса в `AuctionEditScreen::remove()` — удаление только `draft` и `cancelled` (как в RfqEditScreen).
3. Написаны тесты для команды очистки (3 теста).

**Изменённые файлы:**
- `app/Console/Commands/CleanupTestDataCommand.php` — **новый**
- `app/Orchid/Screens/AuctionEditScreen.php` — добавлена проверка статуса + import Alert
- `tests/Feature/CleanupTestDataCommandTest.php` — **новый** (3 теста)

---

## 2026-02-21 — #60: Новая главная страница (dashboard)

**Задача:** Полноценный 3-колоночный dashboard с профилем, новостями, постами, закупками, заявками и приглашениями.

**Новые файлы:**
- `database/migrations/2026_02_21_100000_create_posts_table.php` — миграция таблицы постов
- `app/Models/Post.php` — модель поста (SoftDeletes, InteractsWithMedia, LogsActivity)
- `app/Http/Controllers/PostController.php` — store/destroy для постов
- `resources/views/partials/dashboard/profile-card.blade.php` — карточка профиля
- `resources/views/partials/dashboard/join-requests-widget.blade.php` — виджет заявок на вступление
- `resources/views/partials/dashboard/my-companies-widget.blade.php` — список компаний пользователя
- `resources/views/partials/dashboard/my-projects-widget.blade.php` — список проектов
- `resources/views/partials/dashboard/news-widget.blade.php` — 3 последние новости
- `resources/views/partials/dashboard/post-form.blade.php` — форма создания поста (Alpine.js preview)
- `resources/views/partials/dashboard/posts-feed.blade.php` — лента постов
- `resources/views/partials/dashboard/activity-feed.blade.php` — лента активности + load more
- `resources/views/partials/dashboard/tenders-widget.blade.php` — виджет закупок
- `resources/views/partials/dashboard/invitations-widget.blade.php` — виджет приглашений
- `resources/views/partials/dashboard/bids-widget.blade.php` — виджет заявок

**Изменённые файлы:**
- `app/Models/User.php` — добавлен `companies.slug` в `moderatedCompanies()` select
- `app/Http/Controllers/DashboardController.php` — полностью переписан `index()`: 3 колонки данных
- `resources/views/dashboard.blade.php` — 3-колоночный layout (grid-cols-5: 1+3+1)
- `routes/web.php` — добавлены роуты POST /posts и DELETE /posts/{post}
- `tests/Feature/DashboardTest.php` — обновлены существующие + добавлены тесты постов и view data

**Тесты:** 192 passed (401 assertions) — все тесты проходят.

---

## 2026-02-21 — G7 (#58): Изменение структуры меню

**Задача:** Реструктуризация навигации — «Тендеры» → «Закупки», Dashboard как главная, перенос «Мои запросы» на страницу компаний.

**Изменённые файлы:**
- `resources/views/layouts/navigation.blade.php` — Logo ведёт на dashboard (auth) / welcome (guest); убран пункт «Dashboard» из навбара; dropdown «Тендеры» → «Закупки» с новой структурой (Найти закупку, Мои заявки, Мои приглашения, Мои закупки, Создать запрос цен, Создать аукцион, Правила проведения); убран «Мои запросы на присоединение»; добавлена «Лента активности» в user dropdown; зеркальные изменения в мобильном меню
- `routes/web.php` — `/` для auth-пользователей редиректит на dashboard
- `app/Http/Controllers/CompanyController.php` — `index()` передаёт `$pendingJoinRequests` во view
- `resources/views/companies/index.blade.php` — Amber-блок ожидающих запросов на присоединение сверху страницы
- `app/Http/Controllers/Auth/SocialiteController.php` — OAuth redirect → dashboard вместо /companies

**Тесты:** 185 passed (377 assertions) — все тесты проходят.

---

## 2026-02-17 (сессия 4) — Выполнение бэклога: 7 задач

### A14 — Удалить примечание о торгах
**Статус:** Текст не найден в коде — уже удалён или не добавлялся. Задача закрыта.

### A13 — Примечание UTC+3 ко всем полям времени
**Добавлено:**
- `resources/views/auctions/edit.blade.php` — UTC+3 к полям end_date, trading_start
- `resources/views/rfqs/edit.blade.php` — UTC+3 к полю end_date
- `resources/views/auctions/show.blade.php` — (МСК) к датам приёма заявок и начала торгов
- `resources/views/rfqs/show.blade.php` — (МСК) к датам начала и окончания

### G12 (#61) — Яндекс.Метрика
**Добавлено:** Счётчик Яндекс.Метрика (id=106718528) в `<head>` всех layout'ов:
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/welcome.blade.php`
- Работает только в production (`@production`)

### C6 (#63) — Запросы на присоединение → вкладка «Люди»
**Перенесено:** Блок запросов на присоединение из вкладки «Управление» во вкладку «Люди» на странице компании.
**Добавлено:** Уведомление модераторам компании при получении запроса (email + database):
- `app/Notifications/JoinRequestNotification.php` — новый файл
- `app/Http/Controllers/CompanyJoinRequestController.php` — отправка уведомлений в store()
- `resources/views/partials/notification-text.blade.php` — текст уведомления
- `resources/views/notifications/index.blade.php` — иконка уведомления

### G14 (#69) — Форма обратной связи
**Добавлено:** Форма обратной связи в профиле пользователя:
- `resources/views/profile/partials/feedback-form.blade.php` — новый файл
- `resources/views/profile/edit.blade.php` — подключение формы
- `app/Http/Controllers/ProfileController.php` — метод feedback()
- `routes/web.php` — маршрут POST /profile/feedback
- Отправка на admin@bizzio.ru с reply-to пользователя

### G15 (#70) — PDF-протоколы: логотип, ссылки, нумерация
**Обновлено:** PDF-протоколы аукционов и RFQ:
- `resources/views/pdf/auction-protocol.blade.php` — логотип, ссылка, нумерация страниц
- `resources/views/pdfs/rfq-protocol.blade.php` — логотип, ссылка, нумерация страниц
- Фирменный зелёный (#28a745) в заголовке и футере

### N1 — Настройка таймаута RSS в админке
**Добавлено:** Индивидуальный интервал обновления для каждого RSS-источника:
- Миграция: поле `parse_interval` (по умолчанию 15 мин)
- `app/Models/RSSSource.php` — поле parse_interval
- `app/Orchid/Screens/RSSSourceEditScreen.php` — поле в админке
- `app/Console/Commands/ParseRSSCommand.php` — пропуск источников до истечения интервала
- `bootstrap/app.php` — scheduler: everyFifteenMinutes → everyFiveMinutes

---

## 2026-02-17 — T11 (#79): Переименование «Запрос котировок» → «Запрос цен»

**Изменено:** Замена терминологии по всему проекту (27 файлов):
- 15 Blade-шаблонов, 10 PHP-файлов, 2 файла маршрутов
- Все формы слова: «Запрос/Запросы/Запроса/запрос/запросу котировок» → «цен»

## 2026-02-17 — G16 (#67): Ограничение размера фото до 2 МБ

**Исправлено:** `app/Http/Controllers/CompanyController.php` — `uploadPhotos()`: `max:5120` → `max:2048`

## 2026-02-17 — G13 (#50): Подтверждение соответствия ТЗ

**Добавлено:** Обязательный чекбокс «Настоящим подтверждаю соответствие своего предложения Техническому заданию (ТЗ)» на формах подачи заявок:
- `resources/views/rfqs/show.blade.php` — форма подачи предложения RFQ
- `resources/views/auctions/show.blade.php` — форма подачи заявки на аукцион

## 2026-02-17 — C4+C5 (#72, #71): Управление участниками компании

**C4 (#72):** Добавлена возможность изменения роли и прав модераторов:
- `resources/views/companies/show.blade.php` — кнопка «Изменить» + модальное окно с формой редактирования роли и прав

**C5 (#71):** Выпадающий список пользователей заменён на поиск:
- `resources/views/companies/show.blade.php` — Alpine.js компонент `userSearch()` с динамическим поиском
- `app/Http/Controllers/SearchController.php` — добавлены пользователи в quick search API, API теперь возвращает плоский массив JSON
- `tests/Feature/SearchTest.php` — обновлены тесты под новый формат ответа

## 2026-02-17 — G11 (#62): Поиск без учёта регистра

**Проблема:** PostgreSQL `LIKE` регистрозависим — «АРИС» не находил «Арис».

**Исправлено:** Заменён `like` на `ilike` (PostgreSQL) во всех поисковых запросах:
- `app/Models/Company.php` — `scopeSearch()` (name, inn)
- `app/Models/Project.php` — `scopeSearch()` (name)
- `app/Models/Rfq.php` — `scopeSearch()` (title, number)
- `app/Models/Auction.php` — `scopeSearch()` (title, number)
- `app/Orchid/Screens/ProjectListScreen.php` — фильтр поиска
- `app/Http/Controllers/CompanyController.php` — поиск компаний

Кросс-БД совместимость: `ilike` для PostgreSQL, `like` для SQLite (тесты).

## 2026-02-17 — A21 (#66): Поиск компаний-участников для аукциона

**Проблема:** Статический `<select multiple>` для приглашения компаний — неудобно при большом количестве компаний.

**Исправлено:** Заменён на Alpine.js компонент `companySearch()` с динамическим поиском:
- `resources/views/auctions/create.blade.php` — интерактивный поиск через `/search/quick` API
- `app/Http/Controllers/AuctionController.php` — удалён избыточный запрос `$allCompanies`

---

## 17.02.2026 (сессия 2)

### C8: Admin notification on new company (issue #65) — CLOSED
- **Задача:** Уведомлять админов при создании новой компании.
- **Реализация:** Event-driven: `CompanyCreated` → `SendCompanyCreatedNotification` → `CompanyCreatedNotification` (email + database).
- **Файлы:** `app/Events/CompanyCreated.php` (new), `app/Listeners/SendCompanyCreatedNotification.php` (new), `app/Notifications/CompanyCreatedNotification.php` (new), `AppServiceProvider.php`, `CompanyController.php`.

### C7: Fix admin company verification (issue #64) — CLOSED
- **Причина 1:** `UpdateCompanyOrchidRequest::authorize()` не проверяла admin-доступ — 403 для администратора.
- **Причина 2:** `is_verified` отсутствовало в validation rules — checkbox молча игнорировался.
- **Фикс:** Добавлена admin-проверка в authorize(), `is_verified` в rules(), `sendTrueOrFalse()` в checkbox.

### A22: Fix PDF upload limit (issue #54) — CLOSED
- **Причина:** Docker-образ не был пересобран после добавления `docker/uploads.ini` (PHP `upload_max_filesize=2M` вместо 100M).
- **Фикс:** Rebuild нужен: `docker compose build app`. Также добавлен `maxFileSize(10)` в Orchid Upload fields.

### A20: Fix auction status on My Bids page (issue #52) — CLOSED
- **Причина:** Столбец «Статус» показывал только статус заявки (Ожидание/Принята), не показывая стадию аукциона.
- **Фикс:** `resources/views/auctions/my-bids.blade.php` — двухуровневый статус: статус аукциона + статус заявки.

### A19: Fix auction currency in trading (issue #53) — CLOSED
- **Причина:** Hardcoded ₽ в `AuctionController::getState()`, `StoreAuctionBidRequest`, `AuctionEditScreen`.
- **Фикс:** Заменено на `$auction->currency_symbol` во всех местах.

### G10: Fix logout 419 error (issue #55) — CLOSED
- **Причина:** CSRF token expired → 419 error page при нажатии Logout.
- **Фикс:** `bootstrap/app.php` — обработка `TokenMismatchException` → redirect to login.

### S5: Email SMTP (issue #24) — CLOSED (already done)
- Уже было настроено в commit 718c1b7 (Beget SMTP). Закрыт issue.

### PR1: Fix project editing authorization (issue #73) — CLOSED
- **Проблема:** Orchid admin-экраны `ProjectEditScreen` и `ProjectListScreen` не проверяли права доступа к конкретному проекту — любой пользователь с доступом к админ-панели мог редактировать/удалять чужие проекты.
- **Фикс 1:** `app/Orchid/Screens/ProjectEditScreen.php` — добавлены проверки `canManage()` в `query()`, `save()`, `remove()`.
- **Фикс 2:** `app/Orchid/Screens/ProjectListScreen.php` — добавлена проверка `canManage()` в `remove()`.
- **Фикс 3:** `app/Http/Controllers/ProjectController.php` — `destroy()`: заменена неконсистентная проверка `created_by` на `canManage()`.
- **Тесты:** 185/185 passed.

### A18: Fix auction PDF protocol (issue #57) — CLOSED
- **Причина:** stale model — `$auction->winner_bid_id` был `null` в памяти при генерации PDF (хотя в БД уже записан).
- **Фикс 1:** `app/Jobs/CloseAuctionJob.php` — добавлен `$auction->refresh()` после `determineWinner()`.
- **Фикс 2:** `app/Models/Auction.php` — `winnerBid()`: `hasOne` → `belongsTo`.
- **Тесты:** 185/185 passed.

### T10: Fix RFQ creation bug (issue #51) — CLOSED
- **BUG 1 (CRITICAL):** `RfqController::show()` — падал 500 для неавторизованных пользователей на закрытых RFQ. Добавлена проверка `auth()->check()`.
- **BUG 2 (CRITICAL):** `StoreRfqRequest` — валидация `required|file` для `technical_specification` блокировала temp-upload. Исправлено на `nullable|file` + проверка в `withValidator`.
- **BUG 4:** `UpdateRfqRequest` — `after:start_date` ссылался на пустое поле. Исправлено на `$rfq->start_date`.
- **BUG 7:** `Rfq::winnerBid()` — `hasOne` заменён на `belongsTo`.
- **Тесты:** добавлено поле `currency` в 5 RFQ-тестах и 3 Auction-тестах (pre-existing failures).
- **Файлы:** `app/Http/Controllers/RfqController.php`, `app/Http/Requests/StoreRfqRequest.php`, `app/Http/Requests/UpdateRfqRequest.php`, `app/Models/Rfq.php`, `tests/Feature/RfqTest.php`, `tests/Feature/AuctionTest.php`
- **Тесты:** 185/185 passed.

### Обновление бэклога: интеграция GitHub Issues #46-#80
- **Что сделано:** Проверены открытые GitHub Issues, обнаружено 6 новых (#75-#80: AI-идеи, переименование, мобильное приложение). Все issues #46-#80 интегрированы в бэклог.
- **Изменённые файлы:**
  - `docs/04_БЭКЛОГ_ФИКСОВ.md` — добавлено 31 новых задач. Новые категории: Проекты (PR1-PR2), AI/Этап 2 (AI1-AI4), Мобильное приложение (M1). Итого: 75 задач (21 готово, 54 ожидает). Обновлены фазы выполнения (6 фаз вместо 5).
  - `docs/05_АНАЛИЗ_GITHUB_ISSUES.md` — добавлен раздел 4 (issues #75-#80), обновлена сводка и связь с бэклогом.
  - `CLAUDE.md` — исправлены устаревшие ссылки на VKIDProvider (заменён на YandexProvider), обновлены OAuth-провайдеры, пакеты, Docker-конфиг, jobs, factories.

---

## 12.02.2026

### Анализ GitHub Issues vs ТЗ (Этап 1)

- Создан документ `docs/05_АНАЛИЗ_GITHUB_ISSUES.md` — анализ 26 открытых GitHub issues
- Сопоставление с ТЗ первого этапа: 67% задач — из ТЗ, 33% — новые
- Расчёт трудозатрат: 60–107 часов (~3–4 недели)
- Рекомендуемый порядок выполнения в 4 фазах

**Файлы:** docs/05_АНАЛИЗ_GITHUB_ISSUES.md

---

## 04.02.2026

### Переключение почты Unisender → Beget SMTP

- `.env.example`: SMTP-настройки заменены на Beget (`smtp.beget.com:465`, SSL)
- `.env`: хост, логин и параметры обновлены для Beget
- Важно: Beget требует совпадения MAIL_FROM_ADDRESS с MAIL_USERNAME

**Файлы:** .env.example, .env

### Фикс: class_exists() для Socialite в AppServiceProvider

- Обёрнута регистрация Yandex Socialite в `class_exists()` — предотвращает fatal error при `composer install` когда пакет ещё не установлен
- Убраны `use` импорты для `SocialiteProviders\*` — заменены на строковые литералы

**Файлы:** app/Providers/AppServiceProvider.php

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

---

### 2026-03-14 — Фикс 502 ошибки на продакшене

**Проблема:** Сайт bizzio.ru возвращал 502. Caddy-контейнер не мог зарезолвить хост `app` (`dial tcp: lookup app on 127.0.0.11:53: no such host`).

**Причина:** Caddy оказался только в сети `docker_default`, а app-контейнер — в `bizzio_app-network`. Контейнеры были в разных Docker-сетях и не видели друг друга. Вероятно, Caddy был пересоздан без учёта `docker-compose.override.yml`.

**Решение:** Пересоздан Caddy-контейнер: `docker compose up -d --force-recreate caddy`. Теперь он корректно подключён к обеим сетям (`bizzio_app-network` + `docker_default`), как прописано в override.

**Изменённые файлы:** нет (операция на сервере)

---

## 2026-03-18: Регистрация на главной странице

**Что сделано:** Форма регистрации теперь отображается на главной странице (bizzio.ru/?mode=register) вместо отдельной страницы /register. При нажатии «Создать аккаунт» показывается форма с полями: имя, email, пароль, подтверждение пароля. Ссылка «Уже есть аккаунт? Войти» возвращает на форму входа. Ошибки валидации корректно редиректят обратно на форму регистрации. Также исправлен баг в тесте reCAPTCHA (не устанавливался config site_key).

**Изменённые файлы:**
- `resources/views/welcome.blade.php` — добавлена форма регистрации с переключением по `?mode=register`
- `routes/auth.php` — GET `/register` теперь редиректит на `/?mode=register`
- `app/Http/Controllers/Auth/RegisteredUserController.php` — ошибки валидации редиректят на `/?mode=register`
- `tests/Feature/Auth/RegistrationTest.php` — обновлены тесты, добавлен тест формы на welcome page

---

## 2026-03-18: Исправление протокола аукциона (#57)

**Что сделано:** Устранена корневая причина некорректного PDF-протокола аукциона. `UpdateAuctionStatuses` (Job и artisan-команда) дублировали логику закрытия аукциона с ошибками: ставили несуществующий `winner_company_id` вместо `winner_bid_id`, не заполняли `trading_end`, не генерировали PDF-протокол. Теперь оба файла делегируют закрытие `CloseAuctionJob`, который корректно определяет победителя и генерирует протокол.

Также исправлена подпись в PDF-протоколах: «B2B платформа для строительной индустрии» → «соединяя бизнес» (#57 комментарий от MSverlov).

**Причина:** Два конкурирующих пути закрытия аукциона — `UpdateAuctionStatuses` (каждую минуту) срабатывал раньше `CloseAuctionJob` и закрывал аукцион без `winner_bid_id` и без PDF.

**Изменённые файлы:**
- `app/Jobs/UpdateAuctionStatuses.php` — делегирование закрытия в CloseAuctionJob
- `app/Console/Commands/UpdateAuctionStatuses.php` — аналогично
- `resources/views/pdf/auction-protocol.blade.php` — подпись «соединяя бизнес»
- `resources/views/pdfs/rfq-protocol.blade.php` — подпись «соединяя бизнес»

---

## 2026-03-18: Дефолтное время аукциона (#40)

**Что сделано:** Изменены дефолтные значения дат при создании аукциона: окончание приёма заявок +1 день (было +7 дней), начало торгов +5 мин после окончания приёма (было +1 день). JS-автосинк обновлён: +5 мин (было +1 мин).

**Изменённые файлы:**
- `resources/views/auctions/create.blade.php` — дефолтные значения дат и JS-синхронизация

---

## 2026-03-18: Приглашение компаний в открытом аукционе (#66)

**Что сделано:** Блок приглашения компаний теперь виден для обоих типов аукциона (открытый и закрытый). Ранее скрывался при выборе «Открытая процедура». Подсказка адаптируется: для закрытого — «только приглашённые», для открытого — «получат уведомление».

**Изменённые файлы:**
- `resources/views/auctions/create.blade.php` — убрано скрытие блока приглашений для открытого типа
