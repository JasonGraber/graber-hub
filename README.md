# 🏠 Graber Hub

A family command center built on a Raspberry Pi. Manage schedules, chores, countdowns, photo slideshows, and Bible verses — from a beautiful dark-themed wall display, your phone, or your watch.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Platform](https://img.shields.io/badge/platform-Raspberry%20Pi-red.svg)
![Android](https://img.shields.io/badge/android-companion%20app-green.svg)
![Wear OS](https://img.shields.io/badge/wear%20os-watch%20app-blue.svg)

---

## ✨ What It Does

| | Web Dashboard | Phone App | Watch App |
|---|---|---|---|
| 📅 **Schedule** | Full-day timeline with progress tracking | Add, edit, delete blocks + templates | View today's blocks |
| ✅ **Chores** | Per-kid cards with completion animations | Assign and toggle chores | Tap to complete |
| ⏳ **Countdowns** | Animated hero countdown with floating dots | Manage upcoming events | Quick glance |
| ⏱️ **Timer** | Full-screen countdown with alarm | Start/stop from anywhere | Timer controls |
| 📖 **Verses** | Daily rotation in header | Add and manage verses | — |
| 📷 **Photos** | Ken Burns slideshow with weather overlay | Set album + switch modes | — |
| 🗣️ **Voice** | — | — | — |

**Voice control** works on any Google Home/Nest device via webhooks:
> "Hey Google, Aurora finished make bed"
> "Hey Google, what are Deandre's chores?"

---

## 📸 Display Modes

The Hub supports three display modes, switchable from the phone app:

- **🖥️ Dashboard** — Chores, schedule, countdowns, timer, Bible verse
- **📷 Photos** — Full-screen Google Photos slideshow with clock, weather, and verse overlay
- **✨ Auto** — Photos at night (8 PM–7 AM), dashboard during the day

A live mode indicator appears on both the wall display and the phone app.

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
           │ HTTP (WiFi / Tailscale / Cloudflare)
     ┌─────┴──────┐
     │             │
┌────┴────┐  ┌────┴────┐
│ Android │  │ Wear OS │
│  Phone  │  │  Watch  │
└─────────┘  └─────────┘
```

---

## 📋 Prerequisites

- **Raspberry Pi 4** (2GB+ RAM) with Raspbian / Raspberry Pi OS
- **Display** — DAKboard, any monitor, or a TV in kiosk mode
- **lighttpd** with PHP (FastCGI)
- **Cloudflare Tunnel** (optional) — for remote access and voice webhooks
- **Android phone** and/or **Wear OS watch** for companion apps

---

## 🚀 Quick Start

### 1. Clone

```bash
git clone https://github.com/JasonGraber/graber-hub.git
cd graber-hub
```

### 2. Deploy the web dashboard to your Pi

```bash
scp web/{api,index,photos,voice}.php pi@<PI_IP>:/tmp/

# On the Pi:
sudo cp /tmp/{api,index,photos,voice}.php /var/www/html/
sudo chown www-data:www-data /var/www/html/{api,index,photos,voice}.php
sudo mkdir -p /home/pi/graber-hub
sudo chown www-data:www-data /home/pi/graber-hub
```

The API auto-creates `data.json` with defaults on first request. To customize kids, copy the example:

```bash
scp web/data.example.json pi@<PI_IP>:/home/pi/graber-hub/data.json
```

### 3. Point the display browser to your dashboard

```
http://localhost/index.php
```

### 4. Install the companion apps (optional)

**Phone app:**
```bash
cd android && ./gradlew assembleDebug
# APK → android/app/build/outputs/apk/debug/app-debug.apk
```

**Watch app:**
```bash
cd watch && ./gradlew assembleDebug
adb connect <WATCH_IP>:<PORT>
adb install watch/app/build/outputs/apk/debug/app-debug.apk
```

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
| `/countdowns` | GET/POST | List or add countdowns |
| `/countdowns/{id}` | DELETE | Delete a countdown |
| `/timer/start` | POST | Start timer (`{ minutes, label }`) |
| `/timer/stop` | POST | Stop the timer |
| `/timer` | GET | Timer status |
| `/mode` | GET/POST | Get or set display mode |
| `/photo-settings` | GET/POST | Photo slideshow settings |
| `/verses` | GET/POST | List or add Bible verses |
| `/verses/{id}` | DELETE | Delete a verse |

---

## 🗣️ Voice Control

See [docs/GOOGLE-ASSISTANT-SETUP.md](docs/GOOGLE-ASSISTANT-SETUP.md) for the full Google Home / IFTTT setup guide.

**Built-in smart features:**
- Fuzzy matching — "make the bed" matches "make bed"
- Nicknames — "Lexi" → Alexia, "Dre" → Deandre, "Tru" → Truett
- Encouraging responses — random positive messages on completion
- All-done celebration — special message when a kid finishes everything

---

## 🎨 Customization

### Kids
Edit `data.json` to set names, initials, and accent colors:
```json
{
  "kids": [
    { "id": 1, "name": "Aurora", "initial": "A", "color": "#ec5281" },
    { "id": 2, "name": "Deandre", "initial": "D", "color": "#03a9f4" }
  ]
}
```

### Theme
The dashboard uses CSS custom properties — easy to retheme:
```css
:root {
  --bg: #0a0a0f;
  --surface: #14141f;
  --accent: #03a9f4;
  --pink: #ec5281;
  --green: #4ade80;
}
```

### Bible Verses
Add via the API or companion app. Verses rotate daily.

### Photo Slideshow
1. Create a shared Google Photos album
2. Paste the share URL in the companion app
3. Switch display mode to "Photos" or "Auto"

---

## 📁 Project Structure

```
graber-hub/
├── web/                          # Raspberry Pi dashboard & API
│   ├── index.php                 # Dashboard UI (HTML/CSS/JS)
│   ├── api.php                   # REST API
│   ├── photos.php                # Photo slideshow engine
│   ├── voice.php                 # Voice webhook endpoints
│   └── data.example.json         # Example data file
├── android/                      # Phone companion app
│   ├── app/src/main/java/...     # Kotlin + Jetpack Compose
│   ├── build.gradle.kts
│   └── gradlew
├── watch/                        # Wear OS watch app
│   ├── app/src/main/kotlin/...   # Kotlin + Wear Compose
│   ├── build.gradle.kts
│   └── gradlew
├── docs/                         # Documentation
│   └── GOOGLE-ASSISTANT-SETUP.md
├── LICENSE
└── README.md
```

---

## 🛠️ Tech Stack

| Component | Stack |
|-----------|-------|
| **Web Dashboard** | PHP, vanilla JS (ES5 for Pi compatibility), CSS3 |
| **API** | PHP with JSON file storage |
| **Phone App** | Kotlin, Jetpack Compose, Material 3, Retrofit |
| **Watch App** | Kotlin, Wear Compose, Horologist, Retrofit |
| **Infrastructure** | lighttpd, Cloudflare Tunnel, Tailscale |

---

## 🤝 Contributing

This started as a family project. If you want to build something similar:

1. Fork the repo
2. Customize kids, colors, schedule templates, and verses
3. Deploy to your Pi
4. PRs welcome for features that help other families!

---

## 📄 License

MIT License — see [LICENSE](LICENSE) for details.

---

Built with love for the Graber family by [Jason Graber](https://github.com/JasonGraber) and [Cody](https://github.com/openclaw/openclaw).

> *"Train up a child in the way he should go; even when he is old he will not depart from it."* — Proverbs 22:6
