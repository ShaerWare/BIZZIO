# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Bizzio.ru — B2B business network for the construction industry. Laravel 12 + Orchid admin panel + PostgreSQL.

## Development Commands

### Docker (Primary)
```bash
docker compose up -d                                    # Start containers
docker compose down                                     # Stop containers
docker compose exec app php artisan <command>           # Run artisan in container
docker compose exec app composer install                # Install dependencies
```

### Local Development
```bash
composer run dev          # Start all: Laravel + Queue (queue:listen) + Logs (pail) + Vite
php artisan serve         # Start Laravel server only
php artisan queue:work    # Process queue jobs (production)
php artisan queue:listen  # Process queue jobs with auto-reload on code changes (dev)
```

### Testing
```bash
composer run test                        # Run all tests (clears config first)
php artisan test --filter=TestName       # Run specific test
php artisan test --filter=TestClass::testMethod  # Run single test method
```
**Note:** Tests use SQLite in-memory (`phpunit.xml`), not PostgreSQL. PostgreSQL-specific features (e.g. `jsonb`, full-text search) are not tested.
Test env: `QUEUE_CONNECTION=sync` (jobs run synchronously unless `Queue::fake()` used), `SESSION_DRIVER=array`, `CACHE_STORE=array`, `BCRYPT_ROUNDS=4`.

### Code Style
```bash
./vendor/bin/pint --test     # Check code style (Laravel Pint)
./vendor/bin/pint            # Fix code style
```

### Database & Cache
```bash
php artisan migrate --seed                    # Run migrations with seeders
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
```

### Custom Artisan Commands
```bash
php artisan auctions:check-expired   # Close expired auctions (manual or one-off use)
php artisan auctions:update-statuses # Update auction statuses (scheduled every minute)
php artisan rss:parse                # Parse RSS news sources (scheduled every 5 min)
php artisan news:clean-old           # Clean old news articles (scheduled daily at 02:00)
```

### Scheduler
Configured in `bootstrap/app.php` via `withSchedule()`:
- `rss:parse` — every 5 minutes (with `withoutOverlapping`)
- `auctions:update-statuses` — every minute (with `withoutOverlapping`)
- `news:clean-old` — daily at 02:00

### Testing PDF Generation
Queue worker must be running: `php artisan queue:work`

```bash
# Dispatch jobs manually via tinker:
php artisan tinker
>>> App\Jobs\CloseRfqJob::dispatch(App\Models\Rfq::find(123));
>>> App\Jobs\CloseAuctionJob::dispatch(123);
```

**PDF Files Location:**
- RFQ protocols: `storage/app/public/rfq-protocols/protocol_RFQ-XXXX_timestamp.pdf`
- Auction protocols: stored via Media Library (check `media` table)

## Architecture

### Core Modules
- **Companies** — Profiles with verification, documents, moderator assignment, join requests
- **Projects** — Company invitations, comments, participant roles
- **RFQ (Запрос цен)** — Weighted scoring criteria, auto-calculation, PDF protocols
- **Auction (Аукционы)** — Real-time trading (long-polling), anonymized participants, PDF protocols
- **News** — RSS aggregator with keyword filtering, personalized feed
- **Search** — Search via model `scopeSearch()` with `ilike`/`like` queries (Scout uses `collection` driver; search is not index-based)
- **Tenders** — Unified view (`/tenders`) combining RFQs and Auctions with shared filters

### Status Lifecycles

**RFQ:** `draft` → `active` → `closed`
- `draft`: Only visible to owner/moderators
- `active`: Accepting bids (between start_date and end_date)
- `closed`: CloseRfqJob runs at end_date, generates PDF protocol, determines winner

**Auction:** `draft` → `active` → `trading` → `closed` / `cancelled`
- `draft`: Only visible to owner/moderators
- `active`: Accepting initial applications (between start_date and end_date)
- `trading`: Real-time bidding (started via `startTrading()`, uses long-polling)
- `closed`: CloseAuctionJob runs 20min after last bid, generates PDF protocol
- `cancelled`: Active with no bids at transition, or trading with no bids after 24h

### Key Directories
```
app/
├── Services/       # Business logic: RfqScoringService, AuctionWinnerService, *ProtocolService, NewsFilterService
├── Events/         # Domain events (registered in AppServiceProvider@registerEventListeners)
├── Listeners/      # Send*Notification handlers
├── Jobs/           # CloseAuctionJob, CloseRfqJob, UpdateAuctionStatuses, NotifyAdminOnRSSErrorJob
├── Policies/       # RfqPolicy, AuctionPolicy (registered via Gate::policy in AppServiceProvider)
├── Socialite/      # YandexProvider (custom Yandex OAuth implementation)
├── Orchid/         # Admin panel screens and layouts
└── Http/Controllers/Api/  # API endpoints (v1/chat - Gemini AI proxy)
```

### Event-Driven Architecture
Events registered in `AppServiceProvider@registerEventListeners()`:
- `ProjectInvitationSent` → `SendProjectInvitationNotification`
- `TenderInvitationSent` → `SendTenderInvitationNotification`
- `TenderClosed` → `SendTenderClosedNotification`
- `AuctionTradingStarted` → `SendAuctionTradingStartedNotification`
- `CommentCreated` → `SendCommentNotification`
- `CompanyCreated` → `SendCompanyCreatedNotification`

### Key Packages
- `orchid/platform` — Admin panel
- `spatie/laravel-activitylog` — Activity feed logging
- `spatie/laravel-medialibrary` — File/image management
- `barryvdh/laravel-dompdf` — PDF protocol generation
- `google-gemini-php/client` — Gemini AI chat proxy
- `willvincent/feeds` — RSS feed parsing
- `laravel/socialite` — OAuth (Google, Yandex)

### OAuth Providers
- **Google** — Standard Socialite provider. `.env`: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`
- **Yandex** — Custom provider at `app/Socialite/YandexProvider.php`, registered in `AppServiceProvider::configureSocialite()`. `.env`: `YANDEX_CLIENT_ID`, `YANDEX_CLIENT_SECRET`, `YANDEX_REDIRECT_URI`
- **VK** — Package `socialiteproviders/vkontakte` is installed but NOT configured (no entry in `config/services.php` or `AppServiceProvider`)

### AI Chat API
`POST /api/v1/chat` — Proxies chat to Google Gemini Pro (`geminiPro()`).
Input: `message` (string, max 2000), `history` (array of `{role: user|bot, text}`).
Requires `.env`: `GEMINI_API_KEY`

### Search Conventions
- PostgreSQL `LIKE` is case-sensitive. All search queries use `ilike` for PostgreSQL, `like` for SQLite (tests):
  ```php
  $op = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
  $query->where('name', $op, "%{$search}%");
  ```
- Quick search API (`GET /search/quick?q=...`) returns a flat JSON array of results (not wrapped in `{results: [...]}`)
- Each result has: `type`, `type_label`, `id`, `title`, `subtitle`, `url`
- Types: `company`, `project`, `rfq`, `auction`, `user`

### Routing Conventions
**Important:** Fixed routes MUST be defined BEFORE dynamic `{param}` routes to avoid conflicts.
```php
// ✅ Correct: /auctions/create before /auctions/{auction}
Route::get('/auctions/create', ...)->name('create');
Route::get('/auctions/{auction}', ...)->name('show');
```

## Brand Colors

Primary color: **Bizzio Green** (`#28a745`)

Tailwind CSS uses `emerald-*` classes for UI elements. Custom palette in `tailwind.config.js`:
```javascript
bizzio: {
    500: '#28a745',  // Primary brand green
    600: '#22963e',  // Hover states
    700: '#1e8537',  // Active states
}
```

Welcome page uses gradient: `#28a745 → #81b407` (defined in `public/css/custom.css`)

## Docker Setup

- **app** container (`my_project_app`): PHP 8.2-FPM + Nginx + Supervisor, timezone Europe/Moscow, resource limits: 1 CPU / 0.9G RAM. Port 80 not exposed by default (use nginx-proxy in production)
- **db** container (`my_project_db`): PostgreSQL 14 Alpine, host port 5435, timezone Europe/Moscow
- Production: `docker-compose.override.yml.prod` with nginx-proxy + Let's Encrypt

## Production Deployment

Supervisor (configured in `docker/supervisord.conf`) auto-starts: php-fpm, nginx, queue worker (`queue:work database`), and scheduler (`schedule:run` every 60 sec).

```bash
# Deploy
git pull origin main
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache && docker compose exec app php artisan route:cache && docker compose exec app php artisan view:cache
docker compose exec app php artisan queue:restart

# Logs
docker compose exec app tail -f /var/log/queue-worker.log    # Queue worker
docker compose exec app tail -f /var/log/scheduler.log       # Scheduler
docker compose logs -f app                                    # Container
```

## Environment Notes

- `APP_TIMEZONE=Europe/Moscow`
- `APP_URL` must match actual URL (http://localhost:8080 for local dev)
- `SESSION_SECURE_COOKIE=false` for HTTP development
- HTTPS forced when `APP_ENV=production` or `APP_URL` starts with https
- Session and queue both use `database` driver (`SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`)
- CSRF token expiry (419) is handled globally in `bootstrap/app.php` — redirects to login with a flash message instead of showing an error page
- SMTP: `MAIL_FROM_ADDRESS` must match `MAIL_USERNAME` (Beget hosting requirement)

## Key Model Details

```
User ←→ Company (many-to-many via company_user pivot with role)
Company → Projects (owner)
Project ←→ Companies (many-to-many via project_participants)
Company → Rfqs (owner)
Rfq → RfqBids (one-to-many)
Company → Auctions (owner)
Auction → AuctionBids (one-to-many)
```

- `User` extends `Orchid\Platform\Models\User` (not Laravel's default `Authenticatable`)
- `Company` route model binding uses `slug` (`getRouteKeyName() → 'slug'`), not `id`
- `Company`, `Rfq`, `Auction` use `SoftDeletes`, `InteractsWithMedia`, `LogsActivity`
- `Company::shouldBeSearchable()` — only verified companies are indexed
- RFQ numbers: `К-ГГММДД-0001`, Auction numbers: `А-ГГММДД-0001`
- Auction bid step: 0.5%–5% of current price

## Testing Patterns

Tests use `RefreshDatabase` trait with standard setup:
```php
protected function setUp(): void {
    parent::setUp();
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->company = Company::factory()->create(['created_by' => $this->user->id]);
    $this->company->assignModerator($this->user, 'owner');
    Storage::fake('public');
    Queue::fake();
}
```
Helper methods like `createRfq()`, `createAuction()` are defined in each test class.

**Factories:** Only `UserFactory`, `CompanyFactory`, `ProjectFactory` exist. Rfq, Auction, and bid models must be created manually in tests via helper methods.

## Documentation

All project docs in `docs/`:
- `00_ТЕХНИЧЕСКОЕ_ЗАДАНИЕ.md` — Original requirements
- `01_ПЛАН_РАЗРАБОТКИ.md` — Development plan (10 sprints)
- `04_БЭКЛОГ_ФИКСОВ.md` — Bug backlog with priorities (60/75 completed)
- `sprints/*.md` — Sprint reports (1-9 completed)
- `CHANGELOG_CLAUDE.md` — Log of Claude Code changes
- `claude/start_message.md` — Context for new Claude Code sessions

## Claude Code Instructions
- После каждого успешного выполнения задачи записывай краткий отчет (что сделано, какие файлы изменены) в конец файла `docs/CHANGELOG_CLAUDE.md`.
- Если возникла ошибка в терминале, которую ты фиксишь, запиши её причину в этот же файл.