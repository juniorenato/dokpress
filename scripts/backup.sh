#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [ ! -f .env ]; then
  echo ".env not found. Copy .env.example first." >&2
  exit 1
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

if [ -z "${DB_BACKUP_DIR:-}" ]; then
  echo "DB_BACKUP_DIR is not set in .env." >&2
  exit 1
fi

STAMP="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$DB_BACKUP_DIR"
OUT_DIR="$(cd "$DB_BACKUP_DIR" && pwd)"
OUT_FILE="${OUT_DIR}/${MARIADB_DATABASE}-${STAMP}.sql.gz"

docker compose exec -T mariadb mariadb-dump \
  -u"${MARIADB_USER}" \
  -p"${MARIADB_PASSWORD}" \
  "${MARIADB_DATABASE}" | gzip > "$OUT_FILE"

echo "Backup written to ${OUT_FILE}"
