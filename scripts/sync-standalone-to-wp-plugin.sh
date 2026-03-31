#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STANDALONE="$ROOT_DIR/standalone"
PLUGIN="$ROOT_DIR/wp-plugin/erfa-aca7-email"

if [[ ! -d "$STANDALONE" ]]; then
  echo "Missing standalone/" >&2
  exit 1
fi
if [[ ! -d "$PLUGIN" ]]; then
  echo "Missing wp-plugin/erfa-aca7-email/" >&2
  exit 1
fi

mkdir -p "$PLUGIN/assets/data" "$PLUGIN/assets/img"

# Copy the web UI + data into the plugin assets
rsync -a "$STANDALONE/index.html" "$PLUGIN/assets/index.html"
rsync -a "$STANDALONE/data/" "$PLUGIN/assets/data/"
rsync -a "$STANDALONE/img/" "$PLUGIN/assets/img/"

echo "Synced standalone -> wordpress plugin assets."
