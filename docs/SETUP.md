# Raspberry Pi Setup

This guide starts from a fresh Raspberry Pi OS installation and ends with a wall-mounted dashboard plus an optional Android controller.

## 1. Prepare the Pi

Use Raspberry Pi OS Bookworm or newer. Enable SSH, connect the Pi to the same network as the display and phone, then reserve its IP address in your router.

```bash
sudo apt-get update
sudo apt-get install -y git
git clone https://github.com/JasonGraber/graber-hub.git
cd graber-hub
GRABER_HUB_TIMEZONE=America/New_York ./scripts/install-pi.sh
```

Change `GRABER_HUB_TIMEZONE` to an [IANA timezone](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones), such as `America/Chicago` or `Europe/London`.

The installer:

- installs lighttpd, PHP-CGI, curl, and jq;
- serves the app from `/var/www/html`;
- stores writable state in `/var/lib/graber-hub`;
- creates starter data only when no data file exists;
- validates lighttpd and the heartbeat endpoint before finishing.

## 2. Customize the family

Edit the starter file:

```bash
sudo nano /var/lib/graber-hub/data.json
sudo chown www-data:www-data /var/lib/graber-hub/data.json
sudo chmod 640 /var/lib/graber-hub/data.json
```

Change the records in `kids`. Keep each `id` unique and numeric. The Android app can handle day-to-day chores, schedules, countdowns, verses, photos, and display mode after initial setup.

## 3. Configure the wall display

On the Pi itself, install Chromium if it is not already present:

```bash
sudo apt-get install -y chromium
```

Open `http://127.0.0.1/` in full-screen kiosk mode. A common autostart command is:

```bash
chromium --kiosk --noerrdialogs --disable-infobars http://127.0.0.1/
```

Exact autostart configuration differs between Raspberry Pi OS desktop releases. First prove the URL works in a normal browser, then add the kiosk command to the desktop session's autostart settings.

## 4. Connect Android

Install the companion app from <https://github.com/JasonGraber/graber-hub-app>. In Settings, enter:

```text
http://PI_ADDRESS/
```

Keep the trailing slash. Tap **Save & Connect**, then confirm the app reports **Connected**.

For access away from home, install [Tailscale](https://tailscale.com/download/linux) on the Pi and phone and use the Pi's Tailscale address. Do not forward port 80 from your router.

## 5. Photos

The app can upload local photos or point the Hub at a compatible Google Photos album. Uploaded photos are stored in `/var/www/html/uploads`; photo metadata and settings are stored under `/var/lib/graber-hub`.

Back up both locations if photos matter to you.

## 6. Optional voice webhook

`voice.php` can mark chores complete and answer simple status questions. If a proxy or automation service can reach it, require a token:

```text
GRABER_HUB_VOICE_TOKEN=<long-random-value>
```

Send that value in the `X-Graber-Hub-Token` header. See [GOOGLE-ASSISTANT-SETUP.md](GOOGLE-ASSISTANT-SETUP.md) for webhook examples.

## 7. Backups and updates

Back up runtime data:

```bash
sudo tar -czf "graber-hub-backup-$(date +%F).tar.gz" /var/lib/graber-hub /var/www/html/uploads
```

Update code without replacing family data:

```bash
cd ~/graber-hub
git pull --ff-only
./scripts/install-pi.sh
```

## Troubleshooting

- **Blank dashboard:** `sudo journalctl -u lighttpd -n 100 --no-pager`
- **API health:** `curl -fsS http://127.0.0.1/api.php/heartbeat | jq`
- **Permissions:** writable files and directories must belong to `www-data`.
- **Phone cannot connect:** confirm both devices are on the same non-guest Wi-Fi, or both are signed into Tailscale.
- **HTTP blocked on Android:** use the companion app, which explicitly permits local cleartext HTTP. Do not browse the API over public Internet.
