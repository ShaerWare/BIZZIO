# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Bizzo.ru — B2B business network for the construction industry. Laravel 12 + Orchid admin panel.

**Status:** 8/10 sprints completed (80% MVP), ~360 hours of development.

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
composer run dev          # Start dev server (Laravel + Queue + Logs + Vite)
php artisan serve         # Start Laravel server only
php artisan queue:work    # Process queue jobs
```

### Testing
```bash
composer run test                        # Run all tests (clears config first)
php artisan test                         # Alternative
php artisan test --filter=TestName       # Run specific test
php artisan test tests/Feature/XxxTest.php  # Run single test file
```

### Code Style
```bash
./vendor/bin/pint --check    # Check code style (Laravel Pint)
./vendor/bin/pint            # Fix code style
```

### Database
```bash
php artisan migrate              # Run migrations
php artisan migrate --seed       # Run migrations with seeders
php artisan migrate:rollback     # Rollback last migration
```

### Cache (clear all)
```bash
php artisan config:clear && php artisan cache:clear && php artisan route:clear
```

### Custom Artisan Commands
```bash
php artisan auctions:check-expired   # Close expired auctions (runs every minute via scheduler)
php artisan auctions:update-status   # Update auction statuses
php artisan rss:parse                # Parse RSS news sources
php artisan news:clean-old           # Clean old news entries
```

## Architecture

### Core Modules
- **Companies** — Company profiles with verification, document upload, moderator assignment, join requests
- **Projects** — Project management with company invitations, comments, participant roles
- **RFQ (Request for Quotation)** — Tender system with weighted scoring criteria, auto-calculation, PDF protocols
- **Auction** — Real-time online trading (long-polling), anonymized participants, configurable bid step, PDF protocols
- **News** — RSS aggregator with keyword filtering, personalized feed
- **Search** — Global search via Laravel Scout (database driver) across User, Company, Project, Rfq, Auction

### Key Directories
```
app/
├── Events/         # Domain events: ProjectInvitationSent, TenderClosed, etc.
├── Listeners/      # Event handlers: Send*Notification classes
├── Services/       # Business logic: RfqScoringService, AuctionWinnerService, RfqProtocolService, etc.
├── Policies/       # Authorization: RfqPolicy, AuctionPolicy
├── Socialite/      # Custom OAuth: VKIDProvider (new VK ID API)
├── Jobs/           # Queue jobs: CloseAuctionJob, CloseRfqJob, UpdateAuctionStatuses, etc.
└── Orchid/         # Admin panel screens and layouts

routes/
├── web.php         # Main web routes (companies, projects, rfqs, auctions, news, search)
├── platform.php    # Orchid admin routes
└── api.php         # API routes (v1: POST /api/v1/chat for Gemini proxy)
```

### Event-Driven Architecture
Events are registered in `AppServiceProvider@registerEventListeners()`. Key events:
- `ProjectInvitationSent` → `SendProjectInvitationNotification`
- `TenderInvitationSent` → `SendTenderInvitationNotification`
- `TenderClosed` → `SendTenderClosedNotification`
- `AuctionTradingStarted` → `SendAuctionTradingStartedNotification`
- `CommentCreated` → `SendCommentNotification`

### Services Layer
Business logic is encapsulated in `app/Services/`:
- `RfqScoringService` — Weighted scoring calculation for RFQ bids
- `RfqProtocolService` — PDF protocol generation for RFQ results
- `AuctionWinnerService` — Winner determination logic for auctions
- `AuctionProtocolService` — PDF protocol generation for auction results
- `NewsFilterService` — Keyword-based news filtering for personalized feeds

### Key Packages
- `orchid/platform` — Admin panel
- `spatie/laravel-activitylog` — Action logging for activity feed
- `spatie/laravel-medialibrary` — File/image management with conversions
- `laravel/socialite` — OAuth (Google built-in, VK via custom provider)
- `barryvdh/laravel-dompdf` — PDF generation for protocols
- `willvincent/feeds` — RSS parsing
- `google-gemini-php/client` — Gemini AI API client
- `laravel/scout` — Full-text search (database driver for MVP)

### VK ID OAuth
Two VK providers are registered in `AppServiceProvider@configureSocialite()`:
- `vkid` — Custom provider at `app/Socialite/VKIDProvider.php` for new VK ID API
- `vk` — `socialiteproviders/vkontakte` package for legacy compatibility

Requires in `.env`: `VKID_CLIENT_ID`, `VKID_CLIENT_SECRET`, `VKID_REDIRECT_URI`

## Docker Setup

- **app** container: PHP 8.2-FPM + Nginx + Supervisor (single container), internally exposes port 80
- **db** container: PostgreSQL 14 Alpine, exposed on host port 5435
- Use `docker-compose.override.yml.prod` for production with nginx-proxy + Let's Encrypt

## Environment Notes

- `APP_URL` must match actual URL (http://localhost:8080 for local dev)
- `SESSION_SECURE_COOKIE=false` for HTTP development
- HTTPS is forced only when `APP_ENV=production` or `APP_URL` starts with https (see `AppServiceProvider@configureHttps()`)

## Documentation

All project docs in `docs/`:
- `00_ТЕХНИЧЕСКОЕ_ЗАДАНИЕ.md` — Original requirements
- `01_ПЛАН_РАЗРАБОТКИ.md` — Development plan (10 sprints)
- `04_БЭКЛОГ_ФИКСОВ.md` — Bug backlog with priorities
- `sprints/*.md` — Sprint reports (1-8 completed)
- `CHANGELOG_CLAUDE.md` — Log of Claude Code changes

## Claude Code Instructions
- После каждого успешного выполнения задачи записывай краткий отчет (что сделано, какие файлы изменены) в конец файла `docs/CHANGELOG_CLAUDE.md`.
- Если возникла ошибка в терминале, которую ты фиксишь, запиши её причину в этот же файл.