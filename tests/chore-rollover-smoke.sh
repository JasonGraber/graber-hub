#!/usr/bin/env bash
set -euo pipefail

repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
test_dir=$(mktemp -d /tmp/graber-hub-rollover-test.XXXXXX)
data_file="$test_dir/data.json"
port=18792
base_url="http://127.0.0.1:$port/api.php"
today=$(TZ=America/New_York date +%F)
yesterday=$(TZ=America/New_York date -d "$today -1 day" +%F)
tomorrow=$(TZ=America/New_York date -d "$today +1 day" +%F)

jq -n \
    --arg today "$today" \
    --arg yesterday "$yesterday" \
    --arg tomorrow "$tomorrow" \
    '{
        kids: [
            {id: 1, name: "Child", initial: "C", color: "#000000", sort: 1},
            {id: 5, name: "Mom", initial: "M", color: "#ffffff", sort: 2}
        ],
        chores: {
            ($yesterday): [
                {id: 11, kid_id: 1, title: "🧹 Pick up room", emoji: "", done: false, template_id: 501},
                {id: 12, kid_id: 5, title: "🛒 Buy groceries", emoji: "", done: false},
                {id: 13, kid_id: 1, title: "Feed dog", emoji: "", done: true}
            ],
            ($today): [
                {id: 14, kid_id: 5, title: "Wash car", emoji: "🚗", done: false}
            ],
            ($tomorrow): [
                {id: 15, kid_id: 1, title: "PICK-UP ROOM", emoji: "🧹", done: false}
            ]
        },
        chore_templates: [
            {id: 501, kid_id: 1, title: "Pick up room", emoji: "🧹", repeat: "daily", start_date: $yesterday, active: true}
        ],
        schedules: {},
        countdowns: [],
        verses: [],
        timer: {active: false, end_time: null, label: ""},
        data_version: 7,
        nextId: 900
    }' >"$data_file"

TZ=America/New_York PHP_CLI_SERVER_WORKERS=8 GRABER_HUB_DATA_FILE="$data_file" \
    php -S "127.0.0.1:$port" -t "$repo_dir/web" >"$test_dir/php.log" 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true' EXIT

for _ in $(seq 1 30); do
    if curl -fsS "$base_url/chore-templates" >/dev/null 2>&1; then
        break
    fi
    sleep 0.1
done

# Looking ahead must not move today's unfinished one-off chore early, and an
# equivalent existing chore must suppress recurring materialization.
future=$(curl -fsS "$base_url/chores?date=$tomorrow")
printf '%s' "$future" | jq -e \
    '[.[] | select(.kid_id == 1 and ((.title | ascii_downcase) | contains("pick-up room")))] | length == 1' >/dev/null
printf '%s' "$future" | jq -e \
    '[.[] | select(.title == "Wash car")] | length == 0' >/dev/null

headers_one="$test_dir/headers-one"
headers_two="$test_dir/headers-two"
body="$test_dir/body"
curl -fsS -D "$headers_one" -o "$body" "$base_url/chores?date=$today"
version_one=$(awk 'tolower($1) == "x-data-version:" {gsub("\r", "", $2); print $2}' "$headers_one")

# The daily recurring chore wins the duplicate merge; the one-off rolls over;
# completed history stays yesterday; and the source unfinished items disappear.
jq -e '
    ([.[] | select(.kid_id == 1 and .template_id == 501)] | length) == 1 and
    ([.[] | select(.kid_id == 5 and .title == "🛒 Buy groceries" and .id == 12)] | length) == 1 and
    ([.[] | select(.title == "Wash car" and .id == 14)] | length) == 1
' "$body" >/dev/null
jq -e --arg yesterday "$yesterday" '
    .chores[$yesterday] == [{id: 13, kid_id: 1, title: "Feed dog", emoji: "", done: true}]
' "$data_file" >/dev/null

# Repeated and concurrent reads are idempotent and keep valid JSON.
curl -fsS -D "$headers_two" -o "$body" "$base_url/chores?date=$today"
version_two=$(awk 'tolower($1) == "x-data-version:" {gsub("\r", "", $2); print $2}' "$headers_two")
test "$version_one" = "$version_two"
seq 1 20 | xargs -P 10 -I{} curl -fsS "$base_url/chores?date=$today" -o /dev/null
jq -e --arg today "$today" --argjson version "$version_two" '
    .data_version == $version and
    ([.chores[$today][] | select(.kid_id == 1 and .template_id == 501)] | length) == 1 and
    ([.chores[$today][] | select(.kid_id == 5 and .title == "🛒 Buy groceries")] | length) == 1
' "$data_file" >/dev/null

echo "chore rollover smoke test passed"
