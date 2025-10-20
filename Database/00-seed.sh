#!/usr/bin/env bash
set -euo pipefail

DB_NAME="${MYSQL_DATABASE:-rescue}"
DB_USER="root"
DB_PASS="${MYSQL_ROOT_PASSWORD}"

run_sql() {
  mariadb -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$1"
}

BASE="/seed/Database"
STRUCTURE_FILE="$BASE/database_new_structure_and_standing_data.sql"

echo "==> Importing structure (if present)..."
if [[ -f "$STRUCTURE_FILE" ]]; then
  echo "   -> $STRUCTURE_FILE"
  run_sql "$STRUCTURE_FILE"
else
  echo "   !! Structure file not found at: $STRUCTURE_FILE"
fi

echo "==> Importing migrate_*.sql files..."
shopt -s nullglob
for f in "$BASE"/migrate_*.sql; do
  echo "   -> $f"
  run_sql "$f"
done

echo "==> Database seeding complete."
