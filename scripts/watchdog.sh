#!/bin/bash
# Graber Hub Watchdog — checks services and auto-heals

# Check lighttpd
if ! systemctl is-active --quiet lighttpd; then
    logger -t graber-watchdog "lighttpd down — restarting"
    sudo systemctl restart lighttpd
fi

# Check cloudflared
if ! systemctl is-active --quiet cloudflared; then
    logger -t graber-watchdog "cloudflared down — restarting"
    sudo systemctl restart cloudflared
fi

# Check if Chromium is running (kiosk display)
if ! pgrep -f 'chromium.*kiosk' > /dev/null; then
    logger -t graber-watchdog "Chromium kiosk not running — relaunching"
    /home/pi/startup/chromium.sh &
fi

# Check if API responds
HTTP_CODE=$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 'http://localhost/api.php/heartbeat' 2>/dev/null)
if [ "$HTTP_CODE" != "200" ]; then
    logger -t graber-watchdog "API not responding (HTTP $HTTP_CODE) — restarting lighttpd"
    sudo systemctl restart lighttpd
    sleep 2
    # Reload Chromium
    DISPLAY=:0 xdotool key F5 2>/dev/null
fi
