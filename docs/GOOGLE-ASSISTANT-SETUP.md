# Voice Automation Setup

Graber Hub includes an optional webhook for marking chores complete and reading family status. Google Home and IFTTT capabilities change frequently, so treat the examples below as the server contract and connect them through the automation service you use.

## Security first

The webhook changes family data. Do not expose it anonymously.

1. Set `GRABER_HUB_VOICE_TOKEN` to a long random value in the PHP/lighttpd environment.
2. Require callers to send the same value in `X-Graber-Hub-Token`.
3. Use HTTPS at the tunnel or reverse proxy.
4. Keep the primary `/api.php` endpoint private on your LAN or Tailscale network.

## Endpoints

Assume the public webhook base is `https://hub.example.com/voice.php`.

| Endpoint | Method | JSON body | Purpose |
|---|---|---|---|
| `/chore-done` | POST | `{"kid":"Sam","chore":"make bed"}` | Complete a matching chore |
| `/chores-left` | POST | `{"kid":"Sam"}` | Report remaining chores |
| `/whats-next` | GET | — | Report the current schedule block |
| `/ifttt` | POST | `{"value1":"Sam finished make bed"}` | Parse a single text phrase |
| `/status` | GET | — | Health check |

Every request must include:

```text
X-Graber-Hub-Token: YOUR_TOKEN
```

Responses are JSON and include a `speech` field when a spoken response is available.

## Test with curl

```bash
curl -fsS https://hub.example.com/voice.php/chore-done \
  -H 'Content-Type: application/json' \
  -H 'X-Graber-Hub-Token: YOUR_TOKEN' \
  --data '{"kid":"Sam","chore":"make bed"}'
```

Test the health endpoint with the same header before configuring an automation platform.

## IFTTT-style request

Configure a Webhooks action with:

- URL: `https://hub.example.com/voice.php/ifttt`
- Method: `POST`
- Content type: `application/json`
- Header: `X-Graber-Hub-Token: YOUR_TOKEN`
- Body: `{"value1":"{{TextField}}"}`

Example phrases:

- `Sam finished make bed`
- `Jordan did take out trash`
- `How many chores does Sam have left`

## Cloudflare Tunnel

If you choose Cloudflare Tunnel, create a named tunnel and publish only the voice route through an authenticated policy or worker. Do not publish the whole Hub just because the tunnel can reach it.

```bash
cloudflared tunnel login
cloudflared tunnel create graber-hub
cloudflared tunnel route dns graber-hub hub.example.com
```

Example origin configuration:

```yaml
tunnel: graber-hub
credentials-file: /home/pi/.cloudflared/TUNNEL_ID.json
ingress:
  - hostname: hub.example.com
    path: /voice.php/*
    service: http://127.0.0.1:80
  - service: http_status:404
```

Install the tunnel as a service only after the route, token, and HTTPS behavior have been verified.

## Matching behavior

- Kid names come from `data.json`; no family names are hard-coded.
- Chores use case-insensitive fuzzy matching.
- If no chore matches, the response lists unfinished chores for that kid.
- When all chores are complete, the response includes a celebration.
