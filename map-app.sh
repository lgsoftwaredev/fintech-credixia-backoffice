#!/usr/bin/env bash
# scripts/map-app.sh
# Export a map of app/ excluding app/Models to a .txt file.

set -Eeuo pipefail

# ---------- Constants ----------
PROJECT_ROOT="${1:-.}"
APP_DIR="$PROJECT_ROOT/app"
EXCLUDED_DIR_REL="app/Models"
OUTPUT_FILE="$PROJECT_ROOT/app_map.txt"

# ---------- Guards ----------
if [[ ! -d "$APP_DIR" ]]; then
  echo "Error: '$APP_DIR' not found. Run from project root or pass the project root path."
  exit 1
fi

# ---------- Header ----------
HEADER=$(
  cat <<EOF
App file map (excluding app/Models)
Project root: $(cd "$PROJECT_ROOT" && pwd)
Generated at (UTC): $(date -u +"%Y-%m-%dT%H:%M:%SZ")
--------------------------------------------------
EOF
)

# ---------- Mapper ----------
generate_with_tree() {
  # Use 'tree' if available; ignore directories named 'Models' under app
  (cd "$PROJECT_ROOT" && tree -a "app" -I "Models")
}

generate_with_find() {
  # Portable fallback using 'find'
  (
    cd "$PROJECT_ROOT"
    find "app" \
      \( -path "$EXCLUDED_DIR_REL" -o -path "$EXCLUDED_DIR_REL/*" \) -prune -o -print \
      | LC_ALL=C sort
  )
}

if command -v tree >/dev/null 2>&1; then
  MAP_CONTENT="$(generate_with_tree)"
else
  MAP_CONTENT="$(generate_with_find)"
fi

# ---------- Write to .txt ----------
{
  echo "$HEADER"
  echo "$MAP_CONTENT"
} > "$OUTPUT_FILE"

echo "Done. File written to: $OUTPUT_FILE"
