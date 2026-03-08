# Graber Hub — Google Assistant Setup Guide

## Voice Webhook (Already Live)

Your Graber Hub voice API is accessible at:
```
https://approximately-reservation-lewis-females.trycloudflare.com/voice.php
```

**Note:** This URL changes if the tunnel restarts. For a permanent URL, set up a named Cloudflare tunnel (see bottom of this doc).

### Endpoints

| Endpoint | Method | Body | What it does |
|----------|--------|------|-------------|
| `/voice.php/chore-done` | POST | `{"kid":"Aurora","chore":"make bed"}` | Marks a chore as done |
| `/voice.php/chores-left` | POST | `{"kid":"Aurora"}` | Reports remaining chores |
| `/voice.php/whats-next` | GET | — | What's happening now on the schedule |
| `/voice.php/ifttt` | POST | `{"value1":"Aurora finished make bed"}` | IFTTT-compatible format |
| `/voice.php/status` | GET | — | Health check |

All responses include a `speech` field with a natural language response.

---

## Option A: IFTTT (Easiest — 10 minutes)

### Step 1: Create IFTTT Account
1. Go to https://ifttt.com and sign up (free tier works)
2. Connect your Google account (same one used on Google Home)

### Step 2: Create Applet — "Check Off Chore"
1. Click "Create" → "If This" → search **"Google Assistant V2"**
2. Choose **"Activate scene"** trigger
3. Scene name: `Graber Hub chore done`
4. Click "Then That" → search **"Webhooks"**  
5. Choose **"Make a web request"**
6. Configure:
   - URL: `https://approximately-reservation-lewis-females.trycloudflare.com/voice.php/ifttt`
   - Method: POST
   - Content Type: application/json
   - Body: `{"value1":"{{TextField}}"}`
7. Save

### Step 3: Create Google Home Routine
1. Open **Google Home** app on your phone
2. Tap **Automations** → **+** → **Household**
3. **Starter:** "When I say to Google Assistant"
4. Add phrases:
   - "Aurora finished make bed"
   - "Deandre finished take out trash"  
   - "Tell Graber Hub $ finished $" (use $ for variables)
5. **Action:** "Try adding your own" → "Activate IFTTT scene: Graber Hub chore done"

### Step 4: Repeat for "Chores Left"
Same process, different webhook URL:
- URL: `https://approximately-reservation-lewis-females.trycloudflare.com/voice.php/chores-left`
- Body: `{"text":"{{TextField}}"}`
- Phrases: "How many chores does Aurora have left", "What are Deandre's chores"

---

## Option B: Direct Google Home Script (More Control)

Google Home now supports **Home Script** for custom automations with HTTP actions.

1. Open Google Home app → Settings → Automations → Script editor
2. Create a new script:

```yaml
automation:
  - alias: "Chore Check Off"
    trigger:
      - platform: voice
        phrases:
          - "{name} finished {chore}"
          - "{name} did {chore}"
          - "{name} completed {chore}"
    action:
      - service: http.post
        url: "https://approximately-reservation-lewis-females.trycloudflare.com/voice.php/chore-done"
        headers:
          Content-Type: "application/json"
        body:
          kid: "{{ name }}"
          chore: "{{ chore }}"
      - service: tts.speak
        message: "{{ response.speech }}"
```

---

## Voice Commands (What the Kids Say)

Once set up, these work on any Google Home/Nest device in the house:

**Check off a chore:**
> "Hey Google, Aurora finished make bed"
> "Hey Google, Deandre did take out trash"
> "Hey Google, Truett completed feed the dog"

**Check remaining chores:**
> "Hey Google, how many chores does Alexia have left"
> "Hey Google, what are Aurora's chores"

**Check schedule:**
> "Hey Google, what's next on the schedule"

---

## Making the Tunnel Permanent

The current tunnel URL changes on restart. For a permanent setup:

1. Create a free Cloudflare account at https://dash.cloudflare.com
2. Add a domain (or use a free subdomain)
3. On the Pi:
```bash
cloudflared tunnel login
cloudflared tunnel create graber-hub
cloudflared tunnel route dns graber-hub hub.yourdomain.com
```
4. Create config at `/home/pi/.cloudflared/config.yml`:
```yaml
tunnel: graber-hub
credentials-file: /home/pi/.cloudflared/<tunnel-id>.json
ingress:
  - hostname: hub.yourdomain.com
    service: http://localhost:80
  - service: http_status:404
```
5. Install as service: `sudo cloudflared service install`
6. Update all webhook URLs to use your permanent domain

---

## Smart Features Built In

- **Fuzzy matching:** "make the bed" matches "make bed", "clean up room" matches "clean room"
- **Nickname support:** "Lexi" matches Alexia, "Dre" matches Deandre, "Tru" matches Truett
- **Dynamic chores:** Always checks today's actual chores — when Michelle adds one, voice recognizes it immediately
- **Encouraging responses:** Random positive messages ("Way to go!", "Nailed it!", "You rock!")
- **All-done celebration:** Special message when a kid finishes all chores
- **Chore listing:** If the voice can't match a chore, it reads back the remaining ones so the kid can try again
