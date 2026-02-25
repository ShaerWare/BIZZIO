# Админ-панель Orchid

URL: `/admin`

Платформа: [Orchid](https://orchid.software/) — пакет для Laravel.

## Доступ

Guard: `web` (стандартный)

Middleware: `web`, `platform`, cache headers

Роли:
- **admin** — полный доступ (platform.index, platform.systems.roles, platform.systems.users)
- **moderator** — только просмотр (platform.index)

## Экраны (Screens)

### Управление компаниями

| Экран | Описание |
|-------|----------|
| CompanyListScreen | Таблица компаний с пагинацией. Колонки: ID, название, ИНН, отрасль, верифицирована, создатель |
| CompanyEditScreen | Форма редактирования: название, slug, ИНН, правовая форма, описания, отрасль, верификация, медиа |

### Управление проектами

| Экран | Описание |
|-------|----------|
| ProjectListScreen | Таблица проектов. Фильтры: поиск, статус, компания |
| ProjectEditScreen | Форма: название, описание, даты, статус, компания-владелец |

### Управление RFQ

| Экран | Описание |
|-------|----------|
| RfqListScreen | Таблица RFQ с количеством заявок |
| RfqEditScreen | Форма: название, описание, тип, валюта, даты, веса критериев |

### Управление аукционами

| Экран | Описание |
|-------|----------|
| AuctionListScreen | Таблица аукционов со статусами |
| AuctionEditScreen | Форма: название, описание, тип, валюта, даты, цена, шаг |

### Управление новостями и RSS

| Экран | Описание |
|-------|----------|
| NewsListScreen | Таблица новостей с источником и датой публикации |
| RSSSourceListScreen | Таблица RSS-источников |
| RSSSourceEditScreen | Форма: название, URL, включён, интервал парсинга |

### Управление пользователями

| Экран | Описание |
|-------|----------|
| UserListScreen | Таблица пользователей с фильтрами |
| UserEditScreen | Форма: имя, email, пароль, роли |
| UserProfileScreen | Профиль текущего пользователя |

### Управление ролями

| Экран | Описание |
|-------|----------|
| RoleListScreen | Таблица ролей |
| RoleEditScreen | Форма: название, slug, права доступа |

## Layouts

### Таблицы (List Layouts)

| Layout | Описание |
|--------|----------|
| UserListLayout | Таблица пользователей: ID, имя, email, дата |
| RoleListLayout | Таблица ролей: ID, название, slug |

### Формы (Edit Layouts)

| Layout | Описание |
|--------|----------|
| UserEditLayout | Поля: имя, email |
| UserPasswordLayout | Смена пароля пользователя |
| UserRoleLayout | Назначение ролей |
| ProfilePasswordLayout | Смена собственного пароля |
| RoleEditLayout | Поля: название, slug |
| RolePermissionLayout | Управление правами |

### Фильтры

| Filter | Описание |
|--------|----------|
| UserFiltersLayout | Поиск + фильтр по роли |
| RoleFilter | Фильтрация по роли |

### Presenters

| Presenter | Описание |
|-----------|----------|
| UserPresenter | Форматирование отображения пользователя |

## Конфигурация (config/platform.php)

```php
'prefix' => env('DASHBOARD_PREFIX', '/admin'),
'guard' => 'web',
'middleware' => ['web', 'platform'],
'auth' => true, // Встроенная аутентификация Orchid
```

## Особенности

- User модель extends `Orchid\Platform\Models\User` — для совместимости с Orchid
- Orchid предоставляет собственные Allowed Filters и Allowed Sorts на модели User
- Валидация в админке через `UpdateCompanyOrchidRequest` (отдельный Form Request)
- Медиа-файлы управляются через Spatie Media Library, не через Orchid Attachments
