# Architecture

## Runtime flow

```text
Android / Wear OS / browser
          |
          | HTTP on trusted LAN or Tailscale
          v
lighttpd + PHP-CGI on Raspberry Pi
          |
          +-- api.php -------- data.json, photo settings, uploads
          +-- index.php ------ wall dashboard
          +-- photos.php ----- slideshow compatibility endpoint
          +-- voice.php ------ optional authenticated automation webhook
```

There is no database server. The API serializes writes with `flock`, writes JSON atomically inside the lock, and exposes a monotonically increasing `X-Data-Version` header. Clients include the last version on mutations; a stale mutation gets HTTP 409 and must refresh before retrying.

## Data ownership

Runtime state is deliberately outside the repository:

| Data | Installer location | Owner |
|---|---|---|
| Family state | `/var/lib/graber-hub/data.json` | `www-data` |
| Photo settings | `/var/lib/graber-hub/photo-settings.json` | `www-data` |
| Photo metadata | `/var/lib/graber-hub/photos.json` | `www-data` |
| Uploaded photos | `/var/www/html/uploads/` | `www-data` |

Environment variables can override every path. See `web/api.php` for the canonical names.

## API shape

`GET /api.php/heartbeat` is the best readiness check. It returns the dashboard payload, timer, mode, and photo settings in one response.

The dashboard endpoint materializes recurring chores for the requested day before responding. Materialization and unfinished-chore rollover are idempotent and occur under the same file lock as writes.

## Clients

- Wall display: `web/index.php`, optimized for a 16:9 kiosk.
- Android: <https://github.com/JasonGraber/graber-hub-app>.
- Wear OS: `watch/` in this repository.

The server contract is the compatibility boundary. Client-specific preferences, such as custom schedule templates and remembered server URLs, may remain local to the client.
