#!/bin/sh
# Builds the Laravel caches against the runtime environment, optionally migrates, then runs the command.
set -eu

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan optimize --no-interaction
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
