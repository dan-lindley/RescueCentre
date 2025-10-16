#!/usr/bin/env bash
set -euo pipefail

DB_NAME="${MYSQL_DATABASE:-rescue}"
DB_USER="root"
DB_PASS="${MYSQL_ROOT_PASSWORD}"

run_sql() {
  mariadb -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$1"
}

BASE="/seed/Database"
STRUCTURE_FILE="$BASE/RescueDB - with WP - Structure only.sql"

echo "==> Importing structure (if present)..."
if [[ -f "$STRUCTURE_FILE" ]]; then
  echo "   -> $STRUCTURE_FILE"
  run_sql "$STRUCTURE_FILE"
else
  echo "   !! Structure file not found at: $STRUCTURE_FILE"
fi

echo "==> Importing rescue_*.sql files..."
shopt -s nullglob
for f in "$BASE"/rescue_*.sql; do
  echo "   -> $f"
  run_sql "$f"
done

echo "==> Database seeding complete."
