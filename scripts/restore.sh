#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

DUMP_FILE="${1:-}"
if [ -z "$DUMP_FILE" ]; then
  echo "Usage: scripts/restore.sh <dump>.sql.gz" >&2
  exit 1
fi

if [ ! -f "$DUMP_FILE" ]; then
  echo "Dump not found: ${DUMP_FILE}" >&2
  exit 1
fi

if [ ! -f .env ]; then
  echo ".env not found. Copy .env.example first." >&2
  exit 1
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

gunzip -c "$DUMP_FILE" | docker compose exec -T mariadb mariadb \
  -u"${MARIADB_USER}" \
  -p"${MARIADB_PASSWORD}" \
  "${MARIADB_DATABASE}"

echo "Restore completed from ${DUMP_FILE}"
