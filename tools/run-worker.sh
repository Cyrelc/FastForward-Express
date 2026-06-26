#!/usr/bin/env bash
#
# Queue worker launcher.
#
# Supervisor points at THIS script instead of `artisan queue:work` directly.
# Before starting (or restarting) a worker we block until MySQL is reachable.
# This guarantees the process stays alive past Supervisor's `startsecs`, so a
# transient DB outage (e.g. mysqld briefly down during month-end payment runs)
# can never burn through `startretries` and drive the program to FATAL — which
# is what was silently killing the workers for good.
#
# Once the worker exits (graceful --max-time restart, queue:restart, or a lost
# DB connection), Supervisor relaunches this script and we wait for the DB again.
#
set -euo pipefail

# Resolve the project root from this script's location so the same file works in
# both dev (~/Desktop/FastForward-Express) and prod (/var/www/FastForward-Express).
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

# Block until the database accepts connections. db:wait logs recovery
# instrumentation (Threads_connected / max_connections) once it reconnects.
php artisan db:wait --sleep=3

# Hand off to the real worker. `exec` replaces this shell so Supervisor signals
# (QUIT/TERM) reach the PHP worker directly for a clean shutdown.
exec php artisan queue:work \
    --sleep=3 \
    --tries=3 \
    --timeout=120 \
    --max-time=86400 \
    --memory=256
