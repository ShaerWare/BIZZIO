# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Bizzio.ru — B2B business network for the construction industry. Laravel 12 + Orchid admin panel + PostgreSQL.

**Status:** 9/10 sprints completed (90% MVP), ~380 hours of development.
**Tests:** 185 feature tests, 377 assertions.

## Development Commands

### Docker (Primary)
```bash
docker compose up -d                              # Start containers
docker compose down                               # Stop containers
docker exec my_project_app php artisan <command>  # Run artisan in container
docker exec my_project_app composer install       # Install dependencies
```

### Local Development
```bash
composer run dev          # Start all: Laravel + Queue + Logs + Vite (uses concurrently)
php artisan serve         # Start Laravel server only
php artisan queue:work    # Process queue jobs (use queue:listen for auto-reload on code changes)
```

### Testing
```bash
composer run test                        # Run all tests (clears config first)
php artisan test --filter=TestName       # Run specific test
php artisan test --filter=TestClass::testMethod  # Run single test method
```

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
php artisan auctions:check-expired   # Close expired auctions (scheduler runs every minute)
php artisan auctions:update-status   # Update auction statuses
php artisan rss:parse                # Parse RSS news sources
```

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
- **RFQ (Тендеры)** — Weighted scoring criteria, auto-calculation, PDF protocols
- **Auction (Аукционы)** — Real-time trading (long-polling), anonymized participants, PDF protocols
- **News** — RSS aggregator with keyword filtering, personalized feed
- **Search** — Laravel Scout (database driver) across User, Company, Project, Rfq, Auction

### Status Lifecycles

**RFQ:** `draft` → `active` → `closed`
- `draft`: Only visible to owner/moderators
- `active`: Accepting bids (between start_date and end_date)
- `closed`: CloseRfqJob runs at end_date, generates PDF protocol, determines winner

**Auction:** `draft` → `active` → `trading` → `closed`
- `draft`: Only visible to owner/moderators
- `active`: Accepting initial applications (between start_date and end_date)
- `trading`: Real-time bidding (started via `startTrading()`, uses long-polling)
- `closed`: CloseAuctionJob runs 20min after last bid, generates PDF protocol

### Key Directories
```
app/
├── Services/       # Business logic: RfqScoringService, AuctionWinnerService, *ProtocolService, NewsFilterService
├── Events/         # Domain events (registered in AppServiceProvider@registerEventListeners)
├── Listeners/      # Send*Notification handlers
├── Jobs/           # CloseAuctionJob, CloseRfqJob, UpdateAuctionStatuses
├── Policies/       # RfqPolicy, AuctionPolicy
├── Socialite/      # VKIDProvider (custom VK ID API implementation)
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

### Key Packages
- `orchid/platform` — Admin panel
- `spatie/laravel-activitylog` — Activity feed logging
- `spatie/laravel-medialibrary` — File/image management
- `barryvdh/laravel-dompdf` — PDF protocol generation
- `laravel/socialite` + `app/Socialite/VKIDProvider.php` — OAuth (Google, VK ID)

### VK ID OAuth
Custom provider at `app/Socialite/VKIDProvider.php` for new VK ID API.
Requires `.env`: `VKID_CLIENT_ID`, `VKID_CLIENT_SECRET`, `VKID_REDIRECT_URI`

### AI Chat API
`POST /api/v1/chat` — Proxies chat to Google Gemini API.
Requires `.env`: `GEMINI_API_KEY`

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

- **app** container: PHP 8.2-FPM + Nginx + Supervisor, port 80, timezone Europe/Moscow
- **db** container: PostgreSQL 14 Alpine, host port 5435, timezone Europe/Moscow
- Timezone configured via tzdata in Dockerfile + TZ environment variable
- Production: `docker-compose.override.yml.prod` with nginx-proxy + Let's Encrypt

## Production Deployment

### Deploy from Git
```bash
cd /path/to/project
git pull origin main
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan storage:link
```

### Server Commands (docker compose exec app)
```bash
# Artisan commands
docker compose exec app php artisan migrate              # Run migrations
docker compose exec app php artisan db:seed              # Run seeders
docker compose exec app php artisan tinker               # Laravel REPL

# Queue & Jobs
docker compose exec app php artisan queue:work           # Process jobs (auto-started by Supervisor)
docker compose exec app php artisan queue:restart        # Restart queue workers after code changes
docker compose exec app php artisan queue:failed         # List failed jobs
docker compose exec app php artisan queue:retry all      # Retry failed jobs

# Scheduler (auto-runs every minute via Supervisor)
docker compose exec app php artisan schedule:run         # Manual run
docker compose exec app php artisan schedule:list        # List scheduled tasks

# Custom commands
docker compose exec app php artisan auctions:check-expired   # Close expired auctions
docker compose exec app php artisan rss:parse                # Parse RSS feeds

# Cache management
docker compose exec app php artisan config:cache        # Cache config (production)
docker compose exec app php artisan route:cache         # Cache routes (production)
docker compose exec app php artisan view:cache          # Cache views (production)
docker compose exec app php artisan optimize:clear      # Clear all caches

# Logs
docker compose exec app tail -f /var/log/queue-worker.log    # Queue worker logs
docker compose exec app tail -f /var/log/scheduler.log       # Scheduler logs
docker compose logs -f app                                    # Container logs
```

### What Supervisor Auto-Starts
Configured in `docker/supervisord.conf`:
- **php-fpm** — PHP FastCGI Process Manager
- **nginx** — Web server
- **laravel-worker** — Queue worker (`queue:work database`)
- **laravel-scheduler** — Cron scheduler (`schedule:run` every 60 sec)

### Restart Services After Deploy
```bash
docker compose restart app                              # Restart entire container
docker compose exec app php artisan queue:restart       # Graceful queue restart
docker compose exec app supervisorctl restart all       # Restart all Supervisor programs
```

### First-Time Setup on Server
```bash
git clone <repo> /path/to/project
cd /path/to/project
cp .env.example .env
# Edit .env with production values (DB, MAIL, APP_URL, etc.)
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

## Environment Notes

- `APP_URL` must match actual URL (http://localhost:8080 for local dev)
- `SESSION_SECURE_COOKIE=false` for HTTP development
- HTTPS forced when `APP_ENV=production` or `APP_URL` starts with https

## Key Model Relationships

```
User ←→ Company (many-to-many via company_user pivot with role)
Company → Projects (owner)
Project ←→ Companies (many-to-many via project_participants)
Company → Rfqs (owner)
Rfq → RfqBids (one-to-many)
Company → Auctions (owner)
Auction → AuctionBids (one-to-many)
```

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

## Documentation

All project docs in `docs/`:
- `00_ТЕХНИЧЕСКОЕ_ЗАДАНИЕ.md` — Original requirements
- `01_ПЛАН_РАЗРАБОТКИ.md` — Development plan (10 sprints)
- `04_БЭКЛОГ_ФИКСОВ.md` — Bug backlog with priorities (21/38 completed)
- `sprints/*.md` — Sprint reports (1-9 completed)
- `CHANGELOG_CLAUDE.md` — Log of Claude Code changes
- `claude/start_message.md` — Context for new Claude Code sessions

## Claude Code Instructions
- После каждого успешного выполнения задачи записывай краткий отчет (что сделано, какие файлы изменены) в конец файла `docs/CHANGELOG_CLAUDE.md`.
- Если возникла ошибка в терминале, которую ты фиксишь, запиши её причину в этот же файл.