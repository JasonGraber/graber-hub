# Security Policy

## Supported version

Security fixes are applied to the latest commit on `main`.

## Deployment warning

Graber Hub is designed for a trusted home network or private VPN such as Tailscale. The primary API does not include user accounts or per-request authentication. **Do not forward it directly to the public Internet.**

The optional voice webhook supports `GRABER_HUB_VOICE_TOKEN`. If it is reachable outside the LAN, set a strong token and require the `X-Graber-Hub-Token` header at the proxy or caller.

Keep these files out of Git and support requests:

- real `data.json` files;
- uploaded family photos;
- server addresses that identify a private network;
- tunnel credentials, API keys, tokens, and signing keys.

## Reporting a vulnerability

Open a GitHub security advisory on the repository instead of posting sensitive details in a public issue.
