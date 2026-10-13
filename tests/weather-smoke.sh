#!/usr/bin/env bash
set -euo pipefail

repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
test_dir=$(mktemp -d /tmp/graber-hub-weather-test.XXXXXX)
data_file="$test_dir/data.json"
cache_file="$test_dir/weather-cache.json"
mock_port=18795
api_port=18796
base_url="http://127.0.0.1:$api_port/api.php"

jq -n '{kids: [], chores: {}, chore_templates: [], schedules: {}, countdowns: [], verses: [], timer: {active: false, end_time: null, label: ""}, data_version: 1, nextId: 100}' >"$data_file"
printf '%s' '{"current":{"time":"2026-10-13T08:15","temperature_2m":72.4,"weather_code":2}}' >"$test_dir/weather.json"

php -S "127.0.0.1:$mock_port" -t "$test_dir" >"$test_dir/mock.log" 2>&1 &
mock_pid=$!
GRABER_HUB_DATA_FILE="$data_file" \
GRABER_HUB_WEATHER_CACHE_FILE="$cache_file" \
GRABER_HUB_WEATHER_URL="http://127.0.0.1:$mock_port/weather.json" \
    php -S "127.0.0.1:$api_port" -t "$repo_dir/web" >"$test_dir/api.log" 2>&1 &
api_pid=$!
trap 'kill "$api_pid" "$mock_pid" 2>/dev/null || true' EXIT

for _ in $(seq 1 30); do
    if curl -fsS "$base_url/verses" >/dev/null 2>&1; then
        break
    fi
    sleep 0.1
done

curl -fsS "$base_url/weather" | jq -e '
    .temperature_f == 72 and
    .weather_code == 2 and
    .description == "Partly cloudy" and
    .icon == "⛅" and
    .location == "Sarasota, FL" and
    .stale == false
' >/dev/null
jq -e '.temperature_f == 72 and .stale == false' "$cache_file" >/dev/null

kill "$mock_pid"
wait "$mock_pid" 2>/dev/null || true
curl -fsS "$base_url/weather" | jq -e '.temperature_f == 72 and .stale == true' >/dev/null

if rg -q 'wttr\.in' "$repo_dir/web/index.php"; then
    echo "dashboard still references wttr.in" >&2
    exit 1
fi
rg -q 'typeof data\.temperature_f !== "number"' "$repo_dir/web/index.php"
rg -q 'textContent = "--\\u00B0"' "$repo_dir/web/index.php"

echo "weather smoke test passed"
