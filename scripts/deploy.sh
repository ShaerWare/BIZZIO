#!/usr/bin/env bash
#
# Post-git deploy steps, run INSIDE a project directory on the server
# (/var/www/BIZZIO for prod, /var/www/bizzio-test for staging).
#
# The calling workflow is responsible for the git fetch + reset --hard.
# This script only rebuilds the app inside the already-running Docker stack.
# Frontend assets (public/build) are committed to git, so there is no npm
# step here — the server has no Node.js.
#
set -euo pipefail

echo "==> composer install (no-dev)"
docker compose exec -T app composer install --no-dev --optimize-autoloader --no-interaction

echo "==> migrate"
docker compose exec -T app php artisan migrate --force

echo "==> rebuild caches"
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

echo "==> restart queue workers"
docker compose exec -T app php artisan queue:restart

echo "==> fix storage / cache permissions"
docker compose exec -T app sh -c 'chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache'

echo "==> deploy complete"
