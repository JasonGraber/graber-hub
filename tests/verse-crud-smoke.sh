#!/usr/bin/env bash
set -euo pipefail

repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
test_dir=$(mktemp -d /tmp/graber-hub-verse-test.XXXXXX)
data_file="$test_dir/data.json"
port=18794
base_url="http://127.0.0.1:$port/api.php"
headers="$test_dir/headers"
body="$test_dir/body"

jq -n '{
    kids: [],
    chores: {},
    chore_templates: [],
    schedules: {},
    countdowns: [],
    verses: [
        {id: 1, text: "Verse one", ref: "Book 1:1"},
        {id: 2, text: "Verse two", ref: "Book 2:2"}
    ],
    timer: {active: false, end_time: null, label: ""},
    data_version: 3,
    nextId: 10
}' >"$data_file"

TZ=America/New_York PHP_CLI_SERVER_WORKERS=4 GRABER_HUB_DATA_FILE="$data_file" \
    php -S "127.0.0.1:$port" -t "$repo_dir/web" >"$test_dir/php.log" 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true' EXIT

for _ in $(seq 1 30); do
    if curl -fsS "$base_url/verses" >/dev/null 2>&1; then
        break
    fi
    sleep 0.1
done

response_version() {
    awk 'tolower($1) == "x-data-version:" {gsub("\r", "", $2); print $2}' "$headers"
}

curl -fsS -D "$headers" -o "$body" "$base_url/verses"
version=$(response_version)
test "$version" = "3"

curl -fsS -D "$headers" -o "$body" -X POST "$base_url/verses" \
    -H 'Content-Type: application/json' \
    -H "X-Data-Version: $version" \
    --data-binary '{"text":"Be still","reference":"Psalm 46:10"}'
jq -e '.success == true and .id == 11' "$body" >/dev/null
verse_id=$(jq -r '.id' "$body")
version=$(response_version)

stale_status=$(curl -sS -D "$headers" -o "$body" -w '%{http_code}' -X PUT "$base_url/verses/$verse_id" \
    -H 'Content-Type: application/json' \
    -H 'X-Data-Version: 3' \
    --data-binary '{"text":"Stale edit","reference":"Wrong 1:1"}')
test "$stale_status" = "409"
jq -e '.error == "stale data"' "$body" >/dev/null

curl -fsS -D "$headers" -o "$body" -X PUT "$base_url/verses/$verse_id" \
    -H 'Content-Type: application/json' \
    -H "X-Data-Version: $version" \
    --data-binary '{"text":"Be still and know","reference":"Psalm 46:10"}'
jq -e '.success == true and .id == 11' "$body" >/dev/null
version=$(response_version)

curl -fsS "$base_url/verses" | jq -e \
    '.[] | select(.id == 11) | .text == "Be still and know" and .ref == "Psalm 46:10"' >/dev/null
curl -fsS "$base_url/verses/active" | jq -e '.active_verse_id == null' >/dev/null

curl -fsS -D "$headers" -o "$body" -X PUT "$base_url/verses/active/$verse_id" \
    -H 'Content-Type: application/json' \
    -H "X-Data-Version: $version"
jq -e '.success == true and .id == 11' "$body" >/dev/null
version=$(response_version)
curl -fsS "$base_url/dashboard" | jq -e '.verse.id == 11 and .verse.text == "Be still and know"' >/dev/null

curl -fsS -D "$headers" -o "$body" -X DELETE "$base_url/verses/active" \
    -H 'Content-Type: application/json' \
    -H "X-Data-Version: $version"
jq -e '.success == true' "$body" >/dev/null
version=$(response_version)
curl -fsS "$base_url/verses/active" | jq -e '.active_verse_id == null' >/dev/null

curl -fsS -D "$headers" -o "$body" -X PUT "$base_url/verses/active/$verse_id" \
    -H 'Content-Type: application/json' \
    -H "X-Data-Version: $version"
version=$(response_version)
curl -fsS -D "$headers" -o "$body" -X DELETE "$base_url/verses/$verse_id" \
    -H 'Content-Type: application/json' \
    -H "X-Data-Version: $version"
jq -e '.success == true' "$body" >/dev/null
version=$(response_version)
curl -fsS "$base_url/verses" | jq -e '[.[] | select(.id == 11)] | length == 0' >/dev/null
curl -fsS "$base_url/verses/active" | jq -e '.active_verse_id == null' >/dev/null

missing_status=$(curl -sS -D "$headers" -o "$body" -w '%{http_code}' -X DELETE "$base_url/verses/$verse_id" \
    -H 'Content-Type: application/json' \
    -H "X-Data-Version: $version")
test "$missing_status" = "404"
jq -e '.error == "not found"' "$body" >/dev/null
test "$(response_version)" = "$version"

echo "verse CRUD smoke test passed"
