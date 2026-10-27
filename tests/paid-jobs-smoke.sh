#!/usr/bin/env bash
set -euo pipefail

repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
test_dir=$(mktemp -d /tmp/graber-hub-paid-jobs-test.XXXXXX)
data_file="$test_dir/data.json"
port=18797
base_url="http://127.0.0.1:$port/api.php"
today=$(TZ=America/New_York date +%F)
yesterday=$(TZ=America/New_York date -d "$today -1 day" +%F)
tomorrow=$(TZ=America/New_York date -d "$today +1 day" +%F)

jq -n --arg today "$today" --arg yesterday "$yesterday" --arg tomorrow "$tomorrow" '{
  kids: [
    {id: 1, name: "Sam", initial: "S", color: "#ec5281", sort: 1},
    {id: 2, name: "Jordan", initial: "J", color: "#03a9f4", sort: 2}
  ],
  chores: {
    ($today): [
      {id: 10, kid_id: 1, title: "Make bed", emoji: "", done: false},
      {id: 11, kid_id: 2, title: "Feed dog", emoji: "", done: true}
    ]
  },
  chore_templates: [], schedules: {}, countdowns: [], verses: [],
  paid_jobs: [
    {id: 20, title: "Wash car", description: "Outside and wheels", amount: 8, due_date: $tomorrow, status: "available", claimed_by_kid_id: null, claimed_at: null, completed_at: null, claim_reopened: false, last_claimed_by_kid_id: null},
    {id: 21, title: "Old job", description: "Expired claim", amount: 3, due_date: $yesterday, status: "claimed", claimed_by_kid_id: 1, claimed_at: "2026-01-01T12:00:00-05:00", completed_at: null, claim_reopened: false, last_claimed_by_kid_id: null}
  ],
  timer: {active: false, end_time: null, label: ""}, data_version: 4, nextId: 21
}' >"$data_file"

TZ=America/New_York PHP_CLI_SERVER_WORKERS=8 GRABER_HUB_DATA_FILE="$data_file" \
  php -S "127.0.0.1:$port" -t "$repo_dir/web" >"$test_dir/php.log" 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true' EXIT

for _ in $(seq 1 30); do
  curl -fsS "$base_url/dashboard" >/dev/null 2>&1 && break
  sleep 0.1
done

# Expired claims reopen in place and identify the former claimant.
jobs=$(curl -fsS "$base_url/paid-jobs")
printf '%s' "$jobs" | jq -e '.[0].id == 20 and .[1].id == 21 and .[1].status == "available" and .[1].last_claimed_by_kid_id == 1 and .[1].claim_reopened == true' >/dev/null

# A child with unfinished chores cannot claim; an eligible child can.
status=$(curl -sS -o "$test_dir/blocked.json" -w '%{http_code}' -X PUT "$base_url/paid-jobs/20/claim" -H 'Content-Type: application/json' -d '{"kid_id":1}')
test "$status" = 409
curl -fsS -X PUT "$base_url/paid-jobs/20/claim" -H 'Content-Type: application/json' -d '{"kid_id":2}' | jq -e '.success == true' >/dev/null

# Claimed jobs stay in the same array position and expose the child's name.
jobs=$(curl -fsS "$base_url/paid-jobs")
printf '%s' "$jobs" | jq -e '.[0].id == 20 and .[0].status == "claimed" and .[0].claimed_by_name == "Jordan"' >/dev/null

# Once the missed due date reopens a job, the former claimant cannot reclaim it,
# while a different eligible child can.
curl -fsS -X PUT "$base_url/chores/10/toggle?date=$today" | jq -e '.done == true' >/dev/null
status=$(curl -sS -o /dev/null -w '%{http_code}' -X PUT "$base_url/paid-jobs/21/claim" -H 'Content-Type: application/json' -d '{"kid_id":1}')
test "$status" = 409
curl -fsS -X PUT "$base_url/paid-jobs/21/claim" -H 'Content-Type: application/json' -d '{"kid_id":2}' | jq -e '.success == true' >/dev/null

# Complete, reopen, edit, create, validation, and delete all preserve full data.
curl -fsS -X PUT "$base_url/paid-jobs/20/complete" -H 'Content-Type: application/json' -d '{"completed":true}' | jq -e '.success == true' >/dev/null
curl -fsS -X PUT "$base_url/paid-jobs/20/complete" -H 'Content-Type: application/json' -d '{"completed":false}' | jq -e '.success == true' >/dev/null
curl -fsS -X PUT "$base_url/paid-jobs/20" -H 'Content-Type: application/json' -d "{\"title\":\"Wash and vacuum car\",\"description\":\"Inside and out\",\"amount\":10,\"due_date\":\"$tomorrow\"}" | jq -e '.success == true' >/dev/null
created=$(curl -fsS -X POST "$base_url/paid-jobs" -H 'Content-Type: application/json' -d "{\"title\":\"Sweep patio\",\"description\":\"Front and back\",\"amount\":4,\"due_date\":\"$tomorrow\"}")
created_id=$(printf '%s' "$created" | jq -r '.id')
status=$(curl -sS -o /dev/null -w '%{http_code}' -X POST "$base_url/paid-jobs" -H 'Content-Type: application/json' -d "{\"title\":\"Too much\",\"description\":\"\",\"amount\":11,\"due_date\":\"$tomorrow\"}")
test "$status" = 422
curl -fsS -X DELETE "$base_url/paid-jobs/$created_id" | jq -e '.success == true' >/dev/null
jq -e '.chores and .kids and .paid_jobs and .schedules and .data_version > 4' "$data_file" >/dev/null

echo "paid jobs smoke test passed"
