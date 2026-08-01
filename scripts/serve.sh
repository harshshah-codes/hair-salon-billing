#!/usr/bin/env bash
#
# Local dev runner: seeds the database and starts the PHP built-in server.
#
# Usage:
#   ./scripts/serve.sh                 # http://127.0.0.1:8000
#   PORT=9000 ./scripts/serve.sh       # custom port
#   SKIP_SEED=1 ./scripts/serve.sh     # skip re-seeding
#
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

PORT="${PORT:-8000}"
HOST="${HOST:-127.0.0.1}"

if ! command -v php >/dev/null 2>&1; then
    echo "ERROR: php not found on PATH." >&2
    exit 1
fi

# The app needs pdo_mysql. On distros where it lives in a non-standard
# scan dir (e.g. Arch's /etc/php/conf.d), pass it explicitly:
#   PHP_INI_SCAN_DIR=/path/to/phpconf ./scripts/serve.sh
if ! php -m 2>/dev/null | grep -qi pdo_mysql; then
    echo "ERROR: pdo_mysql extension is not loaded." >&2
    echo "" >&2
    echo "Load it via PHP_INI_SCAN_DIR, e.g.:" >&2
    echo "  PHP_INI_SCAN_DIR=/path/to/phpconf ./scripts/serve.sh" >&2
    echo "  (the directory must contain a pdo_mysql.ini with: extension=pdo_mysql)" >&2
    exit 1
fi

if [ "${SKIP_SEED:-0}" != "1" ]; then
    echo "==> Seeding database (SKIP_SEED=1 to skip)..." 
    php scripts/seed.php
fi

echo "==> Starting server on http://${HOST}:${PORT}"
echo "    Sign in at /auth/login"
exec php -S "${HOST}:${PORT}" -t public
