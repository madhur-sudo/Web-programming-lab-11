#!/bin/sh
# docker-entrypoint.sh
# Runs database seeding before starting Apache.

set -e

echo "[Entrypoint] Applying database schema..."

# Wait for the database to be ready (simple retry loop)
MAX_RETRIES=30
RETRY=0
until php /var/www/html/scripts/migrate.php; do
  RETRY=$((RETRY+1))
  if [ "$RETRY" -ge "$MAX_RETRIES" ]; then
    echo "[Entrypoint] Database schema could not be applied. Exiting."
    exit 1
  fi
  echo "[Entrypoint] Database not ready, retrying in 2s... (${RETRY}/${MAX_RETRIES})"
  sleep 2
done

echo "[Entrypoint] Starting Apache..."
exec "$@"
