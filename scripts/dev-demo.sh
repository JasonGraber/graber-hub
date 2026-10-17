#!/usr/bin/env bash
set -euo pipefail

repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
demo_dir=${GRABER_HUB_DEMO_DIR:-"$(mktemp -d /tmp/graber-hub-demo.XXXXXX)"}
port=${PORT:-8080}

mkdir -p "$demo_dir/uploads"
php "$repo_dir/scripts/create-demo-data.php" > "$demo_dir/data.json"

echo "Demo data: $demo_dir"
echo "Dashboard: http://127.0.0.1:$port/"

GRABER_HUB_DATA_FILE="$demo_dir/data.json" \
GRABER_HUB_PHOTO_SETTINGS_FILE="$demo_dir/photo-settings.json" \
GRABER_HUB_PHOTOS_FILE="$demo_dir/photos.json" \
GRABER_HUB_UPLOAD_DIR="$demo_dir/uploads" \
php -S "127.0.0.1:$port" -t "$repo_dir/web"
