#!/usr/bin/env bash
set -euo pipefail

repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
test_dir=$(mktemp -d /tmp/graber-hub-voice-test.XXXXXX)
data_file="$test_dir/data.json"
port=18796
base_url="http://127.0.0.1:$port/voice.php"
token=voice-smoke-test-token
timezone=America/New_York

GRABER_HUB_TIMEZONE="$timezone" php "$repo_dir/scripts/create-demo-data.php" > "$data_file"

GRABER_HUB_DATA_FILE="$data_file" \
GRABER_HUB_VOICE_TOKEN="$token" \
GRABER_HUB_TIMEZONE="$timezone" \
php -S "127.0.0.1:$port" -t "$repo_dir/web" >"$test_dir/php.log" 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true' EXIT

for _ in $(seq 1 30); do
    status=$(curl -sS -o /dev/null -w '%{http_code}' "$base_url/status" 2>/dev/null || true)
    if [ "$status" = "401" ]; then break; fi
    sleep 0.1
done
test "$status" = "401"

curl -fsS "$base_url/status" \
    -H "X-Graber-Hub-Token: $token" | jq -e '.ok == true' >/dev/null

curl -fsS -X POST "$base_url/chore-done" \
    -H 'Content-Type: application/json' \
    -H "X-Graber-Hub-Token: $token" \
    --data '{"kid":"Sam","chore":"make the bed"}' | jq -e \
    '.ok == true and .kid == "Sam" and .chore == "Make the bed"' >/dev/null

today=$(TZ="$timezone" date +%F)
jq -e --arg today "$today" \
    '.chores[$today][] | select(.title == "Make the bed") | .done == true' \
    "$data_file" >/dev/null
jq -e '.data_version == 2' "$data_file" >/dev/null

echo "voice smoke test passed"
