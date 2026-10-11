#!/usr/bin/env bash
set -euo pipefail

repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
test_dir=$(mktemp -d /tmp/graber-hub-schedule-test.XXXXXX)
data_file="$test_dir/data.json"
port=18791
base_url="http://127.0.0.1:$port/api.php"

GRABER_HUB_DATA_FILE="$data_file" php -S "127.0.0.1:$port" -t "$repo_dir/web" >"$test_dir/php.log" 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true' EXIT

for _ in $(seq 1 30); do
    if curl -fsS "$base_url/dashboard" >/dev/null 2>&1; then
        break
    fi
    sleep 0.1
done

headers="$test_dir/headers"
body="$test_dir/body"
curl -fsS -D "$headers" -o "$body" "$base_url/schedule?date=2099-01-01"
version=$(awk 'tolower($1) == "x-data-version:" {gsub("\\r", "", $2); print $2}' "$headers")
test -n "$version"

payload='{"date":"2099-01-01","blocks":[{"time_start":"08:00","time_end":"09:00","title":"Breakfast","emoji":"🍳"},{"time_start":"09:00","time_end":"10:00","title":"School","emoji":"📚"}]}'
curl -fsS -D "$headers" -o "$body" -X POST "$base_url/schedule/template" \
    -H 'Content-Type: application/json' \
    -H "X-Data-Version: $version" \
    --data-binary "$payload"
jq -e '.ok == true and .count == 2' "$body" >/dev/null
new_version=$(awk 'tolower($1) == "x-data-version:" {gsub("\\r", "", $2); print $2}' "$headers")

curl -fsS "$base_url/schedule?date=2099-01-01" | jq -e \
    'length == 2 and .[0].title == "Breakfast" and .[1].title == "School"' >/dev/null

stale_status=$(curl -sS -o "$body" -w '%{http_code}' -X POST "$base_url/schedule" \
    -H 'Content-Type: application/json' \
    -H "X-Data-Version: $version" \
    --data-binary '{"date":"2099-01-01","time_start":"10:00","title":"Stale"}')
test "$stale_status" = "409"
jq -e '.error == "stale data"' "$body" >/dev/null

block_id=$(curl -fsS "$base_url/schedule?date=2099-01-01" | jq -r '.[0].id')
curl -fsS -X PUT "$base_url/schedule/$block_id?date=2099-01-01" \
    -H 'Content-Type: application/json' \
    -H "X-Data-Version: $new_version" \
    --data-binary '{"title":"Family Breakfast"}' | jq -e '.title == "Family Breakfast"' >/dev/null

echo "schedule smoke test passed"
