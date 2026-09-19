#!/bin/bash
set -euo pipefail

SITE_ROOT="/Volumes/Gopi/Latest Website/gcmsafetynets.in"
ZIP_PATH="/Volumes/Gopi/Latest Website/gcmsafetynets-deploy.zip"

cd "$SITE_ROOT"

rm -f "$ZIP_PATH"

zip -r "$ZIP_PATH" . \
  -x "./.git/*" \
  -x "./.idea/*" \
  -x "./.vscode/*" \
  -x "./node_modules/*" \
  -x "./cache/*" \
  -x "./logs/*" \
  -x "./sitemap.xml" \
  -x "./sitemap.xml.gz" \
  -x "./.DS_Store" \
  -x "./**/.DS_Store"

echo "Created: $ZIP_PATH"
