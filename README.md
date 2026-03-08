# 🏠 Graber Hub

A family command center built on a Raspberry Pi with a DAKboard display. Manage schedules, chores, countdowns, photo slideshows, and Bible verses — all from a beautiful dark-themed dashboard or companion Android/Wear OS apps.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Platform](https://img.shields.io/badge/platform-Raspberry%20Pi-red.svg)
![Android](https://img.shields.io/badge/android-companion%20app-green.svg)

---

## ✨ Features

### 📺 Dashboard (Web)
- **Schedule** — Daily time blocks with progress tracking, auto-highlighting current activity
- **Chores** — Per-kid chore cards with completion tracking and celebration animations (confetti! 🎉)
- **Countdowns** — Animated countdown to upcoming events (trips, anniversaries, etc.)
- **Timer** — Full-screen countdown timer with alarm sound
- **Bible Verses** — Daily verse rotation displayed in the header
- **Photo Mode** — Full-screen Google Photos slideshow with Ken Burns effect, weather overlay, and verse display
- **Display Modes** — Switch between Dashboard, Photos, and Auto (photos at night, dashboard during day)
- **Weather** — Live weather from Open-Meteo (no API key needed)

### 📱 Companion App (Android)
- Full CRUD for schedules, chores, and countdowns
- Display mode control (switch the Hub between Dashboard/Photos/Auto)
- Google Photos album management
- Network auto-discovery (Home WiFi + Tailscale fallback)
- Bible verse management
- Material 3 design with Shed Suite design language

### ⌚ Watch App (Wear OS)
- View today's schedule and chores
- Mark chores complete from your wrist
- Countdown timers
- Designed for Pixel Watch

### 🗣️ Voice Control
- Google Home integration via IFTTT webhooks
- "Hey Google, Aurora finished make bed"
- "Hey Google, how many chores does Deandre have left?"
- "Hey Google, what's next on the schedule?"
- Fuzzy matching + nickname support

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────┐
│              Raspberry Pi (Hub)              │
│                                             │
│  ┌──────────┐  ┌──────────┐  ┌───────────┐ │
│  │ index.php │  │ api.php  │  │ photos.php│ │
│  │ Dashboard │  │ REST API │  │ Slideshow │ │
│  └──────────┘  └──────────┘  └───────────┘ │
│       │              │              │       │
│       └──────────────┼──────────────┘       │
│                      │                      │
│            ┌─────────┴─────────┐            │
│            │   data.json       │            │
│            │   photo-settings  │            │
│            └───────────────────┘            │
│                      │                      │
│  ┌──────────┐  ┌─────┴──────┐              │
│  │ voice.php │  │ lighttpd   │              │
│  │ Webhooks  │  │ Web Server │              │
│  └──────────┘  └────────────┘              │
└──────────┬──────────────────────────────────┘
           │ HTTP (LAN / Tailscale / Cloudflare)
     ┌─────┴──────┐
     │             │
┌────┴────┐  ┌────┴────┐
│ Android │  │ Wear OS │
│  Phone  │  │  Watch  │
└─────────┘  └─────────┘
```

---

## 📋 Prerequisites

- **Raspberry Pi 4** (2GB+ RAM) with Raspbian/Raspberry Pi OS
- **DAKboard** or any display running a fullscreen kiosk browser
- **lighttpd** with PHP (FastCGI) — usually pre-installed on DAKboard
- **Cloudflare Tunnel** (optional) — for remote access and voice webhooks

---

## 🚀 Quick Start

### 1. Clone the repo

```bash
git clone https://github.com/JasonGraber/graber-hub.git
cd graber-hub
```

### 2. Deploy the web dashboard to your Pi

```bash
# Copy web files to the Pi
scp web/api.php web/index.php web/photos.php web/voice.php pi@<PI_IP>:/tmp/

# On the Pi:
sudo cp /tmp/{api,index,photos,voice}.php /var/www/html/
sudo chown www-data:www-data /var/www/html/{api,index,photos,voice}.php
sudo chmod 711 /var/www/html/{api,index,photos,voice}.php

# Create data directory
sudo mkdir -p /home/pi/graber-hub
sudo chown www-data:www-data /home/pi/graber-hub
```

### 3. Initialize data

The API auto-creates `data.json` with default kids, verses, and countdowns on first request. To customize, edit `web/data.example.json` and copy it:

```bash
cp web/data.example.json /home/pi/graber-hub/data.json
sudo chown www-data:www-data /home/pi/graber-hub/data.json
```

### 4. Point the browser to the dashboard

Update your DAKboard/kiosk config to load:
```
http://localhost/index.php
```

### 5. Install the companion app (optional)

Build from `android/` or grab the latest APK from [Releases](https://github.com/JasonGraber/graber-hub/releases).

---

## 📡 API Reference

Base URL: `http://<PI_IP>/api.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/dashboard` | GET | Full dashboard data (kids, chores, schedule, countdowns, verse) |
| `/schedule` | GET | Today's schedule blocks |
| `/schedule` | POST | Add a schedule block |
| `/schedule/{id}` | PUT | Edit a schedule block |
| `/schedule/{id}` | DELETE | Delete a schedule block |
| `/chores` | GET | Today's chores |
| `/chores` | POST | Add a chore |
| `/chores/{id}/toggle` | POST | Toggle chore completion |
| `/chores/{id}` | DELETE | Delete a chore |
| `/kids/{id}` | PUT | Update kid config (name, color) |
| `/countdowns` | GET | All countdowns |
| `/countdowns` | POST | Add a countdown |
| `/countdowns/{id}` | DELETE | Delete a countdown |
| `/timer/start` | POST | Start a timer (`{ minutes, label }`) |
| `/timer/stop` | POST | Stop the timer |
| `/timer` | GET | Timer status |
| `/mode` | GET | Current display mode |
| `/mode` | POST | Set display mode (`{ mode: "dashboard"\|"photos"\|"auto" }`) |
| `/photo-settings` | GET | Photo slideshow settings |
| `/photo-settings` | POST | Update photo settings |
| `/verses` | GET | All Bible verses |
| `/verses` | POST | Add a verse |
| `/verses/{id}` | DELETE | Delete a verse |

---

## 🗣️ Voice Control Setup

See [docs/GOOGLE-ASSISTANT-SETUP.md](docs/GOOGLE-ASSISTANT-SETUP.md) for complete Google Home / IFTTT integration guide.

**Quick version:**
1. Install Cloudflare Tunnel on the Pi for external access
2. Set up IFTTT webhooks pointing to `voice.php` endpoints
3. Create Google Home routines that trigger IFTTT scenes

---

## 🎨 Customization

### Kids
Edit `data.json` to change kid names, initials, and accent colors:
```json
{
  "kids": [
    { "id": 1, "name": "Aurora", "initial": "A", "color": "#ec5281" },
    { "id": 2, "name": "Deandre", "initial": "D", "color": "#03a9f4" }
  ]
}
```

### Design Tokens
The dashboard uses CSS variables — easy to retheme:
```css
:root {
  --bg: #0a0a0f;        /* Background */
  --surface: #14141f;    /* Card surfaces */
  --accent: #03a9f4;     /* Primary accent (blue) */
  --pink: #ec5281;       /* Secondary accent */
  --green: #4ade80;      /* Success/completion */
}
```

### Bible Verses
Add via the API or companion app. Verses rotate daily based on day-of-year.

### Photo Slideshow
1. Create a shared Google Photos album
2. Paste the share URL in the companion app or via API
3. Switch display mode to "Photos" or "Auto"

---

## 📁 Project Structure

```
graber-hub/
├── web/                    # Pi dashboard & API
│   ├── index.php           # Main dashboard UI (HTML/CSS/JS)
│   ├── api.php             # REST API (PHP)
│   ├── photos.php          # Photo slideshow engine
│   ├── voice.php           # Voice webhook endpoints
│   └── data.example.json   # Example data file
├── android/                # Companion phone app (→ graber-hub-app repo)
├── watch/                  # Wear OS watch app (→ graber-hub-watch repo)
├── docs/                   # Documentation
│   └── GOOGLE-ASSISTANT-SETUP.md
├── LICENSE
└── README.md
```

---

## 🤝 Contributing

This started as a family project, but if you want to build something similar for your family:

1. Fork the repo
2. Customize the kids, colors, and schedule templates
3. Deploy to your own Pi
4. Submit PRs for features that would help other families!

---

## 📄 License

MIT License — see [LICENSE](LICENSE) for details.

---

## 🙏 Credits

Built with love for the Graber family by Jason Graber and [Cody](https://github.com/openclaw/openclaw) (AI dev partner).

> *"Train up a child in the way he should go; even when he is old he will not depart from it."* — Proverbs 22:6
