# Agent Guide

This file is the operational handoff for coding agents working in this repository.

## Mission

Graber Hub is a self-hosted family command center. The Raspberry Pi is the source of truth; the wall dashboard and mobile clients read and mutate its HTTP API.

## Source of truth

- `web/api.php` — REST API, recurring-chore materialization, optimistic concurrency, weather proxy, photo metadata.
- `web/index.php` — wall dashboard UI.
- `web/photos.php` — Google Photos compatibility endpoint.
- `web/voice.php` — optional voice webhook. Never expose it publicly without `GRABER_HUB_VOICE_TOKEN` or another authenticated proxy.
- `web/data.example.json` — safe starter data. Never commit a real family's `data.json`, photo metadata, uploads, addresses, or tokens.
- `tests/*.sh` — API regression suite using temporary data and PHP's local server.
- `scripts/dev-demo.sh` — local demo with fictional data.
- `scripts/install-pi.sh` — Raspberry Pi OS installer.

The supported Android client is maintained separately at <https://github.com/JasonGraber/graber-hub-app>. The `android/` directory here is historical; do not make phone changes in it. The `watch/` directory is the Wear OS client.

## Invariants

1. Any write to `data.json` must hold an exclusive file lock.
2. Preserve every top-level data key when mutating state. Never save a partial object over the full file.
3. Clients may send `X-Data-Version`; stale writes must return HTTP 409.
4. Recurring chores must be idempotent for a given date.
5. Incomplete chores sort before completed chores, preserving order inside each group.
6. Runtime data lives outside Git. Examples and screenshots use fictional names only.
7. Keep PHP compatible with Raspberry Pi OS packages; avoid framework dependencies.

## Local development

```bash
sudo apt-get install php-cli jq curl
./scripts/dev-demo.sh
```

Open `http://127.0.0.1:8080/`. The demo directory is temporary and printed at startup.

## Verification

```bash
php -l web/api.php
php -l web/index.php
php -l web/photos.php
php -l web/voice.php
for test in tests/*.sh; do "$test"; done
```

For UI changes, also inspect the dashboard at 1920×1080 and a narrow browser width. Do not use live family data for screenshots.

## Deployment contract

1. Push the intended commit first.
2. Back up the live PHP files and data file.
3. Deploy only the changed web files.
4. Compare deployed hashes to the commit.
5. Verify `/api.php/heartbeat` and the rendered dashboard.

Do not overwrite live `data.json`, `photo-settings.json`, `photos.json`, or `uploads/` during deploys.
