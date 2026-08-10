# Changelog Claude Code

Лог изменений, выполненных Claude Code.

---

## 2026-07-14 — feat: пункт меню «Создать коммерческий аукцион» (issue #179)

**Задача:** Заказчик не находил коммерческий аукцион на тесте — фича была доступна только как радиокнопка на форме «Создать запрос цен». Нужен отдельный пункт в меню «Закупки».

**Диагностика:** Код #179 корректно смёржен в `develop` и синхронизирован с `origin/develop` (задеплоен на test.bizzio.ru). Отдельного пункта меню не было по дизайну — процедура выбиралась на форме создания запроса цен.

**Изменения:**
- `resources/views/layouts/navigation.blade.php` — в дропдаун «Закупки» (десктоп + мобильное меню) добавлен пункт «Создать коммерческий аукцион» → `rfqs.create?procedure=commercial`. Порядок: Создать коммерческий аукцион / Создать аукцион / Создать запрос цен.
- `resources/views/rfqs/create.blade.php` — форма предвыбирает процедуру из query-параметра `procedure` (`old('procedure', request('procedure', 'standard'))`); заголовок/подзаголовок/`<title>` меняются на «Создать коммерческий аукцион» при `procedure=commercial`.

**Примечание:** только Blade-изменения, пересборка Vite-ассетов не требуется. Просмотр коммерческих аукционов — через существующий пункт «Найти закупку» (общий список тендеров).

---

## 2026-04-08 — feat: модератор проекта может добавлять участников из своей компании (issue #74)

**Задача:** У модератора проекта должна быть функция «Добавить участника» аналогично администратору, но ограниченная своей компанией.

**Изменения:**
- `app/Models/Project.php` — добавлен метод `canAddMember()` (canManage + модераторы проекта)
- `app/Http/Controllers/ProjectMemberController.php` — `store()` использует `canAddMember()`, проверяет assignableRoles, ограничивает модераторов своей компанией
- `resources/views/projects/partials/members-tab.blade.php` — форма «Добавить участника» отображается для модераторов проекта с подсказкой и корректным списком ролей
- `tests/Feature/ProjectMemberTest.php` — 3 новых теста: добавление из своей компании, запрет из чужой, запрет роли admin

---

## 2026-04-07 — fix: ограничение прав на смену ролей в проекте (issue #74, доработка)

**Проблема:** Модератор проекта мог назначить роль «Администратор», а участник мог повысить себе роль.

**Исправления:**
1. **Запрет смены собственной роли** — контроллер `ProjectMemberController@update` и view `members-tab.blade.php` блокируют изменение роли самому себе
2. **Ограничение назначаемых ролей** — новый метод `Project::getAssignableRoles()`: модератор может назначать только «Модератор» и «Участник», роль «Администратор» доступна только админам проекта и модераторам компании-заказчика
3. **Бэкенд-валидация** — контроллер проверяет, что назначаемая роль входит в список разрешённых для текущего пользователя
4. **Учёт модераторов компании** — `canManageMember()` и `getAssignableRoles()` теперь учитывают модераторов/владельцев компании-заказчика проекта

**Файлы:**
- `app/Models/Project.php` — `canManageMember()`, новый `getAssignableRoles()`
- `app/Http/Controllers/ProjectMemberController.php` — запрет self-update + валидация роли
- `resources/views/projects/partials/members-tab.blade.php` — фильтрация dropdown ролей

---

## 2026-04-06 — Системный промпт бота поддержки

- Создан `docs/SYSTEM_PROMPT_SUPPORT_BOT.md` — системный промпт для AI-ассистента поддержки BIZZIO.ru
- Адаптирован под реальный функционал платформы: компании, проекты, RFQ, аукционы, друзья, новости
- Включает таблицы типичных проблем, навигацию по платформе, правила безопасности

---

## 2026-04-06 — Замена виджета AI-чата

- Обновлён URL виджета AI-секретаря во всех шаблонах: `admin.ai-sekretar24.ru` → `ai-sekretar24.ru`, instance `httpsbizzioru` → `bizzio`
- **Файлы:** `layouts/app.blade.php`, `layouts/guest.blade.php`, `welcome.blade.php`

---

## 2026-04-06 — Доработки по замечаниям заказчика (#74, #130)

### #74 — Управление ролями участников проекта: ограничение прав
- Добавлен метод `Project::getMemberRole()` — получение роли пользователя в проекте
- Добавлен метод `Project::canManageMember()` — проверка прав на управление участником:
  - Администратор проекта может менять роли всех участников
  - Модератор проекта может менять роли только участников своей компании
  - Участник (member) не может менять ничьи роли
- Обновлён `ProjectMemberController::update()` и `destroy()` — используют `canManageMember()` вместо `canManage()`
- Обновлён шаблон `members-tab.blade.php` — dropdown ролей виден только при наличии прав на конкретного участника
- **Файлы:** `app/Models/Project.php`, `app/Http/Controllers/ProjectMemberController.php`, `resources/views/projects/partials/members-tab.blade.php`

### #130 — Поле поиска в шапке: расширение и исправление наслоения
- Расширено поле поиска на десктопе: `w-80 lg:w-96 xl:w-[28rem]` (было `w-64 lg:w-80 xl:w-96`)
- Сокращён placeholder: «Поиск...» вместо «Поиск компаний, проектов...» — убирает наслоение с иконкой лупы
- Добавлен CSS-класс `search-input` с `pl-10` в `@layer components` — гарантирует отступ для иконки (фикс конфликта с глобальным `px-3`)
- **Файлы:** `resources/views/layouts/navigation.blade.php`, `resources/css/app.css`

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

---

## 2026-04-13: Текст подсказки фильтра ключевых слов (#94)

**Что сделано:** Исправлен текст-подсказка на странице настройки ключевых слов — описание осталось от схемы «И», хотя логика фильтрации давно переключена на «ИЛИ». Заменил «содержащие ВСЕ эти слова одновременно» на «содержащие ЛЮБОЕ из этих слов».

**Изменённые файлы:**
- `resources/views/profile/keywords.blade.php` — правка текста в блоке «Как работает фильтрация?»

---

## 2026-04-13: Модератор компании видит поле «Добавить участника» (#74)

**Что сделано:** Обычный модератор компании (без флага `can_manage_moderators`) теперь видит форму «Добавить участника» на странице компании в вкладке «Люди» и может добавлять пользователей с ролью «Участник». Назначение ролей «Админ» и «Модератор» по-прежнему только у владельца / менеджеров (`canManageModerators`).

**Причина:** заказчик сообщил, что после PR #135 (проектные модераторы) форма добавления всё ещё не появляется у обычного модератора на странице компании — PR #135 покрывал только проекты, а жалоба была по `/companies/{slug}`.

**Изменённые файлы:**
- `app/Models/Company.php` — методы `canAddMember()` и `getAssignableMemberRoles()`
- `app/Http/Controllers/CompanyModeratorController.php` — `store()` теперь использует `canAddMember` и валидирует роль через `getAssignableMemberRoles`, обычный модератор не может выдавать флаг `can_manage_moderators`
- `resources/views/companies/show.blade.php` — форма гейтится на `canAddCompanyMember`, dropdown ролей берётся из `assignableMemberRoles`, для обычного модератора показывается подсказка

---

## 2026-05-31: Staging-окружение test.bizzio.ru + CI/CD пайплайн

**Что сделано:** Настроен полный пайплайн «локалка → git → проверка → merge → деплой». Появилось staging-окружение **test.bizzio.ru** (ветка `develop`) и автодеплой на прод **bizzio.ru** (ветка `main`) с ручным аппрувом.

- **Staging на сервере:** новый каталог `/var/www/bizzio-test` (compose-проект `bizzio-test`, отдельная БД на порту 5436, том изолирован), за тем же Caddy. БД — копия прод-БД, медиа скопированы из прода, почта переключена на `log`. Доступ закрыт basic-auth (логин `bizzio`). В `Caddyfile` добавлен блок `test.bizzio.ru` с авто-TLS.
- **CI/CD (`.github/workflows/ci.yml`):** джобы Tests / Pint / Assets на PR и push; деплой-джобы на push в `develop` (→ test) и `main` (→ prod, через GitHub Environment `production` с ручным аппрувом). Деплой по SSH выделенным ключом, общий скрипт `scripts/deploy.sh`. Включена защита веток `develop`/`main` (обязательные проверки).
- **Чистка перед зелёным CI:** прогнал Pint по всему репо (148 файлов, только формат); пересобрал устаревший `public/build` (прод отдавал старый CSS); исправил предсуществующие падающие тесты (стало 241 passed).

**Причина падавших тестов (для истории):**
- `SCOUT_DRIVER` в `.env.example` = `null` → все Scout-поиски возвращали пусто. Добавил `SCOUT_DRIVER=collection` в `phpunit.xml`.
- `MAIL_FROM_ADDRESS` пуст в тест-окружении → любое уведомление/событие с письмом падало с «email must have a From header» (в т.ч. создание компании → 500). Добавил `MAIL_FROM_ADDRESS` в `phpunit.xml`.
- `GET /login` теперь редиректит на модалку `/#login-form` — обновил устаревший Breeze-тест.
- Комментарии к проекту доступны только участникам; фабрика не добавляет создателя в участники (в отличие от `ProjectController`) — тесты добавляют участника явно.

**Изменённые/созданные файлы:**
- `.github/workflows/ci.yml` — пайплайн CI/CD
- `scripts/deploy.sh` — общий скрипт деплоя
- `phpunit.xml` — `SCOUT_DRIVER`, `MAIL_FROM_ADDRESS`
- `tests/Feature/Auth/AuthenticationTest.php`, `tests/Feature/ProjectTest.php` — фиксы тестов
- `public/build/*` — пересборка ассетов
- 148 PHP-файлов — форматирование Pint
- `docker-compose.override.yml` — снят с git-трекинга (был и в .gitignore, и закоммичен)
- `CLAUDE.md` — секция CI/CD, Caddy вместо nginx-proxy

---

## 2026-06: Блок «Первоочередные» (16 задач) + тесты + отчёт

**Что сделано:** Реализованы 15 из 16 приоритетных задач (#139, #143, #144, #140, #149, #136, #142, #145, #137, #146, #134, #147, #148, #150, #152); #151 ожидает уточнения (не воспроизводится). Каждая задача — отдельным PR в develop с авто-деплоем на test.bizzio.ru.

**Тесты:** добавлены автотесты по задачам (NotificationTest, CompanyTest, FriendshipTest, RegistrationTest, SocialiteAvatarTest, AuctionTest, RfqTest, DashboardTest, SeoTest, PriorityTasksTest). Полный прогон — **264 теста, все зелёные**; Pint без замечаний.

**Отчёт заказчику:** `docs/ОТЧЁТ_приоритетные_задачи_2026-06.md`.

**Ключевые технические моменты:**
- #143: причина дублей — слушатели регистрировались дважды (авто-discovery Laravel 11 + ручной Event::listen). Убрана ручная регистрация; добавлена миграция-очистка старых дублей (pgsql self-join, т.к. MIN(uuid) не поддерживается).
- #145/#137/#148: миграции — users.last_name, company_user.position, auctions/rfqs.cancellation_reason.
- #146: Cropper.js через npm+Vite, переиспользуемый компонент x-avatar-cropper.
- #152: partials/seo.blade.php (meta/OG/Twitter/canonical + Schema.org JSON-LD), robots.txt, динамический /sitemap.xml, llms.txt.
- CI: проверка свежести public/build заменена на сборку (хэши Vite/Tailwind отличаются между машинами).

---

## 2026-06-19 — fix: регистрация на проде + только Яндекс OAuth

**Проблема (регистрация на проде):** Пользователи не могли зарегистрироваться. Причина — правило валидации email `email:rfc,dns`: правило `dns` делает живой DNS/MX-запрос (`checkdnsrr()`), который внутри прод-контейнера падает/таймаутится, из-за чего валидные email отклонялись у всех.

**Что сделано:**
- `app/Http/Controllers/Auth/RegisteredUserController.php` — `email:rfc,dns` → `email:rfc` (убрана проверка MX-записи).
- Со страниц авторизации/регистрации убрана кнопка входа через Google — оставлен только Яндекс:
  - `resources/views/welcome.blade.php` (основная форма) — удалена кнопка Google.
  - `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php` (Breeze-вьюхи, не используются напрямую, но почищены) — удалены Google и VK, остался только Яндекс.

---

## 2026-06-29 — fix #174: Запрос цен — протокол и приглашения

**Проблема 1 (участник не может скачать протокол):** Кнопка «Скачать протокол» в `rfqs/show` показывалась только организатору. Логика участника в blade сверяла его заявки с `$availableCompanies`, но эта коллекция заполняется только когда RFQ активен и можно подать заявку (`$canBid`). У закрытого RFQ она всегда пуста → участник никогда не проходил проверку.

**Проблема 2 (статус приглашения не меняется):** При подаче заявки (`storeBid`) статус приглашения оставался `pending` («Ожидает ответа»). В отличие от аукционов (#110), в RFQ не было обновления статуса приглашения на `accepted`.

**Что сделано:**
- `app/Http/Controllers/RfqController.php`:
  - `show()` — добавлен расчёт `$canDownloadProtocol` по реальным компаниям пользователя (`$userCompanies`/`moderatedCompanies`), передаётся во вьюху.
  - `storeBid()` — после создания заявки приглашение компании переводится `pending` → `accepted` (паттерн из AuctionController #110).
- `resources/views/rfqs/show.blade.php` — блок скачивания протокола использует `$canDownloadProtocol` вместо сломанной логики с `$availableCompanies`.

---

## 2026-06-29 — fix #178: Запрос цен — не формируется протокол (висит «Активный»)

**Причина:** Закрытие RFQ зависело ИСКЛЮЧИТЕЛЬНО от отложенной задачи `CloseRfqJob::dispatch($rfq)->delay($rfq->end_date)` (драйвер очереди `database`). Если queue-воркер был перезапущен/недоступен в момент `end_date` (типично при редеплое на тесте), отложенная задача терялась — и RFQ навсегда оставался в статусе `active`, протокол не генерился. У аукционов проблемы нет: их закрывает scheduled-команда `auctions:update-statuses` каждую минуту. У RFQ аналогичной подстраховки не было.

**Что сделано:**
- `app/Console/Commands/CheckExpiredRfqs.php` (новый) — команда `rfqs:check-expired`: находит активные RFQ с `end_date <= now()` и ставит их на закрытие через `CloseRfqJob` (по образцу `CheckExpiredAuctions`).
- `bootstrap/app.php` — команда `rfqs:check-expired` добавлена в планировщик (`everyMinute` + `withoutOverlapping` + `runInBackground`).
- `app/Jobs/CloseRfqJob.php` — добавлена идемпотентность: `$this->rfq->refresh()` + ранний выход, если RFQ уже `closed` (отложенная задача и новая команда могут попасть на один RFQ — защита от двойного скоринга/перегенерации протокола).

**Зависшие RFQ** (напр. test rfqs/28) подхватятся командой автоматически на ближайшем прогоне планировщика после деплоя.

**Примечание:** часть #178 про скачивание протокола участником уже закрыта в #174.

---

## 2026-06-29 — fix #177: Запрос цен — скрыть предложения участников на этапе подачи

**Проблема:** На этапе подачи заявок (`active`) любой пользователь видел обезличенные заявки участников с ценой, сроком и авансом. Должны быть скрыты от всех (включая организатора) — на этапе подачи показываем только количество заявок; сами предложения доступны только в протоколе после завершения.

**Что сделано:**
- `resources/views/rfqs/show.blade.php` — блок «Список заявок» перестроен:
  - Если RFQ не закрыт (`status !== 'closed'`) — вместо таблицы показывается только счётчик «Подано заявок: N» (для всех, включая организатора). Цена/срок/аванс в DOM не попадают.
  - Полная таблица с названиями компаний, ценами и баллами рендерится только после закрытия (`status === 'closed'`) и только тем, кому доступны результаты (`$canSeeResults`).
  - Ветка «результаты скрыты организатором» сохранена для закрытых RFQ с `is_results_hidden`.

**Примечание:** ранее заявки на активном этапе были лишь обезличены (A15), но цены оставались видны — теперь скрыты полностью.

---

## 2026-06-29 — feat #176: Компании — поочерёдная загрузка и удаление файлов

**Задача:** (1) При создании компании файлы можно было выбрать только зажав Ctrl в системном диалоге; нужно добавлять по очереди. (2) Нельзя было выборочно удалять ранее загруженные документы при редактировании.

**Что сделано:**
- `resources/views/components/pdf-documents-input.blade.php` (новый) — переиспользуемый Alpine-компонент: кнопка «Добавить файл» открывает диалог, каждый выбор добавляется в общий список (не заменяя предыдущий), лишние можно убрать до отправки. Накопленные файлы синхронизируются с реальным `<input name="documents[]">` через `DataTransfer`. Дедуп по имени+размеру.
- `resources/views/companies/create.blade.php` — сырой `<input type="file" multiple>` заменён на `<x-pdf-documents-input>`.
- `resources/views/companies/edit.blade.php`:
  - блок «Добавить документы» переведён на тот же компонент;
  - у каждого загруженного документа добавлена кнопка «Удалить» (AJAX `DELETE` без перезагрузки формы, JS `deleteCompanyDocument`).
- `app/Http/Controllers/CompanyController.php` — добавлен метод `deleteDocument()` (по образцу `deletePhoto`): проверка прав модератора, удаление media из коллекции `documents`, JSON-ответ для AJAX.
- `routes/web.php` — маршрут `DELETE companies/{company}/documents/{media}` → `companies.documents.delete`.

**Backend хранения не менялся:** документы по-прежнему в Spatie Media Library (коллекция `documents`), форма шлёт `documents[]`, валидация `documents.*` без изменений.

---

## 2026-06-29 — fix #175: Аукцион — статус при подаче заявки

**Проблема:** После первой подачи заявки на аукцион (этап `active`) показывался блок с текстом «Вы уже подали заявку на этот аукцион». Слово «уже» создавало ощущение повторной/ошибочной подачи, хотя это была первая заявка. Функционально всё работало корректно — проблема только в формулировке.

**Что сделано:**
- `resources/views/auctions/show.blade.php` — текст блока `$existingBid` заменён на позитивное подтверждение: «Ваша заявка на участие принята» + пояснение «Ожидайте начала торгов — вы получите уведомление».

---

## 2026-06-30 — Правки после тестов: #178, #143, #151, #145

**#178 — RFQ не закрывался на test (протокол не формировался).** Диагностика тест-сервера: код-фикс `rfqs:check-expired` корректен, но queue-воркер (`laravel-worker`) был в статусе FATAL — во время деплоя `composer install` кратковременно убирает `vendor/`, воркер падает, supervisor перестаёт его перезапускать. В очереди скопилось 2165 задач, `CloseRfqJob` не выполнялся. Воркер перезапущен вручную (`supervisorctl start laravel-worker`) — backlog разобран, rfq 28 и 29 закрылись.
- `scripts/deploy.sh` — после деплоя принудительный `supervisorctl restart laravel-worker` (поднимает воркер из FATAL); прежний `queue:restart` бесполезен для упавшего процесса.

**#143 — счётчик уведомлений не сбрасывался после прочтения.** Клик по уведомлению вёл на связанную страницу, но не помечал его прочитанным.
- `NotificationController::open()` + роут `GET /notifications/{notification}/go` (`notifications.open`): помечает прочитанным и редиректит на целевой URL.
- Ссылки в `notifications/index.blade.php` и выпадашке колокольчика (`layouts/navigation.blade.php`) переведены на этот роут. Счётчик пересчитывается при следующей загрузке страницы.

**#151 — наслоение иконки-лупы на плейсхолдер в поиске.** По просьбе заказчика иконка просто убрана.
- `companies/index.blade.php`, `friends/index.blade.php` — удалён блок svg-лупы, скорректирован отступ поля.

**#145 — Имя/Фамилия в админке.**
- `app/Orchid/Layouts/User/UserEditLayout.php` — добавлено поле «Фамилия» (`user.last_name`) в форму редактирования/модалку.
- `app/Orchid/Presenters/UserPresenter.php` — заголовок Persona теперь `full_name` (в списке пользователей видны Имя и Фамилия). Сохранение уже работает через `fill()` (поле в `$fillable`).

---

## 2026-07-07 — fix #182: Только верифицированные компании могут участвовать в закупках

**Проблема:** Неверифицированная компания смогла подать заявку на участие в запросе цен (и, аналогично, в аукционе). Участвовать в закупках должны только верифицированные компании. Дополнительно `RfqController::storeBid` вообще не имел валидации и серверных проверок — заявку можно было подать от имени любой компании по её `company_id`.

**Что сделано (серверный контроль — граница безопасности):**
- `app/Http/Controllers/RfqController.php` — `storeBid()`: добавлены валидация полей и проверки: RFQ активен и не просрочен; текущий пользователь — модератор компании; компания `is_verified`; компания не организатор; для закрытого RFQ — приглашена; запрет повторной заявки.
- `app/Http/Controllers/AuctionController.php` — `storeBid()`: перед созданием заявки/ставки проверяется, что пользователь — модератор компании, компания `is_verified`, не организатор, аукцион принимает заявки/идут торги, для закрытого — приглашена. Добавлен `use App\Models\Company`.

**UI (не предлагать неверифицированные компании):**
- `RfqController::show()` — кандидаты на подачу считаются из `biddableCompanies` = верифицированные компании пользователя (`canBid`, `availableCompanies`).
- `AuctionController::show()` — `canBid` и список компаний в форме заявки (`bidCompanies`) считаются из `biddableCompanies`. Просмотр результатов/протоколов оставлен на `userCompanies` (участник мог быть верифицирован ранее).

**Тесты:** `tests/Feature/PriorityTasksTest.php` — 4 новых теста #182 (неверифицированная не подаёт заявку в RFQ и аукцион, не видна в форме RFQ; верифицированная по-прежнему подаёт). Полные RfqTest+AuctionTest (87) зелёные, Pint чистый.

---

## 2026-07-12 — feat: Бейджи (ачивки) пользователей

**Задача:** Админ из админки выдаёт выбранным пользователям бейджи — цветная рамка + подпись.
Бейдж виден на созданных пользователем тендерах (RFQ) и аукционах, на его ответах/ставках
(только после раскрытия анонимности) и на публичном профиле. У пользователя может быть несколько
бейджей. Цвет: пресеты красный/бордовый/зелёный + произвольный из палитры. Подпись: пресеты
«Подозрительная личность»/без подписи/«Подтверждён» + произвольный текст.

**Модель данных:**
- `database/migrations/2026_07_12_120000_create_user_badges_table.php` — таблица `user_badges`
  (`user_id`, `color`, `label` nullable, `created_by` nullable).
- `app/Models/UserBadge.php` — модель + пресеты `COLOR_PRESETS`/`LABEL_PRESETS` и хелперы
  `resolveColor()`/`resolveLabel()` (пресет/кастом → итоговый HEX/подпись).
- `app/Models/User.php` — связь `badges(): HasMany`.

**Админка (Orchid):**
- `app/Orchid/Layouts/User/BadgeFormLayout.php` — общая форма (цвет: Select + `input[type=color]`; подпись: Select + текст).
- `app/Orchid/Layouts/User/BadgeAssignLayout.php` — модал массовой выдачи (Relation-выбор пользователей + форма).
- `app/Orchid/Layouts/User/UserBadgeListLayout.php` — таблица текущих бейджей с предпросмотром и удалением.
- `UserEditScreen` — блоки «Бейджи пользователя» и «Выдать бейдж» + методы `addBadge`/`removeBadge`.
- `UserListScreen` — команда «Выдать бейдж» (модал) + метод `assignBadge` (массовая выдача выбранным).

**Отображение (Blade):**
- `resources/views/components/user-badges.blade.php` — компонент-чипы `<x-user-badges :user="…">`.
- Внедрение: профиль (`users/show` — рамка карточки + чипы), `rfqs/show` и `auctions/show`
  (чипы у «Создатель»), карточки `rfq-card`/`auction-card` (рамка по создателю),
  таблицы результатов ставок — чипы `$bid->user` только в раскрытых ветках (анонимность сохранена).
- Eager-load `creator.badges`/`bids.user.badges` в `RfqController`, `AuctionController`, `TenderController`.

**Тесты:** `tests/Unit/UserBadgeTest.php` (5) + `tests/Feature/UserBadgeDisplayTest.php` (3) — все зелёные.
Pint чист. Ассеты не пересобирались (стили — инлайн Tailwind, `app.css` не менялся).

**Примечание об окружении:** локальный прогон делался в контейнере `php:8.2-cli` без расширения
`gd`, из-за чего 2 теста генерации PDF-протоколов (`CommercialAuctionTest`, `AuctionTest`) падают —
это не связано с бейджами и воспроизводится на чистом коде; на образе приложения (с `gd`) и в CI они зелёные.

---

## 2026-07-13 — Коммерческий аукцион (#179), двухэтапная процедура

Новый вид тендера: этап 1 — Запрос цен (только цена, обезличенно, без промежуточного
протокола) → автоматически этап 2 — коммерческий аукцион в реальном времени по трём
критериям (цена, срок, аванс) с принципом непрерывного лидерства. Ветка
`feat/commercial-auction`, 7 слайсов. Архитектура — расширение существующих `Rfq`/`Auction`
(дискриминатор `procedure`), связанная пара RFQ↔Auction.

**Данные:** миграции добавляют `procedure` + параметры этапа 2 в `rfqs`
(`trading_start/end`, `step_*`, `max_deadline/advance`, `linked_auction_id`), в `auctions`
(`rfq_id`, веса/шаги/референсы, `best_bid_id`) и критерии/`total_score`/`is_base`/
`became_best_at` в `auction_bids`; `rfq_bids.deadline/advance_percent` → nullable.

**Движок:** `CommercialAuctionScoringService` — фиксированная нормировка от референсов,
итоговый балл по весам, приём «строго лучше», пороги «Уменьшите до X» (замкнутая форма).

**Backend:** `CommercialAuctionLauncherService` (авто-запуск этапа 2, НМЦ = макс. цена
этапа 1); ветки в `CloseRfqJob`, `UpdateAuctionStatuses` (закрытие по `trading_end`),
`AuctionWinnerService` (победитель = лучшее предложение); `AuctionController::storeOffer`
(lockForUpdate, приём/отклонение) + расширенный `getState`; `StoreCommercialOfferRequest`;
маршрут `auctions.offers.store`.

**Frontend:** переключатель процедуры + параметры этапа 2 в `rfqs/create`; форма заявки
этапа 1 «только цена»; партиал `auctions/partials/commercial-trading` («Настройка
предложения»: 3 критерия, слайдеры, диапазоны, подсказки, зеркало скоринга в Alpine,
long-poll, гейтинг кнопки, история лучших); метки в карточках + фильтр `procedure` в
каталоге; баннер этап 1→2.

**Протокол:** `CommercialAuctionProtocolService` + `pdfs/commercial-auction-protocol`
(победитель с ИНН, итоговые значения, рейтинг, история лучших).

**Тесты:** `tests/Feature/CommercialAuctionTest.php` (19) + `tests/Unit/CommercialAuctionScoringServiceTest.php` (8).
Полный прогон: 308 тестов зелёные. Pint чист.

**Примечание о git:** из-за параллельной работы над репозиторием слайс 6 (`73d58a2`) случайно
захватил в тот же коммит фичу «Бейджи пользователей» (UserBadge). Историю не переписывал —
UserBadge оставлен как есть. Слайс 7 сделан в изолированном git worktree.

---

## 2026-07-15 — Коммерческий аукцион: фиксы по комментариям заказчика (#179)

Разобраны 5 замечаний заказчика (MSverlov) из канбан-issue #179 от 14.07:

1. **Убран выбор «Вид процедуры»** на форме создания — процедура определяется пунктом меню
   (URL `?procedure=commercial`), радиокнопки заменены на скрытый `<input>`.
2. **Надписи дат** для коммерческого аукциона: «Дата начала/окончания приёма заявок» →
   «…приёма предложений» (условно по `$isCommercial`).
3. **Блок «Начало торгов (этап 2)»** перенесён к датам приёма предложений; **«Окончание
   торгов» (`trading_end`) удалён** — торги закрываются через 20 мин после последнего
   предложения, как в обычном аукционе.
4. **Поля «Максимальные допустимые значения» (`max_deadline`/`max_advance`) удалены** с
   формы. По решению заказчика референсы нормировки срока/аванса выставляются **первым
   предложением этапа 2** (в `AuctionController::storeOffer`, под `lockForUpdate`), далее
   участники задают критерии свободно.
5. **Подача заявки на этапе 1**: форма уже работает (цена-only). Добавлены пояснения в
   вкладке «Заявки», почему пользователь не может подать заявку/предложение (черновик /
   организатор / компания не верифицирована/не приглашена / приём завершён); ветка
   `@elseif($alreadyBid)` сделана достижимой; тексты процедуро-зависимые.

**Изменённые файлы:**
- `resources/views/rfqs/create.blade.php` — убран селектор процедуры, перенос/удаление
  блоков дат этапа 2, удалены поля макс. значений.
- `resources/views/rfqs/show.blade.php` — реструктуризация вкладки «Заявки» + пояснения.
- `resources/views/auctions/partials/commercial-trading.blade.php` — форма торгов работает
  без организаторских границ: границы полей из референсов (фолбэк до 1-го предложения),
  подхват `refs` из long-poll.
- `app/Http/Requests/StoreRfqRequest.php` — убраны правила/сообщения `trading_end`,
  `max_deadline`, `max_advance`.
- `app/Http/Controllers/RfqController.php` — `store` больше не пишет удалённые поля.
- `app/Services/CommercialAuctionLauncherService.php` — аукцион не наследует `trading_end`/
  `max_*` (референсы — из первого предложения).
- `app/Http/Controllers/AuctionController.php` — `storeOffer` выставляет референсы первым
  предложением, убраны организаторские верхние границы; `commercialState.time_remaining`
  считается от `last_bid_at + 20 мин`.
- `app/Jobs/UpdateAuctionStatuses.php` — коммерческие аукционы закрываются по общему правилу
  20 мин с последнего предложения (убрана ветка по `trading_end`).
- `tests/Feature/CommercialAuctionTest.php` — тесты приведены к новому поведению
  (референс от 1-го предложения, закрытие по простою, без организаторских макс. значений).

**Проверка:** полный прогон тестов зелёный, Pint чист, blade-шаблоны компилируются.

---

## 2026-07-15 — HOTFIX: 500 при регистрации компании с 12-значным ИНН (прод)

**Симптом:** на bizzio.ru при регистрации компании — ошибка 500.

**Причина:** `SQLSTATE[22001]: value too long for type character varying(10)` при вставке в
`companies`. Колонка `inn` была `varchar(10)`, а ИНН ИП/физлица содержит 12 цифр. Валидация
(`StoreCompanyRequest`, regex `^\d{10}(\d{2})?$`) 12 цифр допускает, но миграция
`2025_12_22_163101_change_inn_length_in_companies_table` ранее ошибочно сузила колонку с 12 до 10.

**Фикс:**
- Немедленно на проде: `ALTER TABLE companies ALTER COLUMN inn TYPE varchar(12)` — регистрация
  разблокирована сразу.
- Миграция `2026_07_15_160000_widen_inn_length_in_companies_table.php` — `inn` → `varchar(12)`
  (для консистентности test/прод и защиты от регресса при пересборках БД).

**Файлы:** `database/migrations/2026_07_15_160000_widen_inn_length_in_companies_table.php`.

---

## 2026-07-15 — FIX: верификация ИП с 12-значным ИНН в админке (Orchid)

**Симптом:** ИП с 12-значным ИНН регистрировался, но верифицировать его в админке было
нельзя — сохранение экрана компании отклоняло ИНН (пришлось вручную укорачивать до 10 цифр).

**Причина:** админ-панель жёстко требовала ровно 10 цифр в трёх местах, тогда как ИНН
ИП/физлица = 12 цифр (фронт регистрации это допускает):
- `UpdateCompanyOrchidRequest`: правила `size:10` + `regex:/^\d{10}$/`
- `CompanyEditScreen::save()`: проверка `strlen($inn) !== 10`

**Фикс:** приведено к правилу фронта — 10 (юрлицо) или 12 (ИП/физлицо) цифр:
- `UpdateCompanyOrchidRequest`: `regex:/^\d{10}(\d{2})?$/` (убран `size:10`)
- `CompanyEditScreen::save()`: `in_array(strlen($inn), [10, 12])`, обновлены текст ошибки и help.

**Файлы:** `app/Http/Requests/UpdateCompanyOrchidRequest.php`,
`app/Orchid/Screens/CompanyEditScreen.php`.

---

## 2026-07-22 — доработки коммерческого аукциона по замечаниям заказчика (#202–#207)

Комментарии заказчика от 14–15 июля в #179 (убрать «Вид процедуры», «приём заявок»→«предложений»,
перенос/удаление блоков торгов, удаление «Максимальных значений», пояснения на вкладке «Заявки»)
уже были закрыты ранее коммитом 0c0a52e. Здесь — дочерние issues #202–#207 + округление весов.

**#202 — НМЦ этапа 2 = среднее.** `CommercialAuctionLauncherService::launch()` — начальная цена
этапа 2 теперь среднее по предложениям этапа 1 (было максимум). Обновлены докблок, комментарий в
`AuctionController::storeOffer()` и тест `test_closing_commercial_rfq_launches_stage_2_auction`.
⚠️ Разворот раннего указания («от максимальной»): участники с ценой выше среднего в первом
предложении этапа 2 обязаны опуститься ниже своей цены этапа 1 (жёсткой блокировки нет — торги на понижение).

**#203 — инфо-поле этапа 1.** `resources/views/rfqs/show.blade.php` — для коммерческого RFQ подписи
дат «Начало/Окончание приёма предложений» + отдельная строка «Начало коммерческого аукциона»
(`trading_start`). Для обычного RFQ подписи без изменений.

**#204 — скрыть промежуточные результаты этапа 1.** Там же — у коммерческого RFQ всегда показываем
только количество поданных предложений (count-блок), без таблицы цен/баллов, даже после закрытия
(итоги определяются на этапе 2).

**#205 — авто-открытие этапа 2.** Новый лёгкий эндпоинт `GET /rfqs/{rfq}/stage2-status`
(`RfqController::stage2Status`, роут в `routes/web.php`) + JS-поллинг на странице этапа 1: участник,
ожидающий старта, автоматически переходит на аукцион в момент запуска (не пропустит кнопку).

**#206 — критерии этапа 2 от текущего лучшего.** `resources/views/auctions/partials/commercial-trading.blade.php`
— поля цены/срока/аванса пересеиваются значениями текущего лучшего предложения (`seedFromBest()`),
при смене лидера обновляются автоматически; шкала ползунка = максимум критерия. Это же чинит неверную
подсказку снижения (базой стала актуальная лучшая заявка, а не дефолты).

**#207 — поле «Цена».** Там же — крупные кнопки шага −/+ со стабильной шириной (курсор на месте),
нативные мелкие стрелки скрыты (`.ca-no-spin`), под полем — значение с разделением разрядов.
Примечание: разряды показаны под полем, а не внутри `<input type=number>` (нативный number-инпут не
поддерживает форматирование внутри; полноценная маска — по отдельному запросу).

**Округление весов критериев.** `resources/views/rfqs/create.blade.php` — поля весов критериев
`step="0.01"` → `step="1"` (целые проценты; поля шага цены/аванса 0.5% не тронуты).

**Тесты:** `CommercialAuctionTest` — обновлён тест НМЦ (#202), добавлены проверки #204 (только
количество, без таблицы) и `stage2Status` (#205). Локально 18 passed, 1 failed (генерация
PDF-протокола — нехватка `gd` в контейнере php:8.2-cli, не связано; в CI/на образе приложения зелёно).
Pint чист. Ассеты пересобраны (`npm run build`) — новые Tailwind-классы кнопок.

---

## 2026-07-24 — #210/#206/#207 Коммерческий аукцион, этап 2: доработки после тестов

Ветка `fix/commercial-auction-stage2-210-206-207` → develop → test.bizzio.ru.
Разворот дизайна #209 по решению заказчика: референсы нормировки (max срок/аванс) задаёт
**организатор на этапе 1**, а не первое предложение этапа 2.

**#210 — организатор задаёт макс. значения Срока и Аванса на этапе 1.**
- `resources/views/rfqs/create.blade.php` — новые поля `max_deadline` (дни) и `max_advance` (%)
  в секции «Параметры коммерческого аукциона»; поправлен устаревший текст (НМЦ этапа 2 = средняя,
  нормировка от организаторских максимумов).
- `StoreRfqRequest` — правила `max_deadline`/`max_advance` (`required_if:procedure,commercial`) + сообщения.
- `RfqController::store` — сохранение `max_deadline`/`max_advance` для коммерческого RFQ.
- `CommercialAuctionLauncherService::launch()` — переносит `max_deadline`/`max_advance` из RFQ в аукцион
  (раньше не переносились — оставались NULL).
- `AuctionController::storeOffer()` — убрана логика «первое предложение фиксирует референсы»; добавлена
  серверная проверка: срок/аванс не превышают организаторские максимумы.

**#206 — математика/UX этапа 2.**
- `commercial-trading.blade.php` — верхние границы полей срок/аванс = организаторские максимумы (100% шкалы),
  убраны дефолты-заглушки 730 дн / 100% (референсы теперь всегда заданы). Поля по-прежнему сеются от
  текущего лучшего предложения (`seedFromBest`), подсказка «Уменьшите до X» считается от корректных
  фиксированных референсов. Строгое отклонение неулучшающего предложения — сервер (`wouldBeat`) и клиент.

**#207 — поле «Цена» и кнопки критериев.**
- Поле цены — текстовый ввод с разделителем разрядов (формат при blur/шаге/сиде, стабильный курсор при
  наборе); реальное число уходит на сервер скрытым полем. Крупные кнопки −/+.
- Кнопки −/+ добавлены также для полей «Срок» и «Аванс» (нативные мелкие стрелки скрыты `.ca-no-spin`).

**Тесты (`CommercialAuctionTest`):** обновлены под #210 (создание RFQ сохраняет max_*, лаунчер их
переносит, `required_if`-валидация); заменён тест «первое предложение задаёт референс» на
«референсы организатора фиксированы и не меняются предложениями» + новый тест отклонения предложения
сверх максимума; поправлен `refs.max_deadline` в state-payload. Pint чист.

---

## 2026-07-24 — Первоочередные issues: #182, #191, #118, #188, #185

Ветка `fix/priority-batch-191` → develop → test.bizzio.ru.

**#182 (проверка).** Неверифицированная компания не может подавать заявки — уже реализовано
(guard `is_verified` в `RfqController::storeBid` / `AuctionController::storeBid`/`storeOffer`, UI
фильтрует компании, 4 теста `PriorityTasksTest`). Карточка переведена в In review (test).

**#191 — PDF-протоколы (аукцион / запрос цен / ком. аукцион).**
- Заголовок → «ПРОТОКОЛ ПОДВЕДЕНИЯ ПРЕДВАРИТЕЛЬНЫХ ИТОГОВ».
- Победитель — формат ОПФ «Название» (ИНН …) через `Company::legalNameWithInn()`.
- Строка «Общее количество компаний-участников».
- Оговорка об информационном характере результатов.
Файлы: `resources/views/pdf/auction-protocol.blade.php`, `pdfs/rfq-protocol.blade.php`,
`pdfs/commercial-auction-protocol.blade.php`, `app/Models/Company.php`.

**#118 — протокол при авто-отмене аукциона без заявок.**
- `AuctionProtocolService` формирует протокол с причиной отмены; `UpdateAuctionStatuses` вызывает
  генерацию при отмене (нет заявок / нет ставок 24 ч); шаблон показывает уведомление об отмене.

**#188 — «Подано заявок» в карточке обычного аукциона** (`auctions/show.blade.php`, sidebar).

**#185 — конкурсная документация (полная реализация).**
- Четыре типа документов (Извещение / ТЗ / Проект договора / Прочие файлы) для RFQ, Ком. аукциона и
  Аукциона — media-коллекции `notice` / `technical_specification` / `contract_draft` / `other_documents`
  (только PDF). Трейт `HasProcurementDocuments`, константы в `App\Support\ProcurementDocuments`.
- Валидация: только PDF, суммарный объём ≤ 20 МБ (`ValidatesProcurementDocuments` в Store/Update
  Request'ах RFQ и Аукциона; поднят `config/media-library.php` до 20 МБ).
- Сжатие PDF при загрузке (`PdfCompressionService`, Ghostscript `/ebook` с graceful fallback —
  если `gs` нет в образе, файл сохраняется без изменений; для активации нужен ghostscript в Docker-образе).
- Скачивание архивом (ZIP) и по файлу через `ProcurementDocumentController` с контролем доступа:
  после завершения процедуры документы доступны только организатору и участникам (`documentsAccessibleBy`).
- Автоудаление: команда `documents:cleanup` (планировщик, ежедневно 03:00) удаляет документацию
  завершённых процедур старше срока хранения; срок настраивается в админке
  (Orchid `DocumentSettingsScreen`, модель `Setting`, миграция `settings`).
- Формы create/edit RFQ и Аукциона используют партиал `partials/procurement-documents`; страницы
  процедур — `partials/procurement-documents-list` (список + кнопка «Скачать всё архивом»).

**Тесты:** RfqTest + CommercialAuctionTest + AuctionTest — 108 passed; добавлены 3 теста #185
(мультизагрузка + zip, лимит 20 МБ, закрытие доступа после завершения) и тест #118. Pint чист (307 файлов).

**Примечание по #185:** сжатие PDF реализовано, но требует `ghostscript` в Docker-образе `app`
(сейчас не установлен) — до этого работает fallback (файлы сохраняются как есть).

---

## 2026-07-24 — Первоочередные (часть 2): #189, #198, #193, #184, #186, #211

Ветка `fix/priority-batch-2` → develop → test.bizzio.ru.

**#189 — таймер обратного отсчёта.** Живой JS-таймер `partials/countdown.blade.php` (самодостаточный
Alpine). Обычный аукцион (`auctions/show`): «До окончания приёма заявок» между статусом и типом
процедуры (и «До начала приёма заявок», если ещё не стартовал). Коммерческий аукцион (`rfqs/show`):
«До окончания приёма предложений (этап 1)» и «До начала аукциона (этап 2)».

**#198 — количество участников в торгах ком. аукциона.** `AuctionController` (commercial state)
отдаёт `participants_count` (уникальные компании среди предложений); `commercial-trading.blade.php`
показывает «Участников (сделали ставку): N» (обновляется по поллингу).

**#193 — кнопка «Поделиться».** `partials/share-invite.blade.php` — формирует готовый текст
приглашения сторонней (незарегистрированной) компании по шаблону заказчика (вид/наименование
закупки, ссылки на закупку и конкурсную документацию, приём заявок, начало торгов, организатор,
описание, создатель) + кнопки «Копировать текст» и «Отправить по email». Только организатору,
на страницах RFQ и Аукциона.

**#184 — номера версий (тест/прод).** `config/app.php` → `version` (из `APP_VERSION` или файла
`VERSION` в корне); `partials/version-footer.blade.php` в layouts app/guest показывает версию и —
на не-проде — бейдж окружения (`local`/`staging`), чтобы различать тест и прод.

**#186 — Title/описание.** Убрано «для строительной отрасли». Title → «Bizzio.ru — соединяя бизнес
(В2В бизнес-сеть)» (`welcome.blade.php`, `layouts/app.blade.php`, `partials/seo.blade.php`);
meta-описание → «Bizzio.ru — В2В бизнес-сеть: люди, компании, закупки, совместные проекты,
отраслевые новости.» (обновлены og/twitter/Schema.org).

**#211 — поля протокола ком. аукциона.** `pdfs/commercial-auction-protocol.blade.php`:
`@page margin: 1cm 1cm 1cm 2cm` (левое 2 см, верх/право/низ 1 см).

Ассеты без изменений (новых Tailwind-классов нет). Pint чист.

---

## 2026-07-25 — Правки после тестов (канбан): #217, #118, #185, #206 (+ #186/#193 проверены)

Разбор столбца «Правки после тестов» проекта. #186 (Title) и #193 (Поделиться) уже
закрыты в cf7b282 (комментарии тестировщика предшествовали деплою) — проверены, изменений
не требуют. Остальные исправлены:

**#217 — статус «Завершён» у идущих торгов.** Для коммерческого аукциона этап 1 (RFQ)
закрывается при запуске этапа 2, и в списках/карточке RFQ показывался статус «Завершён»,
хотя торги этапа 2 ещё идут. Добавлен `Rfq::commercialTradingInProgress()` (этап 1 закрыт,
связанный аукцион `active`/`trading`). В `components/rfq-card.blade.php` и `rfqs/show.blade.php`
статус переопределяется на «Идут торги (этап 2)», на странице RFQ добавлена кнопка «Перейти
к торгам (этап 2)». Eager-load `linkedAuction` в `TenderController` (index/myTenders),
`RfqController` (index/show) — против N+1.

**#118 — протокол при отмене/несостоявшемся аукционе.** Путь отмены в
`UpdateAuctionStatuses` всегда вызывал стандартный `AuctionProtocolService` — для
коммерческого аукциона это не тот шаблон (иная структура ставок). Добавлен
`generateCancellationProtocol()`: коммерческий → `CommercialAuctionProtocolService`,
обычный → `AuctionProtocolService`. Формулировки причины приведены к требуемым:
«Аукцион признан несостоявшимся из-за отсутствия поданных предложений» / «Торги признаны
несостоявшимися из-за отсутствия поданных заявок».

**#185 — файлы конкурсной документации теряются при ошибке валидации.** Реализовано
временное хранилище (как для аукционов): партиал `partials/procurement-documents.blade.php`
загружает PDF сразу через AJAX (`procurement-temp-upload.store`/`.destroy`), сохраняет их
в сессии (`temp_procurement_docs`) и восстанавливает после ошибки формы. `TempUploadController`
(+ методы `storeProcurement`/`destroyProcurement`), `ProcurementDocuments` (константы + хелперы
`tempFiles`/`hasTemp`/`tempTotalSize`/`clearTemp`), `ProcurementDocumentsService` прикрепляет
файлы из temp и чистит хранилище, валидация (`ValidatesProcurementDocuments`,
`StoreRfqRequest` — обязательность ТЗ) учитывает temp-файлы и суммарный объём ≤ 20 МБ.

**#206 — одинаковые предложения на этапе 2.** Причина: балл лидера читался из колонки
`total_score` (`decimal(8,4)`, округление), а кандидат считался на полной точности — идентичное
предложение ложно «превосходило» лидера и принималось (и кнопка «Подать» была активна).
`CommercialAuctionScoringService::bestScore()`/`analyze()` теперь пересчитывают балл лидера
из его критериев; аналогично во фронтенде (`commercial-trading.blade.php`, `recalc()`).
Юнит-тест `test_analyze_price_threshold_makes_offer_beat_leader` приведён к согласованному
баллу (37 вместо ошибочно захардкоженного 39), добавлен регресс-тест
`test_identical_offer_rejected_even_if_stored_score_rounded_down`. Максимумы срок/аванс уже
заданы организатором (#210). Тесты зелёные, Pint чист. Vite-ассеты не затрагивались.

---

## 2026-07-25 — Хотфикс #185: не отправлялась форма закупки с документами

**Причина.** В партиале `procurement-documents.blade.php` обработчик выбора файла
сразу очищал нативный `<input type=file>` (`event.target.value=''`) и полагался только
на асинхронную temp-загрузку. Если AJAX-загрузка по какой-либо причине не проходила в
браузере, файл пропадал и из input, и из temp — а для коммерческого аукциона (ТЗ
обязательно) форму становилось невозможно отправить («не даёт разместить аукцион»).

**Фикс.** Одиночные поля (Извещение/ТЗ/Проект договора) больше НЕ очищают input —
файл всегда уходит вместе с формой обычным способом (надёжный путь, как в проверенном
компоненте `x-file-upload`). Temp-загрузка теперь чисто вспомогательная — только для
восстановления файлов после ошибки валидации. `removeSingle()` чистит input через
`x-ref`. На сервере `ProcurementDocumentsService`: «Прочие файлы» берутся из temp, а если
temp пуст — из запроса (без двойного прикрепления); валидация суммарного объёма
(`ValidatesProcurementDocuments`) не учитывает запрос дважды. Добавлены тесты:
`AuctionTest::test_can_create_auction_with_procurement_document_via_direct_upload` и
`RfqTest::test_procurement_temp_upload_preserves_tz_across_submit`.

Файлы: `resources/views/partials/procurement-documents.blade.php`,
`app/Services/ProcurementDocumentsService.php`,
`app/Http/Requests/Concerns/ValidatesProcurementDocuments.php`,
`tests/Feature/AuctionTest.php`, `tests/Feature/RfqTest.php`. Ассеты пересобраны. Pint чист.

---

## 2026-07-26 — Тулинг: обновление скилла «пр» и игнор дампов БД

**Что сделано.** Переписан скилл `.claude/commands/пр.md` под актуальный пайплайн
Bizzio: feature-ветка → PR в `develop` → обязательные CI-проверки (Tests, Pint, Assets
freshness) → merge → авто-деплой на test.bizzio.ru, плюс перенос карточек в колонку
канбана «In review (test)» и раздел «Промоушен на прод» (develop→main с ручным
подтверждением GitHub Environment `production`). Прежняя версия описывала устаревший
прямой push в незащищённый `main` с ручным SSH-деплоем. В `.gitignore` добавлен `/_db_dumps`
(локальные дампы БД не попадают в git).

Файлы: `.claude/commands/пр.md`, `.gitignore`, `docs/CHANGELOG_CLAUDE.md`.
Кода приложения не касалось, ассеты не пересобирались.

---

## 2026-07-28 — chore: перенос прод + тест на новый сервер 159.194.219.159

**Задача:** Мигрировать оба окружения Bizzio (прод + тест) со старого сервера 37.233.82.55 на новый VPS 159.194.219.159 (Ubuntu 24.04). DNS переключает заказчик; перенос выполнен параллельно, старый сервер не тронут.

**Выполнено:**
- Новый сервер: установлены Docker 29.x + compose v5 (get.docker.com) и rsync.
- Каталоги проектов перенесены server-to-server через `rsync` (`/var/www/BIZZIO` 1.1G, `/var/www/bizzio-test` 646M) — вместе с `.env`, `.git`, `vendor`, `public/build`, `storage` (медиа 290M/220M).
- Кастомные образы `bizzio-app` и `bizzio-test-app` перенесены через `docker save | docker load` (без пересборки).
- Базы данных перенесены живым `pg_dump` (без простоя старого прода) и восстановлены: прод 63 польз. / 41 компания, тест 33 / 27 — совпадает со старым сервером.
- **Реконструирован отсутствовавший `docker-compose.override.yml` прода** (на старом сервере файл был удалён с диска, Caddy работал из кэша compose). Он объявляет сервис `caddy` (bizzio-caddy, порты 80/443, монтирование Caddyfile + volumes). Файл untracked, лежит на сервере.
- Стеки подняты (прод первым — создаёт сеть `bizzio_app-network`, к которой внешне подключается тест). Все контейнеры RUNNING (app/db/caddy + test app/db), supervisor: nginx/php-fpm/worker/scheduler RUNNING. Внутренний HTTP обоих приложений — 200, Caddy маршрутизирует оба домена (308 → HTTPS).
- CI/CD: секрет `SSH_HOST` перенаправлен на новый IP; deploy-ключ `github-actions-deploy` установлен в `authorized_keys` нового сервера. `SSH_USER`/`SSH_PRIVATE_KEY` не менялись.

**Изменённые файлы (git):**
- `CLAUDE.md` — IP сервера в разделе CI/CD → 159.194.219.159.
- `docker/nginx.conf` — `server_name` → новый IP.
- `.claude/commands/пр.md` — IP в ручном фолбэк-деплое → новый IP.

**Примечания:**
- Пароль root нового сервера НЕ записан в git (только временно в скретчпаде сессии).
- До переключения DNS Caddy на новом сервере не может получить TLS-сертификаты Let's Encrypt (ACME-челлендж ещё уходит на старый IP) — исправится автоматически после смены DNS.
- Временный ключ доступа new→old удалён из `authorized_keys` старого сервера после переноса.

---

## 2026-07-13 — fix: лимит загрузки PDF/документов поднят с 10 МБ до 20 МБ (relates #185)

**Задача:** Пользователи жаловались, что не могут загрузить большие PDF (ТЗ, документы компании). Валидация Laravel резала на 10 МБ, хотя nginx/PHP допускают 100 МБ, а `TempUploadController` уже разрешал 20 МБ. Issue #185 также требует общий лимит 20 МБ и только PDF (остальная часть #185 — отдельные поля документации, сжатие, скачивание архивом, автоудаление — не входит в этот фикс).

**Изменения (лимит `max:10240` → `max:20480`):**
- `app/Http/Requests/StoreRfqRequest.php`, `UpdateRfqRequest.php` — `technical_specification`
- `app/Http/Requests/StoreAuctionRequest.php`, `UpdateAuctionRequest.php` — `technical_specification`
- `app/Http/Requests/StoreCompanyRequest.php`, `UpdateCompanyRequest.php` — `documents.*`
- `config/media-library.php` — `max_file_size` 10 МБ → 20 МБ (иначе сохранение через Media Library упало бы уже после успешной валидации)

**Тексты сообщений и подсказок «10 МБ» → «20 МБ»:**
- сообщения валидации в перечисленных Request-классах
- `app/Orchid/Screens/RfqEditScreen.php`, `CompanyEditScreen.php` (help)
- `resources/views/auctions/edit.blade.php`, `rfqs/edit.blade.php`
- `resources/views/companies/edit.blade.php`, `companies/create.blade.php`
- `resources/views/components/pdf-documents-input.blade.php`, `file-upload.blade.php`

**Примечание:** локально нет PHP и не запущен контейнер `app` — тесты/Pint не прогонялись; изменения затрагивают только числовые лимиты и текст.

---

## 2026-07-24 — #196 Возвращаем параметр «Шаг аукциона»

**Задача.** Организатор снова задаёт минимальный шаг снижения цены обычного аукциона
(поле `step_percent`, 0.5%–5%). Раньше при создании жёстко ставилось `2.5` (комментарий A4),
а диапазон снижения был захардкожен 0.5%–5% независимо от настроек.

**Изменения:**
- `StoreAuctionRequest` / `UpdateAuctionRequest` — добавлено правило `step_percent`
  (`required|numeric|min:0.5|max:5`) + сообщения об ошибках.
- `AuctionController::store()` — `step_percent` берётся из запроса (было фиксированное `2.5`).
- `Auction::getStepRange()` — минимум = организаторский `step_percent` (fallback 0.5%),
  максимум = 5% от текущей цены (потолок снижения).
- `auctions/create.blade.php`, `auctions/edit.blade.php` — новое поле «Шаг аукциона, %»
  (обязательное, 0.5–5, шаг 0.01); поправлены подписи к начальной цене.
- `auctions/show.blade.php` — в параметрах аукциона показывается фактический минимальный шаг;
  кнопки быстрого снижения строятся от организаторского минимума до 5%.

**Файлы:** `app/Http/Controllers/AuctionController.php`, `app/Http/Requests/StoreAuctionRequest.php`,
`app/Http/Requests/UpdateAuctionRequest.php`, `app/Models/Auction.php`,
`resources/views/auctions/{create,edit,show}.blade.php`, `tests/Feature/AuctionTest.php`.

**Тесты:** `AuctionTest` — добавлена проверка сохранения организаторского шага при создании,
`test_step_range_is_calculated_correctly` → `test_step_range_uses_organizer_step_percent`
(минимум = 2% при `step_percent=2`, максимум = 5%).

---

## 2026-07-29 — fix #226: висящее меню и «залипшие» dropdown'ы после миграции

**Причина (общая).** После миграции сторонний виджет AI-чата
`https://ai-sekretar24.ru/widget.js` стал медленным/недоступным. Скрипт был
**render-blocking** (без `defer`/`async`) и последним в `<body>`, поэтому парсинг HTML
останавливался на нём, а `Alpine.js` (deferred ES-модуль) инициализировался только после
завершения парсинга. Пока виджет висел (на мобильном — минутами), **вся интерактивность на
Alpine была мертва**: не открывались меню (особенно мобильное «Закупки»), не работали
формы. Это же ломало восприятие подачи заявок в закупках/аукционах.

**Фиксы:**
1. `resources/views/layouts/app.blade.php` — к скрипту виджета добавлен `defer`
   (убран из критического пути парсинга; недоступный хост больше не морозит страницу).
2. `resources/css/app.css` — добавлено правило `[x-cloak]{display:none!important}`.
   Alpine НЕ добавляет его сам, а в проекте его не было (проверено: 0 вхождений в
   собранном `public/build`), поэтому элементы с `x-cloak` (в т.ч. выпадающий поиск
   «Пригласить компании» на форме `rfqs/create?procedure=commercial`) были видны до старта
   Alpine и «залипали» открытыми. Ассеты пересобраны (`npm run build`).

**Баг #226.2 (открытый ком-аукцион — «участвовать могут только приглашённые / только первая
компания»).** Изменений в логике НЕ вносилось: код уже реализует подтверждённое поведение —
этап 1 открыт всем верифицированным компаниям (`RfqController::storeBid`, ветка `type==='open'`),
на этап 2 проходят только участники этапа 1, НМЦ = средняя цена этапа 1 (#202); в реал-тайм
торгах предложение должно строго побеждать лидера. Симптом «нельзя подать заявку» —
следствие мёртвого Alpine (баг #226.1). Требуется повторная проверка тестировщиком после
деплоя фикса.

**Файлы:** `resources/views/layouts/app.blade.php`, `resources/css/app.css`,
`public/build/*` (пересобрано).

---

## 2026-07-29 — fix #226 (продолжение): defer у виджета также в guest-лейауте и на welcome

Первый фикс #226 добавил `defer` только в `layouts/app.blade.php`. Виджет AI-чата
дублируется ещё в двух местах, которые остались render-blocking:
- `resources/views/layouts/guest.blade.php` (страницы входа/регистрации),
- `resources/views/welcome.blade.php` (публичная посадочная `bizzio.ru/`).

Обнаружено при проверке живого прода: `curl https://bizzio.ru/` отдавал `<script>` виджета
без `defer`. Добавлен `defer` во все три места (проверено: не осталось ни одного
недеферренного вхождения). Ассеты не менялись.

---

## 2026-07-30 — fix #231, #233 (правки после тестов канбана)

Тестировщик завёл три бага по коммерческому закрытому аукциону №71 (#231, #232, #233).
Воспроизведение — фичетестами против SQLite (как в CI), контейнер `my_project_app`.

**#231 — «Мои заявки» (`/my-bids-all`) → 500.**
Причина: `Rfq`/`Auction` используют `SoftDeletes`, а заявки/ставки участника — нет.
После мягкого удаления тендера заявка остаётся сиротой: `$bid->rfq` (или `$bid->auction`)
возвращает `null`, и вью падает на `route('rfqs.show', null)` —
`Missing required parameter for [Route: rfqs.show]`. Подтверждено тестом.
Фикс: в `TenderController::myBids()` добавлены `whereHas('rfq')` / `whereHas('auction')`
(уважают SoftDeletes) — сироты-заявки скрываются. Тот же защитный `whereHas('auction')`
добавлен в `myInvitations()` (аукционные приглашения; у RFQ-приглашений он уже был).

**#233 — дублирование коммерческой процедуры в «Мои закупки» и «Тендерах».**
Причина: коммерческая процедура — это пара `Rfq` (этап 1) + `Auction` (этап 2), обе
привязаны к одной компании и попадали в списки как две карточки. Фикс: приватный хелпер
`hideLaunchedCommercialRfqs()` скрывает этап-1 RFQ после запуска этапа 2
(`procedure='commercial' AND linked_auction_id IS NOT NULL`); применён в `index()` и
`myTenders()`. Незапущенные коммерческие RFQ (идёт приём заявок этапа 1) остаются видимы.

**#232 — «не даёт ввести первое предложение на этапе 2» (закрытый + скрытые результаты).**
Счастливый путь воспроизвести НЕ удалось: закрытый коммерческий аукцион со скрытыми
результатами корректно показывает страницу торгов, отдаёт state и принимает первое
предложение (5/5 тестов зелёные, в т.ч. `test_232_closed_hidden_first_offer_accepted`).
Значит корень — в данных аукциона №71 либо в сценарии (участник не первый / скрытый
лидер). Требуется точный текст ошибки от тестировщика. Пока НЕ исправлено.

**Файлы:** `app/Http/Controllers/TenderController.php`,
`tests/Feature/BoardFixes231233Test.php` (новый, 5 тестов).
Прогон: новые 5/5 ✓, `CommercialAuctionTest`+`AuctionTest` 72/72 ✓, Pint ✓.

---

## 2026-07-30 — fix #232: скрытые результаты в коммерческом аукционе (этап 2)

Тестировщик: «не даёт ввести первое предложение на этапе 2» в закрытом коммерческом
аукционе №71 со скрытыми результатами; на экране — красный баннер «не хватает баллов».

**Диагноз.** Не техническая поломка (happy-path зелёный), а противоречие двух механик:
коммерческий аукцион работает по модели «непрерывного лидерства» (подать можно только
предложение, СТРОГО превосходящее текущего лидера), а при скрытых результатах участник
не видит лидера — не знает, что превзойти, и упирается в «не хватает баллов». Причём
`commercialState` вовсе игнорировал `is_results_hidden` и всегда отдавал детали лидера +
историю (скрытие де-факто не работало).

**Решение (вариант согласован с заказчиком).** Скрываем детали конкурента, но отдаём
числовой ориентир — «балл для лидерства»:
- `AuctionController::commercialState()` — при `is_results_hidden` и если смотрит НЕ
  организатор (`! canManage`): `best_offer = null`, `best_offer_history = []`; добавлены поля
  `results_hidden` и `best_score` (полноточный целевой балл лидера, `null` если лидера нет).
  Организатор по-прежнему видит всё.
- `resources/views/auctions/partials/commercial-trading.blade.php` — клиент считает
  `wouldBeat`/`deficit` по серверному `best_score` (а не по цифрам лидера); при скрытых
  результатах не пересеивает поля значениями лидера (`seedFromBest` отключён), панель лидера
  показывает «Результаты скрыты» + целевой рейтинг, таблица истории заменяется плашкой.
- Модель лидерства сохранена: сервер (`storeOffer` → `wouldBeat`) по-прежнему принимает
  только строго лучшее предложение.

**Файлы:** `app/Http/Controllers/AuctionController.php`,
`resources/views/auctions/partials/commercial-trading.blade.php`,
`tests/Feature/CommercialHiddenResultsTest.php` (новый, 4 теста).
Прогон: `CommercialHiddenResultsTest`+`CommercialAuctionTest` 24/24 ✓, Pint ✓,
ассеты не изменились (новых классов в сборке нет).

---

## 2026-07-30 — fix #232 (UX): явное сообщение организатору в коммерческом аукционе

Проверка на проде показала: аукцион №71 работает штатно — приглашённые участники
(проверено под их аккаунтами) подают первое предложение без проблем, `state` отдаёт
корректный JSON. Тестировщик заходил под аккаунтом **организатора** (msverlov@mail.ru,
компания-организатор), который по правилам не может участвовать в собственном аукционе —
форма скрывалась, показывалось обезличенное «только участники», что читалось как поломка.

Правка: в `resources/views/auctions/partials/commercial-trading.blade.php` заголовок
«Настройка предложения» показываем только когда форма реально доступна; организатору
(`$auction->canManage()`) выводим явное: «Вы организатор этой процедуры… участие
организатора в собственном аукционе недоступно».

**Файлы:** `resources/views/auctions/partials/commercial-trading.blade.php`,
`tests/Feature/CommercialHiddenResultsTest.php` (+2 теста). Прогон 6/6 ✓, ассеты не изменились.

---

## 2026-07-31 — fix #232 (реальный корень): HTML5 step-валидация блокировала подачу предложения

Долгоиграющий баг «не получается сделать первую ставку» в коммерческом аукционе. Сервер,
форма, `action` и `wouldBeat` были корректны — воспроизвести серверными тестами не удавалось.

**Найдено реальным браузером** (Playwright + системный Chrome против test.bizzio.ru, логин
участником): `form.checkValidity() = false`. Поле «Ваш срок» — `<input type="number" min="1"
:step="steps.d">`, дефолт `d = max_deadline`. При `min=1` база шага нечётная, а `max_deadline`
чётный (30/90/100) + чётный `step_deadline` → значение не кратно шагу → HTML5 constraint
validation. `requestSubmit()`/клик по кнопке молча блокируются (submit-событие не диспатчится),
пользователь видит «ничего не происходит». Ручной `fetch` POST на `/offers` при этом проходил —
подтверждая, что бэкенд исправен.

**Фикс:** в `commercial-trading.blade.php` у видимых number/range полей срок/аванс HTML-атрибут
`step` сделан мягким — срок `step="1"` (любое целое), аванс `step="any"`. Кнопки −/+ по-прежнему
шагают организаторским шагом через `stepDeadline()`/`stepAdvance()` (Alpine). Диапазон min/max
сохранён; сервер валидирует значения как раньше.

**Файлы:** `resources/views/auctions/partials/commercial-trading.blade.php`,
`tests/Feature/CommercialHiddenResultsTest.php` (регресс `test_offer_form_inputs_do_not_use_hard_step_binding`).
Прогон CommercialHiddenResultsTest 7/7. Диагностика — через реальный браузер (задел под #238).

---

## 2026-07-31 — #232 UX: покритериальные подсказки при скрытых результатах

Продолжение разбора аукциона 76. Скоринг корректен (проверено на проде: превосходящее
предложение принимается, умеренное — отклоняется). Причина «не хватает баллов» — лидер
демпингует (цена = 1 ₽, рейтинг 78.67), а при скрытых результатах участник видел только
целевой балл, но не понимал, что нужно резко снизить цену. По решению заказчика правила
закупки не меняем (демпинг — честная конкуренция), но добавляем UX-подсказки.

Правка: в `commercial-trading.blade.php` `hintText()` больше не требует видимого лидера —
покритериальный порог «Уменьшите до X» показывается при наличии целевого балла (в т.ч. при
скрытых результатах). X считается от целевого балла и НЕ раскрывает цифры лидера.

**Файлы:** `resources/views/auctions/partials/commercial-trading.blade.php`. Тесты 7/7.

---

## 2026-07-31 — fix: старт этапа 2 по trading_start (#222) + участники видят торги (#237)

Два бага по коммерческому аукциону (Mike, аукцион 83).

**#222 — торги этапа 2 стартовали сразу по завершении приёма заявок, а не в назначенное время.**
`CommercialAuctionLauncherService` ставил статус `trading` немедленно при закрытии RFQ, игнорируя
`trading_start`. Теперь: если время начала торгов ещё не наступило — аукцион создаётся в `active`
(ожидание, приёма заявок нет), а `UpdateAuctionStatuses` переводит его в `trading` в назначенное
время (новый блок 1b, без проверки заявок — участники известны с этапа 1). Если время уже прошло —
стартуем сразу. UI: статус «Ожидание начала торгов» + обратный отсчёт до `trading_start`
(show.blade, auction-card).

**#237 — скрытые результаты прятали ход торгов даже от участников.** Прежний #232-фикс скрывал
лидера/историю от всех, кроме организатора. Правильно: скрывать только от ПОСТОРОННИХ. Добавлены
`Auction::isParticipant()` и `Auction::resultsHiddenFor()`; `commercialState` и партиал используют
их. Теперь организатор и участники этапа 2 видят ход торгов (коды анонимны), а посторонний на
открытом аукционе видит лишь целевой балл.

**Файлы:** `app/Services/CommercialAuctionLauncherService.php`, `app/Jobs/UpdateAuctionStatuses.php`,
`app/Models/Auction.php`, `app/Http/Controllers/AuctionController.php`,
`resources/views/auctions/show.blade.php`, `resources/views/components/auction-card.blade.php`,
`resources/views/auctions/partials/commercial-trading.blade.php`,
`tests/Feature/CommercialAuctionTest.php`, `tests/Feature/CommercialHiddenResultsTest.php`.
Прогон: CommercialAuctionTest+CommercialHiddenResultsTest+AuctionTest 82/82. Pint чист.

---

## 2026-07-31 — hotfix: планировщик отменял коммерческий этап 2 (регресс #222)

Регресс предыдущего фикса #222. Существуют ДВЕ реализации обновления статусов:
`App\Jobs\UpdateAuctionStatuses` (джоба) и `App\Console\Commands\UpdateAuctionStatuses`
(команда `auctions:update-statuses`, её гоняет планировщик каждую минуту). Фикс #222 добавил
обработку коммерческого 'active'→'trading' только в ДЖОБУ. Команда же дублировала логику и
в блоке 1 НЕ исключала коммерческие: при наступлении trading_start видела 'active' + 0
initialBids (у этапа 2 их нет — участники с этапа 1) и ОТМЕНЯЛА аукцион. Симптом (Mike):
«этап 2 не стартует, аукцион переходит в завершён спустя минуту, окно торгов не открывается».

Фикс: команда `auctions:update-statuses` теперь делегирует всю логику единственной реализации —
`App\Jobs\UpdateAuctionStatuses` (устранено дублирование, фиксы больше не разойдутся). Джоба
дополнительно формирует протокол при отмене — команда раньше этого не делала.

**Файлы:** `app/Console/Commands/UpdateAuctionStatuses.php`, `tests/Feature/CommercialAuctionTest.php`
(регресс `test_scheduler_command_starts_commercial_stage2_and_does_not_cancel_it`). Тесты 23/23.

---

## 2026-08-01 — fix: ложный лимит при перезагрузке файла + авто-открытие торгов этапа 2

Два бага (Mike, аукцион 85).

**Файловая ошибка «Общий объём…» при повторной загрузке.** В `TempUploadController::storeProcurement`
проверка суммарного объёма учитывала прежний temp-файл ОДИНОЧНОЙ коллекции (Извещение/ТЗ/Проект
договора), который тут же заменяется. Перезагрузка того же файла (>10 МБ) ложно упиралась в 20 МБ —
приходилось удалять и прикладывать заново. Фикс: при замене одиночной коллекции исключаем её прежний
размер из суммы. «Прочие файлы» (накапливаются) — кумулятивный лимит сохранён.

**Торги этапа 2 открывались с задержкой и требовали ручного рефреша.** Планировщик переводит
'active'→'trading' раз в минуту, а страница ожидания не обновлялась сама. Фикс: (1) ленивый переход в
`AuctionController::show()` — если `trading_start` уже наступил, стартуем торги прямо при заходе; (2) на
странице ожидания — авто-перезагрузка при наступлении времени начала (открывается окно торгов без
ручного рефреша).

**Файлы:** `app/Http/Controllers/TempUploadController.php`, `app/Http/Controllers/AuctionController.php`,
`resources/views/auctions/show.blade.php`, `tests/Feature/ProcurementTempUploadSizeTest.php` (новый, 3 теста).
Прогон: ProcurementTempUploadSizeTest 3/3, AuctionTest+CommercialAuctionTest 75/75. Pint чист, ассеты без изменений.

---

## 2026-08-01 — fix: аванс в торгах показывал «кучу цифр» после запятой

Mike: значение аванса выводилось с длинным хвостом дробей. Причина — ранее для поля аванса
поставили `step="any"` (чтобы обойти HTML5-валидацию), и слайдер при перетаскивании давал
непрерывный float (напр. 43.2857142857 %). Фикс: слайдер/поле аванса → `step="0.01"` (2 знака;
max_advance — decimal(5,2), кратен 0.01, поэтому баг валидации не возвращается) + округление
вывода аванса в панели лидера и истории через round2(). Кнопки −/+ по-прежнему шагают
организаторским шагом. Сохранённые значения и так были decimal(5,2) — правка чисто в живом UI.

**Файлы:** `resources/views/auctions/partials/commercial-trading.blade.php`,
`tests/Feature/CommercialHiddenResultsTest.php` (тест дополнен: нет `step="any"`, есть `step="0.01"`).
Прогон CommercialHiddenResultsTest 8/8. Ассеты без изменений.

---

## 2026-08-03 — feat #216: полноценное редактирование черновика Запроса цен / коммерческого аукциона

**Задача:** в черновике коммерческого аукциона редактировалась лишь часть полей (название, описание,
дата окончания, документация, скрытие результатов). Организатор не мог поправить шаги, максимумы,
веса, время начала торгов — приходилось удалять черновик и заводить заново.

**Сделано:** форма редактирования получила весь набор полей формы создания: тип процедуры
(открытая/закрытая), валюта, дата начала приёма, дата окончания, начало торгов (этап 2), веса трёх
критериев (с подсветкой суммы ≠ 100 %), шаги цены/срока/аванса и максимумы срока/аванса (#210).
Блок этапа 2 показывается только для `procedure=commercial`. Компания-организатор выводится
read-only — смена организатора ломала бы приглашения и права.

**Попутно закрыта дыра в авторизации.** `RfqPolicy::update` разрешает правки только черновику
(страница `/rfqs/{id}/edit` для активного RFQ отдаёт 403), но `UpdateRfqRequest::authorize()`
проверял лишь `canManage()` — прямой PUT правил активный RFQ в обход политики. Теперь request
использует ту же политику (`$user->can('update', $rfq)`), ветка правил «для не-черновика» удалена
как недостижимая.

**Валидация (черновик):** `end_date after start_date`, `trading_start after end_date`, сумма весов
= 100 %, шаги/максимумы в тех же границах, что и при создании; параметры этапа 2 требуются только
у коммерческой процедуры.

**Файлы:** `resources/views/rfqs/edit.blade.php` (переписан), `app/Http/Requests/UpdateRfqRequest.php`,
`tests/Feature/CommercialAuctionTest.php` (+6 тестов).
Прогон: весь набор 342/342. Pint чист, ассеты без изменений.

**Грабли со сборкой ассетов.** `npm run build` на «грязном» кэше вью
(`storage/framework/views/*.php`) дал новый хэш CSS — Tailwind сканирует этот кэш, и в сборку
попали классы из скомпилированных вью прошлых версий. После `artisan view:clear` сборка вернулась
к исходному хэшу. Вывод: перед проверкой Assets freshness всегда чистим кэш вью, иначе
закоммитим ложный дифф `public/build`.

---

## 2026-08-03 — feat #237: результаты коммерческого аукциона скрыты по умолчанию

**Задача:** у коммерческих аукционов результаты должны быть скрыты всегда — их видят только
администраторы/модераторы компании-организатора и компаний-участников. Галочку «Скрыть результаты»
из формы убрать.

**Сделано:**
- `RfqController::store` — при `procedure=commercial` флаг `is_results_hidden` ставится принудительно.
- `UpdateRfqRequest::prepareForValidation` — у коммерческой процедуры флаг нельзя снять даже
  подделанным запросом (галочки в форме нет, поэтому отсутствие поля не должно раскрывать торги).
- `CommercialAuctionLauncherService` — этап 2 создаётся со скрытыми результатами всегда (раньше
  копировал флаг с этапа 1).
- Формы создания и редактирования: галочка показывается только для обычного Запроса цен, для
  коммерческого — поясняющая строка.
- `AuctionController::show` — проверка видимости итогов переведена на `Auction::resultsHiddenFor()`.
  Раньше участником считался только тот, кто подал ставку; теперь — приглашённая компания. Это важно
  для этапа 2: участники приходят с этапа 1 приглашениями, но торговать могли не все, и «молчавший»
  участник итогов не видел.
- Миграция `2026_08_03_120000_hide_results_for_commercial_procedures` — проставляет флаг уже
  созданным коммерческим RFQ и аукционам.

**Файлы:** `app/Http/Controllers/RfqController.php`, `app/Http/Controllers/AuctionController.php`,
`app/Http/Requests/UpdateRfqRequest.php`, `app/Services/CommercialAuctionLauncherService.php`,
`resources/views/rfqs/create.blade.php`, `resources/views/rfqs/edit.blade.php`,
`database/migrations/2026_08_03_120000_hide_results_for_commercial_procedures.php`,
`tests/Feature/CommercialHiddenResultsTest.php` (+5 тестов).
Прогон: весь набор 347/347. Pint чист, ассеты без изменений.

---

## 2026-08-03 — feat #222: предустановка времени этапов коммерческого аукциона

**Задача:** организатор каждый раз вбивал три даты вручную. Нужны разумные значения по умолчанию.

**Сделано:** форма создания коммерческого аукциона (`/rfqs/create?procedure=commercial`) предзаполняет:
начало приёма предложений — текущее время + 1 час (с округлением до минуты), окончание приёма —
начало + 24 часа, начало торгов этапа 2 — окончание приёма + 10 минут. Значения редактируются,
после ошибки валидации подставляется введённое (`old()`), обычный Запрос цен по-прежнему открывается
с пустыми датами. Под полями — подсказки, что это предзаполнение и его можно изменить.

**Файлы:** `resources/views/rfqs/create.blade.php`, `tests/Feature/CommercialAuctionTest.php` (+2 теста).
Прогон: весь набор 349/349. Pint чист, ассеты без изменений.

---

## 2026-08-04 — fix: торги этапа 2 — самоперебивание, шаг критериев, непонятные сообщения, код участника

По итогам живых торгов на bizzio.ru/auctions/86 (обратная связь Mike, 5 замечаний) — issue #257.

**1. Участник перебивал сам себя (предложения 6 и 7 подряд от одной компании).** `storeOffer`
проверял только строгое превосходство над лидером, но не то, кто этот лидер. Теперь, если
лидирует та же компания, предложение отклоняется: перебивать себя нельзя, надо дождаться
конкурента. В UI форма при этом блокируется с явным пояснением.

**2. Заданный шаг критериев не учитывался — можно было ввести аванс 43,7 %.** Шаг применялся
только кнопками −/+, а сервер его не проверял вовсе. По решению заказчика принята семантика
«шаг = минимальное улучшение»: критерий можно оставить как у лидера или ухудшить (компенсируя
другими), но если участник его улучшает — не меньше чем на один шаг. Реализовано в
`CommercialAuctionScoringService::stepViolation()`, зеркальная проверка в Alpine (`checkSteps()`)
блокирует кнопку и показывает, до какого значения нужно улучшить. Первое предложение (лидера нет)
шагом не ограничивается.

**3. «До лучшего значения не хватает 0 баллов», хотя ставка принималась.** Сообщение было
единственной ветвью отказа и показывалось в двух разных ситуациях, где дефицит округляется в ноль:
поля предзаполнены цифрами лидера (ничего не изменили) и лидируете вы сами. Разведено на четыре
состояния: вы лидируете / улучшение меньше шага / совпадает с лучшим / не хватает N баллов.

**4. Аванс при входе в торги показывался ниже начального.** Это не сбой: поля предзаполняются
значениями текущего лидера (#206), чтобы участник улучшал от лучшего предложения, а не от максимума.
Из-за пункта 2 у лидера могло стоять произвольное дробное значение — оно попадало в поле «как есть».
Добавлено округление при подстановке; после включения проверки шага произвольные значения не пройдут.

**5. Код участника перенесён к истории предложений.** Раньше он показывался только во всплывающем
сообщении после подачи. Теперь — плашка над таблицей истории, свои строки выделены цветом,
левой полосой и меткой «вы».

**Файлы:** `app/Services/CommercialAuctionScoringService.php` (+`priceStep`, `steps`, `stepViolation`),
`app/Http/Controllers/AuctionController.php` (`storeOffer`),
`resources/views/auctions/partials/commercial-trading.blade.php`,
`tests/Feature/CommercialHiddenResultsTest.php` (+7 тестов), `public/build/*` (новые Tailwind-классы).
Прогон: весь набор 356/356. Pint чист.

---

## 2026-08-04 — fix #259: этап 1 — сбой таймера и отсутствие кнопки подачи до начала приёма

**Причина.** После предустановки времени этапов (#222) новая процедура публикуется со стартом через
час, то есть «активна, но приём ещё не начался» — теперь это состояние по умолчанию. На странице
запроса цен оно не обрабатывалось:
- таймер считал «До окончания приёма предложений», хотя приём ещё не начинался;
- форма подачи появляется только после `start_date` (`Rfq::isActive()`), но пояснения, почему её
  нет и когда она появится, не было — выглядело как пропавшая кнопка.

На странице аукциона это состояние обработано корректно с #189 (таймер до начала + сообщение
«Приём заявок начнётся …»); на странице запроса цен аналога не было.

**Сделано:** до начала приёма показывается таймер «До начала приёма предложений (этап 1)» вместо
таймера до окончания, плюс блок «Приём предложений начнётся ДД.ММ.ГГГГ в ЧЧ:ММ (МСК)». Партиал
`partials/countdown` получил параметр `reload` — в момент наступления события страница
перезагружается сама, и форма подачи открывается без ручного обновления.

**Файлы:** `resources/views/rfqs/show.blade.php`, `resources/views/partials/countdown.blade.php`,
`tests/Feature/CommercialAuctionTest.php` (+2 теста).
Прогон: весь набор 358/358. Pint чист, ассеты без изменений.

---

## 2026-08-05 — fix #261: шаг цены расходился на копейку, шаг аванса сбивался на дробные значения

**1. Одно нажатие «−» по цене отклонялось сервером.** У аукциона 74 (тест) НМЦ 388 888,50 и шаг 5 %,
то есть 19 444,425. PHP `round(...,2)` даёт 19 444,43, а JS `toFixed(2)` — 19 444,42: клиент считал
шаг сам и снижал цену на копейку меньше, чем требовала серверная проверка. Приходилось нажимать «−»
дважды. Вторым слоем — сравнение на float: `$step - $tolerance` в двоичном виде оказывалось чуть
больше фактической разности цен, и проверка срабатывала даже при совпадающих числах.

Фикс: абсолютный шаг цены считает сервер и передаёт в компонент (`priceStepAbs`), клиент больше его
не пересчитывает. Сравнение в `stepViolation()` переведено в целые единицы последнего разряда
(копейки / сотые процента / дни) — дребезга float нет вовсе. Допуск в одну копейку оставлен как
страховка от расхождений округления, для срока (целые дни) допуск нулевой.

**2. Сбивался шаг аванса — появлялись десятые доли.** Слайдер и поле допускали произвольное значение
с точностью 0,01, шаг применялся только кнопками −/+. Добавлена привязка к сетке шага, отсчитанной
от значения лидера (до первого предложения — от организаторского максимума): слайдер шагает сеткой
при перетаскивании, ручной ввод приводится к сетке при уходе из поля (во время набора не мешаем).
То же для срока.

**Файлы:** `app/Services/CommercialAuctionScoringService.php`,
`resources/views/auctions/partials/commercial-trading.blade.php`,
`tests/Feature/CommercialHiddenResultsTest.php` (+3 теста, включая числа аукциона 74).
Прогон: весь набор 361/361. Pint чист, ассеты без изменений.

**Грабли.** Денежные сравнения с допуском на float ненадёжны в обе стороны: и `round()` перед
сравнением не спасает. Сравнивать суммы следует в целых минимальных единицах (копейках).

---

## 2026-08-05 — fix #256: строка «Шаг снижения» убрана у коммерческого аукциона

В параметрах аукциона выводилось «Шаг снижения — X% — 5% от текущей цены». У коммерческого
аукциона это поле (`step_percent`) не применяется вовсе: там три отдельных шага (цена/срок/аванс),
которые задаёт организатор и которые видны в панели торгов. На проде у аукциона 86 показывалось
«1% — 5%» — значение по умолчанию, не имеющее отношения к его настоящим шагам (5 % / 2 дн. / 10 %).

Строка скрыта для коммерческой процедуры. У обычного аукциона она остаётся: там диапазон реален —
минимум задаёт организатор, максимум 5 % зашит в `Auction::getStepRange()` и проверяется валидацией
ставки.

**Файлы:** `resources/views/auctions/show.blade.php`,
`tests/Feature/CommercialHiddenResultsTest.php` (+1 тест).
Прогон: весь набор 362/362. Pint чист, ассеты без изменений.

---

## 2026-08-06 — feat #195: уведомления о начале этапов коммерческого аукциона

**Задача:** уведомлять администраторов и модераторов приглашённых компаний в момент начала приёма
предложений (этап 1) и пользователей, подавших предложения, — за 30 минут до начала торгов (этап 2).

**Сделано:** команда `commercial:notify-stages` (в планировщике каждую минуту, `withoutOverlapping`)
и два уведомления (`database` + `mail`):
- `CommercialStageOneStartedNotification` — при наступлении `start_date` активной коммерческой
  процедуры уходит модераторам всех приглашённых компаний;
- `CommercialStageTwoSoonNotification` — когда до `trading_start` осталось не больше 30 минут,
  уходит пользователям, подавшим предложения на этапе 1.

**Почему отметки на запросе цен, а не на аукционе.** Аукцион этапа 2 рождается только при закрытии
этапа 1, а по умолчанию (#222) торги начинаются через 10 минут после окончания приёма — то есть
момент «за 30 минут» наступает, когда аукциона ещё не существует. Поэтому и получатели, и время
берутся с запроса цен, а ссылка ведёт на аукцион, если он уже создан, иначе на страницу этапа 1
(она сама откроет торги при старте, #205).

Повторные запуски безопасны: отметки `rfqs.stage1_notified_at` / `stage2_notified_at`. Миграция
проставляет их существующим процедурам, у которых событие уже прошло, — чтобы при выкатке не ушла
пачка уведомлений задним числом.

**Файлы:** `app/Console/Commands/NotifyCommercialStages.php`,
`app/Notifications/CommercialStageOneStartedNotification.php`,
`app/Notifications/CommercialStageTwoSoonNotification.php`, `bootstrap/app.php`, `app/Models/Rfq.php`,
`database/migrations/2026_08_06_100000_add_stage_notification_marks_to_rfqs_table.php`,
`CLAUDE.md`, `tests/Feature/CommercialStageNotificationsTest.php` (новый, 9 тестов).
Прогон: весь набор 371/371. Pint чист, ассеты без изменений.

---

## 2026-08-07 — fix #193: блок «Поделиться» пропадал после публикации процедуры

**Замечание:** «В ком аукционе блок Поделиться пропадает с момента начала приёма предложений по
этапу 1. Необходимо сохранить блок актуальным до окончания Этапа 1 и начала Этапа 2.»

**Причина.** На странице запроса цен оба блока — «Поделиться» (копирование ссылки) и «Пригласить
стороннюю компанию» (готовый текст #193) — были обёрнуты в `@can('update', $rfq)`, а `RfqPolicy::update`
разрешает правки только черновику. С публикацией процедуры блоки исчезали — ровно тогда, когда
приглашать участников и нужно. На странице аукциона использовался `canManage`, поэтому там блок
не пропадал: дефект был только у этапа 1.

**Сделано.** Видимость вынесена в `Rfq::canBeSharedBy()` / `Auction::canBeSharedBy()`:
- организатор видит блок с черновика и до окончания приёма (у коммерческой процедуры — до конца этапа 1);
- остальные авторизованные пользователи — только у открытой процедуры, идущей сейчас (по комментарию
  в задаче: «может быть доступна другим пользователям, но только если аукцион открытый»);
- у аукциона этапа 2 коммерческой процедуры блока нет вовсе: состав участников зафиксирован на
  этапе 1, приглашать со стороны некого;
- после закрытия/отмены и с началом торгов блок скрывается — новых участников уже не привлечь;
- гостям блок не показывается.

Заголовок блока копирования ссылки «Поделиться RFQ» переименован в «Поделиться» — он общий для
запроса цен и коммерческого аукциона.

**Файлы:** `app/Models/Rfq.php`, `app/Models/Auction.php`, `resources/views/rfqs/show.blade.php`,
`resources/views/auctions/show.blade.php`, `tests/Feature/ShareInviteVisibilityTest.php` (новый, 10 тестов).
Прогон: весь набор 381/381. Pint чист, ассеты без изменений.

---

## 2026-08-07 — feat #187: модератор компании может редактировать профиль

**Задача:** модератор компании должен редактировать профиль компании, включая фото и документы;
сейчас это доступно только администратору.

**Что нашлось.** Права были перекошены в обе стороны:
- кнопка «Редактировать» на странице компании была закрыта проверкой `canManageModerators()` —
  её видели только создатель и участник с флагом `can_manage_moderators`, то есть модератор
  до формы не добирался;
- зато бэкенд (`CompanyController::edit/update`, загрузка и удаление фото, удаление документов) и
  блоки управления фото опирались на `isModerator()`, которая истинна для ЛЮБОЙ записи в
  `company_user` — включая рядового «Участника» (роль `member` по умолчанию с #144). Участник мог
  править профиль, заливать и удалять фото по прямой ссылке, просто не видя кнопки.

**Сделано.** Введено отдельное право `Company::canEditProfile()`: создатель, администратор
платформы и участники с ролью `owner` / `admin` / `moderator`; рядовой `member` — нет. Применено
единообразно: кнопка на странице компании, форма редактирования, сохранение (`UpdateCompanyRequest`),
загрузка и удаление фото, удаление документов. Управление составом участников осталось за более
узким `canManageModerators()` — это другое право.

**Файлы:** `app/Models/Company.php`, `app/Http/Controllers/CompanyController.php`,
`app/Http/Requests/UpdateCompanyRequest.php`, `resources/views/companies/show.blade.php`,
`tests/Feature/CompanyProfileEditRightsTest.php` (новый, 11 тестов).
Прогон: весь набор 392/392. Pint чист, ассеты без изменений.

## #269 / #270 / #271 — Отображение параметров торгов коммерческого аукциона (2026-08-10)

**#269** НМЦ и другие суммы рвались посреди цифр при переносе строки. Добавлен общий blade-компонент `<x-money>`: разряды и символ валюты соединены неразрывными пробелами + `whitespace-nowrap`. Применён во всех местах вывода сумм (аукционы, запросы цен, мои заявки, дашборд, карточки). Формат цены, приходящей поллингом (`current_price_formatted`), приведён к тому же виду.

**#270** Шаги изменения цены/срока/аванса и максимумы срока/аванса, заданные организатором, теперь показываются явно — на этапе 1 (страница Запроса цен) и во время торгов (страница Аукциона + панель торгов). Новый партиал `partials/commercial-stage2-parameters.blade.php`.

**#271** В таблице предложений рядом с каждым критерием выводится процент изменения относительно стартового ориентира (НМЦ / макс. срок / макс. аванс): снижение зелёным со знаком «−», рост красным «+».

**Изменённые файлы:**
- `resources/views/components/money.blade.php` (новый), `resources/views/partials/commercial-stage2-parameters.blade.php` (новый)
- `resources/views/auctions/show.blade.php`, `resources/views/auctions/partials/commercial-trading.blade.php`, `resources/views/auctions/my-bids.blade.php`, `resources/views/components/auction-card.blade.php`
- `resources/views/rfqs/show.blade.php`, `resources/views/rfqs/my-bids.blade.php`, `resources/views/tenders/my-bids.blade.php`, `resources/views/partials/dashboard/bids-widget.blade.php`
- `app/Http/Controllers/AuctionController.php` — формат `current_price_formatted`
- `tests/Feature/AuctionTradingDisplayTest.php` — новый тест (4 кейса)
