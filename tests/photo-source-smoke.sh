#!/usr/bin/env bash
set -euo pipefail

repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
test_dir=$(mktemp -d /tmp/graber-hub-photo-test.XXXXXX)
data_file="$test_dir/data.json"
settings_file="$test_dir/photo-settings.json"
metadata_file="$test_dir/photos.json"
cache_file="$test_dir/photo-cache.json"
upload_dir="$test_dir/uploads"
api_port=18797
base_url="http://127.0.0.1:$api_port"

mkdir -p "$upload_dir"
jq -n '{kids: [], chores: {}, chore_templates: [], schedules: {}, countdowns: [], verses: [], timer: {active: false, end_time: null, label: ""}, data_version: 1, nextId: 100}' >"$data_file"
jq -n '{album_url:"https://example.invalid/album", mode:"photos", transition_seconds:30, auto_schedule:{photos_start:"20:00",photos_end:"07:00"}, show_clock:true, show_weather:true, show_date:true, ken_burns:true, photo_source:"google"}' >"$settings_file"
printf '%s' '[]' >"$metadata_file"
printf '%s' '{"photos":["https://lh3.googleusercontent.com/pw/test-image=w1920-h1080"],"fetched_at":4102444800}' >"$cache_file"
printf '%s' 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=' | base64 -d >"$test_dir/photo.png"
printf '%s' 'not an image' >"$test_dir/not-image.txt"

PHP_CLI_SERVER_WORKERS=4 \
GRABER_HUB_DATA_FILE="$data_file" \
GRABER_HUB_PHOTO_SETTINGS_FILE="$settings_file" \
GRABER_HUB_PHOTOS_FILE="$metadata_file" \
GRABER_HUB_PHOTO_CACHE_FILE="$cache_file" \
GRABER_HUB_UPLOAD_DIR="$upload_dir" \
GRABER_HUB_MAX_PHOTOS=50 \
    php -S "127.0.0.1:$api_port" -t "$repo_dir/web" >"$test_dir/server.log" 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true; wait "$server_pid" 2>/dev/null || true' EXIT

for _ in $(seq 1 40); do
    if curl -fsS "$base_url/api.php/photo-settings" >/dev/null 2>&1; then
        break
    fi
    sleep 0.1
done

# The selected source persists through the same API used by Android.
curl -fsS -X POST -H 'Content-Type: application/json' \
    -d '{"photo_source":"uploaded"}' \
    "$base_url/api.php/photo-settings" | jq -e '.photo_source == "uploaded"' >/dev/null
jq -e '.photo_source == "uploaded"' "$settings_file" >/dev/null

# Invalid files are rejected and do not mutate the library.
status=$(curl -sS -o "$test_dir/invalid.json" -w '%{http_code}' \
    -F "photo=@$test_dir/not-image.txt;type=text/plain" "$base_url/api.php/photos")
test "$status" = "415"
jq -e '.error | contains("JPEG")' "$test_dir/invalid.json" >/dev/null

# Concurrent valid uploads remain unique and leave valid metadata.
upload_pids=()
for index in $(seq 1 8); do
    curl -fsS -F "photo=@$test_dir/photo.png;type=image/png" -F "name=Photo $index" \
        "$base_url/api.php/photos" >"$test_dir/upload-$index.json" &
    upload_pids+=("$!")
done
for upload_pid in "${upload_pids[@]}"; do wait "$upload_pid"; done
for result in "$test_dir"/upload-*.json; do jq -e '.success == true' "$result" >/dev/null; done
test "$(find "$upload_dir" -maxdepth 1 -type f | wc -l)" -eq 8
jq -e 'length == 8 and ([.[].filename] | unique | length == 8)' "$metadata_file" >/dev/null

library=$(curl -fsS "$base_url/api.php/photos")
test "$(jq 'length' <<<"$library")" -eq 8
filename=$(jq -r '.[0].filename' <<<"$library")
curl -fsS -X PUT -H 'Content-Type: application/json' -d '{"name":"Family favorite"}' \
    "$base_url/api.php/photos/$filename" | jq -e '.success == true' >/dev/null
curl -fsS "$base_url/api.php/photos" | jq -e --arg filename "$filename" \
    '.[] | select(.filename == $filename) | .name == "Family favorite"' >/dev/null

# The wall display now uses local uploads when that source is selected.
curl -fsS "$base_url/photos.php/list" | jq -e '
    .source == "uploaded" and .count == 8 and
    ([.photos[] | startswith("/uploads/")] | all)
' >/dev/null

curl -fsS -X DELETE "$base_url/api.php/photos/$filename" | jq -e '.success == true' >/dev/null
status=$(curl -sS -o /dev/null -w '%{http_code}' -X DELETE "$base_url/api.php/photos/$filename")
test "$status" = "404"

# Google remains available when explicitly selected, using its existing cache.
curl -fsS -X POST -H 'Content-Type: application/json' -d '{"photo_source":"google"}' \
    "$base_url/api.php/photo-settings" >/dev/null
curl -fsS "$base_url/photos.php/list" | jq -e '
    .source == "google" and .count == 1 and
    (.photos[0] | startswith("https://lh3.googleusercontent.com/"))
' >/dev/null

# Local photo URLs must not receive Google-specific size suffixes.
rg -q 'googleusercontent\.com.*return baseUrl' "$repo_dir/web/index.php"

echo "photo source smoke test passed"
